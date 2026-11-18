<?php

declare(strict_types=1);

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

/**
 * Escapa texto para exibição segura no HTML.
 */
function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Monta uma URL a partir da pasta public/, funcionando tanto com
 * "php -S localhost:8000 -t public" quanto dentro do XAMPP/WAMP
 * (ex.: http://localhost/cadastro-escolar-mongodb/public/).
 */
function url(string $caminho = ''): string
{
    static $base = null;

    if ($base === null) {
        $publicDir = str_replace('\\', '/', (string) realpath(__DIR__ . '/../public'));
        $script    = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
        $relativo  = substr($script, strlen($publicDir));            // ex.: /estudantes/index.php
        $nome      = $_SERVER['SCRIPT_NAME'] ?? '';
        $base      = rtrim(substr($nome, 0, strlen($nome) - strlen($relativo)), '/');
    }

    return $base . '/' . ltrim($caminho, '/');
}

function redirecionar(string $caminho): never
{
    header('Location: ' . url($caminho));
    exit;
}

/* ---------- Mensagens flash (exibidas uma única vez) ---------- */

function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function pegar_flash(): array
{
    $mensagens = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensagens;
}

/* ---------- Proteção CSRF simples para formulários POST ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function exigir_post_com_csrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
        || !hash_equals(csrf_token(), (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(400);
        exit('Requisição inválida.');
    }
}

/**
 * Para onde voltar após uma ação de matrícula.
 * $origem: "estudante:<id>", "curso:<id>" ou "matriculas".
 */
function caminho_origem(string $origem): string
{
    if (preg_match('/^(estudante|curso):([a-f0-9]{24})$/i', $origem, $m)) {
        return ($m[1] === 'estudante' ? 'estudantes' : 'cursos') . '/ver.php?id=' . $m[2];
    }
    return 'matriculas/index.php';
}

/* ---------- Conversões MongoDB ---------- */

/**
 * Converte uma string em ObjectId; retorna null se for inválida.
 */
function oid(?string $id): ?ObjectId
{
    if ($id === null || !preg_match('/^[a-f0-9]{24}$/i', $id)) {
        return null;
    }
    return new ObjectId($id);
}

/**
 * Converte "AAAA-MM-DD" (input type="date") em UTCDateTime.
 */
function data_para_mongo(?string $data): ?UTCDateTime
{
    if (!$data) {
        return null;
    }
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $data, new DateTimeZone('UTC'));
    return $dt ? new UTCDateTime($dt) : null;
}

/**
 * Formata um UTCDateTime para exibição. $formato 'Y-m-d' serve para preencher input date.
 */
function data_formatada(mixed $data, string $formato = 'd/m/Y'): string
{
    if (!$data instanceof UTCDateTime) {
        return '';
    }
    $dt = $data->toDateTime();
    // Datas "só dia" foram gravadas em UTC; horários são exibidos no fuso local.
    if ($formato !== 'd/m/Y' && $formato !== 'Y-m-d') {
        $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }
    return $dt->format($formato);
}

function so_digitos(?string $valor): string
{
    return preg_replace('/\D+/', '', (string) $valor);
}

function formatar_cpf(?string $cpf): string
{
    $cpf = so_digitos($cpf);
    return strlen($cpf) === 11
        ? substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2)
        : (string) $cpf;
}

function formatar_telefone(?string $tel): string
{
    $tel = so_digitos($tel);
    return match (strlen($tel)) {
        11      => sprintf('(%s) %s-%s', substr($tel, 0, 2), substr($tel, 2, 5), substr($tel, 7)),
        10      => sprintf('(%s) %s-%s', substr($tel, 0, 2), substr($tel, 2, 4), substr($tel, 6)),
        default => $tel,
    };
}

function cpf_valido(string $cpf): bool
{
    $cpf = so_digitos($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int) $cpf[$i] * (($t + 1) - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ((int) $cpf[$t] !== $digito) {
            return false;
        }
    }
    return true;
}

/**
 * Verifica se a exceção é de chave duplicada (índice único).
 */
function erro_duplicado(Throwable $e): bool
{
    return $e instanceof MongoDB\Driver\Exception\Exception
        && ($e->getCode() === 11000 || str_contains($e->getMessage(), 'E11000'));
}

/**
 * Descobre qual índice único foi violado (ex.: "cpf_unico").
 */
function indice_duplicado(Throwable $e): string
{
    return preg_match('/index:\s*(\S+)/', $e->getMessage(), $m) ? $m[1] : '';
}

const UFS = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR',
             'PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

const MODALIDADES = ['Presencial', 'EAD', 'Híbrido'];

const STATUS_MATRICULA = [
    'ativa'     => ['rotulo' => 'Ativa',     'cor' => 'success'],
    'trancada'  => ['rotulo' => 'Trancada',  'cor' => 'warning'],
    'concluida' => ['rotulo' => 'Concluída', 'cor' => 'secondary'],
];
