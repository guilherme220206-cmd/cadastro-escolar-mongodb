<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\EstudanteRepository;
use App\MatriculaRepository;

$id = oid($_GET['id'] ?? null);
$estudante = $id ? (new EstudanteRepository())->buscar($id) : null;
if (!$estudante) {
    flash('danger', 'Estudante não encontrado.');
    redirecionar('estudantes/index.php');
}

$repoMatriculas  = new MatriculaRepository();
$listaMatriculas = $repoMatriculas->porEstudante($id);
$cursosLivres    = $repoMatriculas->cursosDisponiveis($id);
$end             = $estudante['endereco'] ?? [];
$origem          = 'estudante:' . $id;

$titulo = $estudante['nome'];
$menu = 'estudantes';
require __DIR__ . '/../../templates/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('estudantes/index.php') ?>">Estudantes</a></li>
        <li class="breadcrumb-item active"><?= e($estudante['nome']) ?></li>
    </ol>
</nav>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e($estudante['nome']) ?> <small class="text-muted fs-6">RA <?= e($estudante['ra']) ?></small></h1>
    <a href="<?= url('estudantes/form.php?id=' . $id) ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i> Editar</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person-vcard me-1"></i> Dados pessoais</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">CPF</dt><dd class="col-7"><?= e(formatar_cpf($estudante['cpf'])) ?></dd>
                    <dt class="col-5">Nascimento</dt><dd class="col-7"><?= data_formatada($estudante['data_nascimento']) ?></dd>
                    <dt class="col-5">E-mail</dt><dd class="col-7"><?= e($estudante['email']) ?></dd>
                    <dt class="col-5">Telefone</dt><dd class="col-7 mb-0"><?= e(formatar_telefone($estudante['telefone'])) ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-geo-alt me-1"></i> Endereço</div>
            <div class="card-body">
                <p class="mb-1"><?= e($end['logradouro'] ?? '') ?>, <?= e($end['numero'] ?? '') ?>
                    <?= !empty($end['complemento']) ? ' — ' . e($end['complemento']) : '' ?></p>
                <p class="mb-1"><?= e($end['bairro'] ?? '') ?> · <?= e($end['cidade'] ?? '') ?>/<?= e($end['estado'] ?? '') ?></p>
                <p class="mb-0 text-muted">CEP <?= e(preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', $end['cep'] ?? '')) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-journal-bookmark me-1"></i> Cursos matriculados (<?= count($listaMatriculas) ?>)</span>
        <?php if ($cursosLivres): ?>
            <form action="<?= url('matriculas/salvar.php') ?>" method="post" class="d-flex gap-2">
                <?= csrf_campo() ?>
                <input type="hidden" name="estudante_id" value="<?= $id ?>">
                <input type="hidden" name="origem" value="<?= e($origem) ?>">
                <select name="curso_id" class="form-select form-select-sm" required aria-label="Curso">
                    <option value="">Selecione um curso...</option>
                    <?php foreach ($cursosLivres as $c): ?>
                        <option value="<?= $c['_id'] ?>"><?= e($c['codigo'] . ' — ' . $c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> Matricular</button>
            </form>
        <?php endif; ?>
    </div>
    <?php
    $mostrarEstudante = false;
    require __DIR__ . '/../../templates/tabela_matriculas.php';
    ?>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
