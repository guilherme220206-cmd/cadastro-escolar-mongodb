<?php

declare(strict_types=1);

namespace App;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

/**
 * Coleção "cursos".
 *
 * Exemplo de documento:
 * {
 *   _id: ObjectId,
 *   codigo, nome, descricao, professor, modalidade,
 *   carga_horaria (int), vagas (int), data_inicio (Date),
 *   criado_em, atualizado_em
 * }
 *
 * Os estudantes NÃO são guardados dentro do curso. Um curso pode ter
 * centenas de alunos e um aluno pode estar em vários cursos, então a
 * ligação fica na coleção "matriculas".
 */
final class CursoRepository
{
    private Collection $colecao;

    public function __construct()
    {
        $this->colecao = Database::get()->selectCollection('cursos');
    }

    /**
     * Lista cursos com a quantidade de matrículas ativas (via $lookup).
     */
    public function listar(string $busca = ''): array
    {
        $pipeline = [];

        $busca = trim($busca);
        if ($busca !== '') {
            $regex = new Regex(preg_quote($busca, '/'), 'i');
            $pipeline[] = ['$match' => ['$or' => [
                ['nome' => $regex], ['codigo' => $regex], ['professor' => $regex], ['modalidade' => $regex],
            ]]];
        }

        $pipeline[] = ['$lookup' => [
            'from'         => 'matriculas',
            'localField'   => '_id',
            'foreignField' => 'curso_id',
            'as'           => 'matriculas',
        ]];
        $pipeline[] = ['$addFields' => [
            'total_ativos' => ['$size' => ['$filter' => [
                'input' => '$matriculas',
                'cond'  => ['$eq' => ['$$this.status', 'ativa']],
            ]]],
            'total_matriculas' => ['$size' => '$matriculas'],
        ]];
        $pipeline[] = ['$project' => ['matriculas' => 0]];
        $pipeline[] = ['$sort' => ['nome' => 1]];

        return $this->colecao->aggregate($pipeline)->toArray();
    }

    public function buscar(ObjectId $id): ?array
    {
        return $this->colecao->findOne(['_id' => $id]);
    }

    public function contar(): int
    {
        return $this->colecao->countDocuments();
    }

    /**
     * @return array{0: array, 1: array<string,string>} [documento, erros]
     */
    public function validar(array $in): array
    {
        $erros = [];
        $txt = fn(string $campo): string => trim((string) ($in[$campo] ?? ''));

        $doc = [
            'codigo'        => strtoupper($txt('codigo')),
            'nome'          => $txt('nome'),
            'descricao'     => $txt('descricao'),
            'professor'     => $txt('professor'),
            'modalidade'    => $txt('modalidade'),
            'carga_horaria' => (int) $txt('carga_horaria'),
            'vagas'         => (int) $txt('vagas'),
            'data_inicio'   => data_para_mongo($txt('data_inicio')),
        ];

        if (!preg_match('/^[A-Z0-9\-]{2,15}$/', $doc['codigo'])) $erros['codigo'] = 'Use de 2 a 15 letras, números ou hífen.';
        if (mb_strlen($doc['nome']) < 3)                    $erros['nome'] = 'Informe o nome do curso.';
        if (mb_strlen($doc['descricao']) < 10)              $erros['descricao'] = 'Descreva o curso (mín. 10 caracteres).';
        if (mb_strlen($doc['professor']) < 3)               $erros['professor'] = 'Informe o professor responsável.';
        if (!in_array($doc['modalidade'], MODALIDADES, true)) $erros['modalidade'] = 'Selecione a modalidade.';
        if ($doc['carga_horaria'] <= 0)                     $erros['carga_horaria'] = 'Carga horária deve ser maior que zero.';
        if ($doc['vagas'] <= 0)                             $erros['vagas'] = 'Número de vagas deve ser maior que zero.';
        if ($doc['data_inicio'] === null)                   $erros['data_inicio'] = 'Informe a data de início.';

        return [$doc, $erros];
    }

    public function inserir(array $doc): ObjectId
    {
        $doc['criado_em'] = new UTCDateTime();
        $doc['atualizado_em'] = new UTCDateTime();
        return $this->colecao->insertOne($doc)->getInsertedId();
    }

    public function atualizar(ObjectId $id, array $doc): void
    {
        $doc['atualizado_em'] = new UTCDateTime();
        $this->colecao->updateOne(['_id' => $id], ['$set' => $doc]);
    }

    /**
     * Exclui o curso e as matrículas ligadas a ele (evita referências órfãs).
     */
    public function excluir(ObjectId $id): void
    {
        Database::get()->selectCollection('matriculas')->deleteMany(['curso_id' => $id]);
        $this->colecao->deleteOne(['_id' => $id]);
    }
}
