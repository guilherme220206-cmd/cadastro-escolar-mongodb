<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\CursoRepository;
use App\MatriculaRepository;

$id = oid($_GET['id'] ?? null);
$curso = $id ? (new CursoRepository())->buscar($id) : null;
if (!$curso) {
    flash('danger', 'Curso não encontrado.');
    redirecionar('cursos/index.php');
}

$repoMatriculas    = new MatriculaRepository();
$listaMatriculas   = $repoMatriculas->porCurso($id);
$estudantesLivres  = $repoMatriculas->estudantesDisponiveis($id);
$ativos            = $repoMatriculas->ativasNoCurso($id);
$temVaga           = $ativos < (int) $curso['vagas'];
$origem            = 'curso:' . $id;

$titulo = $curso['nome'];
$menu = 'cursos';
require __DIR__ . '/../../templates/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('cursos/index.php') ?>">Cursos</a></li>
        <li class="breadcrumb-item active"><?= e($curso['nome']) ?></li>
    </ol>
</nav>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e($curso['nome']) ?> <span class="badge text-bg-light border fs-6"><?= e($curso['codigo']) ?></span></h1>
    <a href="<?= url('cursos/form.php?id=' . $id) ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i> Editar</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <p class="mb-3"><?= nl2br(e($curso['descricao'])) ?></p>
        <div class="row text-center g-2">
            <div class="col-6 col-md"><div class="text-muted small">Professor</div><div class="fw-semibold"><?= e($curso['professor']) ?></div></div>
            <div class="col-6 col-md"><div class="text-muted small">Modalidade</div><div class="fw-semibold"><?= e($curso['modalidade']) ?></div></div>
            <div class="col-6 col-md"><div class="text-muted small">Carga horária</div><div class="fw-semibold"><?= (int) $curso['carga_horaria'] ?>h</div></div>
            <div class="col-6 col-md"><div class="text-muted small">Início</div><div class="fw-semibold"><?= data_formatada($curso['data_inicio']) ?></div></div>
            <div class="col-12 col-md"><div class="text-muted small">Vagas ocupadas</div>
                <div class="fw-semibold <?= $temVaga ? '' : 'text-danger' ?>"><?= $ativos ?> / <?= (int) $curso['vagas'] ?></div></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-people me-1"></i> Estudantes matriculados (<?= count($listaMatriculas) ?>)</span>
        <?php if (!$temVaga): ?>
            <span class="badge text-bg-danger">Curso lotado</span>
        <?php elseif ($estudantesLivres): ?>
            <form action="<?= url('matriculas/salvar.php') ?>" method="post" class="d-flex gap-2">
                <?= csrf_campo() ?>
                <input type="hidden" name="curso_id" value="<?= $id ?>">
                <input type="hidden" name="origem" value="<?= e($origem) ?>">
                <select name="estudante_id" class="form-select form-select-sm" required aria-label="Estudante">
                    <option value="">Selecione um estudante...</option>
                    <?php foreach ($estudantesLivres as $est): ?>
                        <option value="<?= $est['_id'] ?>"><?= e($est['nome'] . ' (RA ' . $est['ra'] . ')') ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> Matricular</button>
            </form>
        <?php endif; ?>
    </div>
    <?php
    $mostrarCurso = false;
    require __DIR__ . '/../../templates/tabela_matriculas.php';
    ?>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
