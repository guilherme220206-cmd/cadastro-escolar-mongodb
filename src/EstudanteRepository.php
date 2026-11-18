<?php

declare(strict_types=1);

namespace App;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

/**
 * Coleção "estudantes".
 *
 * Exemplo de documento:
 * {
 *   _id: ObjectId,
 *   nome, ra, cpf, email, telefone, data_nascimento (Date),
 *   endereco: { cep, logradouro, numero, complemento, bairro, cidade, estado },
 *   criado_em, atualizado_em
 * }
 *
 * O endereço é um subdocumento porque pertence a um único estudante (1:1)
 * e é sempre lido junto com ele. Já os cursos NÃO ficam embutidos aqui:
 * a relação N:N é feita pela coleção "matriculas" (ver MatriculaRepository).
 */
final class EstudanteRepository
{
    private Collection $colecao;

    public function __construct()
    {
        $this->colecao = Database::get()->selectCollection('estudantes');
    }

    /**
     * Lista estudantes com a quantidade de cursos em que estão matriculados
     * (usa $lookup na coleção de matrículas).
     */
    public function listar(string $busca = ''): array
    {
        $pipeline = [];

        $busca = trim($busca);
        if ($busca !== '') {
            $regex = new Regex(preg_quote($busca, '/'), 'i');
            $ou = [['nome' => $regex], ['email' => $regex], ['ra' => $regex], ['endereco.cidade' => $regex]];
            if (so_digitos($busca) !== '') {
                $ou[] = ['cpf' => new Regex(preg_quote(so_digitos($busca), '/'))];
            }
            $pipeline[] = ['$match' => ['$or' => $ou]];
        }

        $pipeline[] = ['$lookup' => [
            'from'         => 'matriculas',
            'localField'   => '_id',
            'foreignField' => 'estudante_id',
            'as'           => 'matriculas',
        ]];
        $pipeline[] = ['$addFields' => ['total_cursos' => ['$size' => '$matriculas']]];
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
     * Valida e normaliza os dados do formulário.
     *
     * @return array{0: array, 1: array<string,string>} [documento, erros]
     */
    public function validar(array $in): array
    {
        $erros = [];
        $txt = fn(string $campo): string => trim((string) ($in[$campo] ?? ''));

        $doc = [
            'nome'            => $txt('nome'),
            'ra'              => strtoupper($txt('ra')),
            'cpf'             => so_digitos($txt('cpf')),
            'email'           => mb_strtolower($txt('email')),
            'telefone'        => so_digitos($txt('telefone')),
            'data_nascimento' => $txt('data_nascimento'),
            'endereco'        => [
                'cep'         => so_digitos($txt('cep')),
                'logradouro'  => $txt('logradouro'),
                'numero'      => $txt('numero'),
                'complemento' => $txt('complemento'),
                'bairro'      => $txt('bairro'),
                'cidade'      => $txt('cidade'),
                'estado'      => strtoupper($txt('estado')),
            ],
        ];

        if (mb_strlen($doc['nome']) < 3)                 $erros['nome'] = 'Informe o nome completo.';
        if ($doc['ra'] === '')                           $erros['ra'] = 'Informe o RA (registro acadêmico).';
        if (!cpf_valido($doc['cpf']))                    $erros['cpf'] = 'CPF inválido.';
        if (!filter_var($doc['email'], FILTER_VALIDATE_EMAIL)) $erros['email'] = 'E-mail inválido.';
        if (!in_array(strlen($doc['telefone']), [10, 11], true)) $erros['telefone'] = 'Telefone deve ter DDD + número.';

        $nascimento = data_para_mongo($doc['data_nascimento']);
        if (!$nascimento || $nascimento->toDateTime() > new \DateTime()) {
            $erros['data_nascimento'] = 'Data de nascimento inválida.';
        }

        $end = $doc['endereco'];
        if (strlen($end['cep']) !== 8)             $erros['cep'] = 'CEP deve ter 8 dígitos.';
        if ($end['logradouro'] === '')             $erros['logradouro'] = 'Informe o logradouro.';
        if ($end['numero'] === '')                 $erros['numero'] = 'Informe o número.';
        if ($end['bairro'] === '')                 $erros['bairro'] = 'Informe o bairro.';
        if ($end['cidade'] === '')                 $erros['cidade'] = 'Informe a cidade.';
        if (!in_array($end['estado'], UFS, true))  $erros['estado'] = 'Selecione o estado.';

        $doc['data_nascimento'] = $nascimento;

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
     * Exclui o estudante e as matrículas dele (evita referências órfãs).
     */
    public function excluir(ObjectId $id): void
    {
        Database::get()->selectCollection('matriculas')->deleteMany(['estudante_id' => $id]);
        $this->colecao->deleteOne(['_id' => $id]);
    }
}
