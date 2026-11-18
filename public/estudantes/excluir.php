<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\EstudanteRepository;

exigir_post_com_csrf();

$id = oid($_POST['id'] ?? null);
if ($id) {
    (new EstudanteRepository())->excluir($id);
    flash('success', 'Estudante excluído (e suas matrículas removidas).');
}

redirecionar('estudantes/index.php');
