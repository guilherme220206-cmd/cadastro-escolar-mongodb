<?php
/** @var string $titulo */
/** @var string $menu   */
$titulo = $titulo ?? 'Cadastro Escolar';
$menu   = $menu ?? '';
$itens  = [
    'inicio'     => ['Início', 'index.php', 'bi-house'],
    'estudantes' => ['Estudantes', 'estudantes/index.php', 'bi-people'],
    'cursos'     => ['Cursos', 'cursos/index.php', 'bi-journal-bookmark'],
    'matriculas' => ['Matrículas', 'matriculas/index.php', 'bi-link-45deg'],
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Cadastro Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; }
        .card { border: 0; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .table > :not(caption) > * > * { vertical-align: middle; }
        .stat .bi { font-size: 2rem; opacity: .8; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= url('index.php') ?>">
            <i class="bi bi-mortarboard-fill me-1"></i> Cadastro Escolar
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto">
                <?php foreach ($itens as $chave => [$rotulo, $link, $icone]): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $menu === $chave ? 'active fw-semibold' : '' ?>" href="<?= url($link) ?>">
                            <i class="bi <?= $icone ?> me-1"></i><?= $rotulo ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container pb-5">
    <?php foreach (pegar_flash() as $f): ?>
        <div class="alert alert-<?= e($f['tipo']) ?> alert-dismissible fade show" role="alert">
            <?= e($f['mensagem']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
