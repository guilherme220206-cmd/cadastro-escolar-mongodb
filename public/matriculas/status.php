<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\MatriculaRepository;

exigir_post_com_csrf();

$origem = (string) ($_POST['origem'] ?? 'matriculas');
$id     = oid($_POST['id'] ?? null);

if ($id) {
    $erro = (new MatriculaRepository())->alterarStatus($id, (string) ($_POST['status'] ?? ''));
    $erro ? flash('danger', $erro) : flash('success', 'Status da matrícula atualizado.');
}

redirecionar(caminho_origem($origem));
