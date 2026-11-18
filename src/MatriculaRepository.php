<?php

declare(strict_types=1);

namespace App;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

/**
 * Coleção "matriculas" — resolve o relacionamento N:N entre Estudante e Curso.
 *
 * Exemplo de documento:
 * {
 *   _id: ObjectId,
 *   estudante_id: ObjectId  -> referência a estudantes._id
 *   curso_id:     ObjectId  -> referência a cursos._id
 *   data_matricula: Date,
 *   status: "ativa" | "trancada" | "concluida"
 * }
 *
 * Um índice único em (estudante_id, curso_id) impede matrícula duplicada.
 * Os dados de estudante e curso são "juntados" na leitura com $lookup.
 */
final class MatriculaRepository
{
    private Collection $colecao;

    public function __construct()
    {
        $this->colecao = Database::get()->selectCollection('matriculas');
    }

    /**
     * Lista matrículas já com os dados do estudante e do curso ($lookup).
     */
    public function listar(array $filtro = []): array
    {
        $pipeline = [];
        if ($filtro) {
            $pipeline[] = ['$match' => $filtro];
        }

        array_push(
            $pipeline,
            ['$lookup' => [
                'from'         => 'estudantes',
                'localField'   => 'estudante_id',
                'foreignField' => '_id',
                'as'           => 'estudante',
            ]],
            ['$unwind' => '$estudante'],
            ['$lookup' => [
                'from'         => 'cursos',
                'localField'   => 'curso_id',
                'foreignField' => '_id',
                'as'           => 'curso',
            ]],
            ['$unwind' => '$curso'],
            ['$project' => [
                'estudante_id'    => 1,
                'curso_id'        => 1,
                'status'          => 1,
                'data_matricula'  => 1,
                'estudante.nome'  => 1,
                'estudante.ra'    => 1,
                'estudante.email' => 1,
                'curso.nome'      => 1,
                'curso.codigo'    => 1,
                'curso.modalidade'    => 1,
                'curso.carga_horaria' => 1,
            ]],
            ['$sort' => ['data_matricula' => -1]],
        );

        return $this->colecao->aggregate($pipeline)->toArray();
    }

    public function porEstudante(ObjectId $estudanteId): array
    {
        return $this->listar(['estudante_id' => $estudanteId]);
    }

    public function porCurso(ObjectId $cursoId): array
    {
        return $this->listar(['curso_id' => $cursoId]);
    }

    public function contar(array $filtro = []): int
    {
        return $this->colecao->countDocuments($filtro);
    }

    public function ativasNoCurso(ObjectId $cursoId): int
    {
        return $this->colecao->countDocuments(['curso_id' => $cursoId, 'status' => 'ativa']);
    }

    /**
     * Cursos em que o estudante ainda NÃO está matriculado (para o select).
     */
    public function cursosDisponiveis(ObjectId $estudanteId): array
    {
        $jaMatriculado = $this->colecao->distinct('curso_id', ['estudante_id' => $estudanteId]);
        return Database::get()->selectCollection('cursos')->find(
            ['_id' => ['$nin' => $jaMatriculado]],
            ['sort' => ['nome' => 1], 'projection' => ['nome' => 1, 'codigo' => 1, 'vagas' => 1]]
        )->toArray();
    }

    /**
     * Estudantes que ainda NÃO estão matriculados no curso (para o select).
     */
    public function estudantesDisponiveis(ObjectId $cursoId): array
    {
        $jaMatriculado = $this->colecao->distinct('estudante_id', ['curso_id' => $cursoId]);
        return Database::get()->selectCollection('estudantes')->find(
            ['_id' => ['$nin' => $jaMatriculado]],
            ['sort' => ['nome' => 1], 'projection' => ['nome' => 1, 'ra' => 1]]
        )->toArray();
    }

    /**
     * Cria a matrícula. Retorna null em caso de sucesso ou a mensagem de erro.
     */
    public function matricular(ObjectId $estudanteId, ObjectId $cursoId): ?string
    {
        $db = Database::get();

        if (!$db->selectCollection('estudantes')->countDocuments(['_id' => $estudanteId], ['limit' => 1])) {
            return 'Estudante não encontrado.';
        }
        $curso = $db->selectCollection('cursos')->findOne(['_id' => $cursoId]);
        if (!$curso) {
            return 'Curso não encontrado.';
        }
        if ($this->ativasNoCurso($cursoId) >= (int) $curso['vagas']) {
            return 'Não há vagas disponíveis no curso ' . $curso['nome'] . '.';
        }

        try {
            $this->colecao->insertOne([
                'estudante_id'   => $estudanteId,
                'curso_id'       => $cursoId,
                'data_matricula' => new UTCDateTime(),
                'status'         => 'ativa',
            ]);
        } catch (\Throwable $e) {
            if (erro_duplicado($e)) {
                return 'Este estudante já está matriculado neste curso.';
            }
            throw $e;
        }

        return null;
    }

    /**
     * Altera o status. Reativar exige vaga disponível.
     */
    public function alterarStatus(ObjectId $id, string $status): ?string
    {
        if (!array_key_exists($status, STATUS_MATRICULA)) {
            return 'Status inválido.';
        }
        $matricula = $this->colecao->findOne(['_id' => $id]);
        if (!$matricula) {
            return 'Matrícula não encontrada.';
        }

        if ($status === 'ativa' && $matricula['status'] !== 'ativa') {
            $curso = Database::get()->selectCollection('cursos')->findOne(['_id' => $matricula['curso_id']]);
            if ($curso && $this->ativasNoCurso($matricula['curso_id']) >= (int) $curso['vagas']) {
                return 'Não há vagas para reativar esta matrícula.';
            }
        }

        $this->colecao->updateOne(['_id' => $id], ['$set' => ['status' => $status]]);
        return null;
    }

    public function excluir(ObjectId $id): void
    {
        $this->colecao->deleteOne(['_id' => $id]);
    }

    /**
     * Ranking de cursos por matrículas ativas (para o painel inicial).
     */
    public function rankingCursos(int $limite = 5): array
    {
        return $this->colecao->aggregate([
            ['$match' => ['status' => 'ativa']],
            ['$group' => ['_id' => '$curso_id', 'total' => ['$sum' => 1]]],
            ['$sort' => ['total' => -1]],
            ['$limit' => $limite],
            ['$lookup' => ['from' => 'cursos', 'localField' => '_id', 'foreignField' => '_id', 'as' => 'curso']],
            ['$unwind' => '$curso'],
        ])->toArray();
    }
}
