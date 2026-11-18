<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\EstudanteRepository;

$busca = trim((string) ($_GET['q'] ?? ''));
$estudantes = (new EstudanteRepository())->listar($busca);

$titulo = 'Estudantes';
$menu = 'estudantes';
require __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Estudantes</h1>
    <a href="<?= url('estudantes/form.php') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Novo estudante</a>
</div>

<form class="mb-3" method="get">
    <div class="input-group">
        <input type="search" name="q" class="form-control" placeholder="Buscar por nome, RA, CPF, e-mail ou cidade" value="<?= e($busca) ?>">
        <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th>Nome</th>
                <th>RA</th>
                <th>E-mail</th>
                <th>Cidade/UF</th>
                <th class="text-center">Cursos</th>
                <th class="text-end">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($estudantes as $est): $id = (string) $est['_id']; ?>
                <tr>
                    <td><a href="<?= url('estudantes/ver.php?id=' . $id) ?>" class="fw-semibold"><?= e($est['nome']) ?></a></td>
                    <td><?= e($est['ra']) ?></td>
                    <td><?= e($est['email']) ?></td>
                    <td><?= e(($est['endereco']['cidade'] ?? '') . '/' . ($est['endereco']['estado'] ?? '')) ?></td>
                    <td class="text-center"><span class="badge text-bg-secondary"><?= (int) $est['total_cursos'] ?></span></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= url('estudantes/ver.php?id=' . $id) ?>" class="btn btn-sm btn-outline-primary" title="Ver / matricular"><i class="bi bi-eye"></i></a>
                        <a href="<?= url('estudantes/form.php?id=' . $id) ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                        <form action="<?= url('estudantes/excluir.php') ?>" method="post" class="d-inline"
                              data-confirmar="Excluir <?= e($est['nome']) ?>? As matrículas dele também serão removidas.">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$estudantes): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Nenhum estudante encontrado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
