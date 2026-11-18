<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\CursoRepository;
use App\EstudanteRepository;
use App\MatriculaRepository;

$matriculas = new MatriculaRepository();

$totais = [
    ['Estudantes', (new EstudanteRepository())->contar(), 'bi-people', 'primary', 'estudantes/index.php'],
    ['Cursos', (new CursoRepository())->contar(), 'bi-journal-bookmark', 'success', 'cursos/index.php'],
    ['Matrículas ativas', $matriculas->contar(['status' => 'ativa']), 'bi-link-45deg', 'warning', 'matriculas/index.php'],
];
$ranking = $matriculas->rankingCursos();

$titulo = 'Início';
$menu = 'inicio';
require __DIR__ . '/../templates/header.php';
?>

<h1 class="h3 mb-4">Painel</h1>

<div class="row g-3 mb-4">
    <?php foreach ($totais as [$rotulo, $valor, $icone, $cor, $link]): ?>
        <div class="col-md-4">
            <a href="<?= url($link) ?>" class="text-decoration-none">
                <div class="card stat h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small"><?= e($rotulo) ?></div>
                            <div class="display-6 fw-semibold text-body"><?= $valor ?></div>
                        </div>
                        <i class="bi <?= $icone ?> text-<?= $cor ?>"></i>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Cursos com mais matrículas ativas</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($ranking as $r): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="<?= url('cursos/ver.php?id=' . $r['_id']) ?>">
                            <span class="badge text-bg-light me-1"><?= e($r['curso']['codigo']) ?></span><?= e($r['curso']['nome']) ?>
                        </a>
                        <span class="badge text-bg-primary rounded-pill"><?= $r['total'] ?> / <?= (int) $r['curso']['vagas'] ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$ranking): ?>
                    <li class="list-group-item text-muted">Nenhuma matrícula ainda.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Como os dados estão organizados</div>
            <div class="card-body small">
                <p><strong>estudantes</strong> — dados pessoais com o <em>endereço embutido</em> (relação 1:1).</p>
                <p><strong>cursos</strong> — dados do curso, sem lista de alunos.</p>
                <p class="mb-0"><strong>matriculas</strong> — coleção de ligação (N:N) com
                    <code>estudante_id</code> e <code>curso_id</code> referenciando as outras coleções,
                    juntadas nas consultas com <code>$lookup</code>.</p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
