<?php

declare(strict_types=1);

/*
 * Arquivo incluído no topo de todas as páginas:
 * carrega o Composer, lê o .env e inicia a sessão.
 */

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(500);
    exit('Dependências não instaladas. Rode "composer install" na raiz do projeto.');
}
require $autoload;

date_default_timezone_set('America/Sao_Paulo');

// Leitor simples de .env (sem dependências extras)
$arquivoEnv = __DIR__ . '/../.env';
if (is_file($arquivoEnv)) {
    foreach (file($arquivoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
        $linha = trim($linha);
        if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
            continue;
        }
        [$chave, $valor] = array_map('trim', explode('=', $linha, 2));
        $valor = trim($valor, "\"'");
        if (getenv($chave) === false) {
            putenv("$chave=$valor");
            $_ENV[$chave] = $valor;
        }
    }
}

if (PHP_SAPI === 'cli') {
    // Uso em linha de comando (ex.: bin/seed.php): sem sessão nem HTML
    App\Database::criarIndices();
    return;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Mensagem amigável para falhas de conexão/consulta no MongoDB
set_exception_handler(function (Throwable $e): void {
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><div style="font-family:sans-serif;max-width:720px;margin:40px auto">'
        . '<h3>Erro ao acessar o MongoDB</h3>'
        . '<p>Verifique se o MongoDB está rodando e se a variável <code>MONGODB_URI</code> do arquivo <code>.env</code> está correta.</p>'
        . '<pre style="white-space:pre-wrap;background:#f5f5f5;padding:12px">' . e($e->getMessage()) . '</pre></div>';
});

// Índices são criados uma vez por sessão (evita idas desnecessárias ao banco)
if (empty($_SESSION['indices_ok'])) {
    App\Database::criarIndices();
    $_SESSION['indices_ok'] = true;
}
