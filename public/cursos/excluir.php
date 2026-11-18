<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\CursoRepository;

exigir_post_com_csrf();

$id = oid($_POST['id'] ?? null);
if ($id) {
    (new CursoRepository())->excluir($id);
    flash('success', 'Curso excluído (e suas matrículas removidas).');
}

redirecionar('cursos/index.php');
