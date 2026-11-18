<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\MatriculaRepository;

exigir_post_com_csrf();

$origem = (string) ($_POST['origem'] ?? 'matriculas');
$id     = oid($_POST['id'] ?? null);

if ($id) {
    (new MatriculaRepository())->excluir($id);
    flash('success', 'Matrícula cancelada.');
}

redirecionar(caminho_origem($origem));
