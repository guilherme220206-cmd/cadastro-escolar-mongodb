<?php

declare(strict_types=1);

namespace App;

use MongoDB\Client;
use MongoDB\Database as MongoDatabase;

/**
 * Ponto único de acesso ao MongoDB.
 *
 * Coleções:
 *  - estudantes  -> dados do estudante + endereço (subdocumento, relação 1:1)
 *  - cursos      -> dados do curso
 *  - matriculas  -> coleção associativa (N:N) com referências estudante_id e curso_id
 */
final class Database
{
    private static ?MongoDatabase $db = null;
    private static bool $indicesCriados = false;

    public static function get(): MongoDatabase
    {
        if (self::$db === null) {
            $uri    = getenv('MONGODB_URI') ?: 'mongodb://localhost:27017';
            $nomeDb = getenv('MONGODB_DB') ?: 'escola';

            $client = new Client($uri, [], [
                // Documentos retornados como arrays PHP comuns (mais simples nas views)
                'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
            ]);
            self::$db = $client->selectDatabase($nomeDb);
        }
        return self::$db;
    }

    /**
     * Cria os índices (operação idempotente: se já existirem, nada muda).
     * Os índices únicos garantem a integridade dos cadastros e impedem
     * que o mesmo estudante seja matriculado duas vezes no mesmo curso.
     */
    public static function criarIndices(): void
    {
        if (self::$indicesCriados) {
            return;
        }
        $db = self::get();

        $db->estudantes->createIndexes([
            ['key' => ['cpf' => 1],   'unique' => true, 'name' => 'cpf_unico'],
            ['key' => ['email' => 1], 'unique' => true, 'name' => 'email_unico'],
            ['key' => ['ra' => 1],    'unique' => true, 'name' => 'ra_unico'],
        ]);

        $db->cursos->createIndexes([
            ['key' => ['codigo' => 1], 'unique' => true, 'name' => 'codigo_unico'],
        ]);

        $db->matriculas->createIndexes([
            ['key' => ['estudante_id' => 1, 'curso_id' => 1], 'unique' => true, 'name' => 'estudante_curso_unico'],
            ['key' => ['curso_id' => 1], 'name' => 'por_curso'],
        ]);

        self::$indicesCriados = true;
    }
}
