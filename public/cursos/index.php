<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\CursoRepository;

$busca = trim((string) ($_GET['q'] ?? ''));
$cursos = (new CursoRepository())->listar($busca);

$titulo = 'Cursos';
$menu = 'cursos';
require __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Cursos</h1>
    <a href="<?= url('cursos/form.php') ?>" class="btn btn-success"><i class="bi bi-plus-lg"></i> Novo curso</a>
</div>

<form class="mb-3" method="get">
    <div class="input-group">
        <input type="search" name="q" class="form-control" placeholder="Buscar por nome, código, professor ou modalidade" value="<?= e($busca) ?>">
        <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th>Código</th>
                <th>Curso</th>
                <th>Professor</th>
                <th>Modalidade</th>
                <th>Carga</th>
                <th>Início</th>
                <th style="min-width:140px">Ocupação</th>
                <th class="text-end">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($cursos as $c):
                $id = (string) $c['_id'];
                $pct = $c['vagas'] > 0 ? min(100, round($c['total_ativos'] / $c['vagas'] * 100)) : 0;
                ?>
                <tr>
                    <td><span class="badge text-bg-light border"><?= e($c['codigo']) ?></span></td>
                    <td><a href="<?= url('cursos/ver.php?id=' . $id) ?>" class="fw-semibold"><?= e($c['nome']) ?></a></td>
                    <td><?= e($c['professor']) ?></td>
                    <td><?= e($c['modalidade']) ?></td>
                    <td><?= (int) $c['carga_horaria'] ?>h</td>
                    <td><?= data_formatada($c['data_inicio']) ?></td>
                    <td>
                        <div class="small"><?= (int) $c['total_ativos'] ?> / <?= (int) $c['vagas'] ?> vagas</div>
                        <div class="progress" style="height:6px">
                            <div class="progress-bar <?= $pct >= 100 ? 'bg-danger' : 'bg-success' ?>" style="width: <?= $pct ?>%"></div>
                        </div>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="<?= url('cursos/ver.php?id=' . $id) ?>" class="btn btn-sm btn-outline-primary" title="Ver alunos"><i class="bi bi-eye"></i></a>
                        <a href="<?= url('cursos/form.php?id=' . $id) ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                        <form action="<?= url('cursos/excluir.php') ?>" method="post" class="d-inline"
                              data-confirmar="Excluir o curso <?= e($c['nome']) ?>? As <?= (int) $c['total_matriculas'] ?> matrícula(s) dele também serão removidas.">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$cursos): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Nenhum curso encontrado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
