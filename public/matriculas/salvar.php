<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\MatriculaRepository;

exigir_post_com_csrf();

$origem      = (string) ($_POST['origem'] ?? 'matriculas');
$estudanteId = oid($_POST['estudante_id'] ?? null);
$cursoId     = oid($_POST['curso_id'] ?? null);

if (!$estudanteId || !$cursoId) {
    flash('warning', 'Selecione o estudante e o curso.');
} elseif ($erro = (new MatriculaRepository())->matricular($estudanteId, $cursoId)) {
    flash('danger', $erro);
} else {
    flash('success', 'Matrícula realizada com sucesso.');
}

redirecionar(caminho_origem($origem));
