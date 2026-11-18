<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Database;
use App\MatriculaRepository;

$statusFiltro = (string) ($_GET['status'] ?? '');
$filtro = array_key_exists($statusFiltro, STATUS_MATRICULA) ? ['status' => $statusFiltro] : [];

$listaMatriculas = (new MatriculaRepository())->listar($filtro);

$db = Database::get();
$estudantes = $db->selectCollection('estudantes')->find([], ['sort' => ['nome' => 1], 'projection' => ['nome' => 1, 'ra' => 1]])->toArray();
$cursos = $db->selectCollection('cursos')->find([], ['sort' => ['nome' => 1], 'projection' => ['nome' => 1, 'codigo' => 1]])->toArray();
$origem = 'matriculas';

$titulo = 'Matrículas';
$menu = 'matriculas';
require __DIR__ . '/../../templates/header.php';
?>

<h1 class="h3 mb-3">Matrículas</h1>

<div class="card mb-3">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-plus-circle me-1"></i> Nova matrícula</div>
    <div class="card-body">
        <?php if (!$estudantes || !$cursos): ?>
            <p class="text-muted mb-0">Cadastre ao menos um estudante e um curso para fazer matrículas.</p>
        <?php else: ?>
            <form action="<?= url('matriculas/salvar.php') ?>" method="post" class="row g-2 align-items-end">
                <?= csrf_campo() ?>
                <input type="hidden" name="origem" value="<?= $origem ?>">
                <div class="col-md-5">
                    <label class="form-label" for="estudante_id">Estudante</label>
                    <select name="estudante_id" id="estudante_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($estudantes as $est): ?>
                            <option value="<?= $est['_id'] ?>"><?= e($est['nome'] . ' (RA ' . $est['ra'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="curso_id">Curso</label>
                    <select name="curso_id" id="curso_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($cursos as $c): ?>
                            <option value="<?= $c['_id'] ?>"><?= e($c['codigo'] . ' — ' . $c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary"><i class="bi bi-link-45deg"></i> Matricular</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <span class="fw-semibold">Todas as matrículas (<?= count($listaMatriculas) ?>)</span>
        <div class="btn-group btn-group-sm">
            <a href="<?= url('matriculas/index.php') ?>" class="btn btn-outline-secondary <?= !$filtro ? 'active' : '' ?>">Todas</a>
            <?php foreach (STATUS_MATRICULA as $valor => $info): ?>
                <a href="<?= url('matriculas/index.php?status=' . $valor) ?>"
                   class="btn btn-outline-secondary <?= $statusFiltro === $valor ? 'active' : '' ?>"><?= $info['rotulo'] ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php require __DIR__ . '/../../templates/tabela_matriculas.php'; ?>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
