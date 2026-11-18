<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../templates/campo.php';

use App\CursoRepository;

$repo  = new CursoRepository();
$id    = oid($_GET['id'] ?? null);
$erros = [];
$valores = ['modalidade' => 'Presencial'];

if ($id) {
    $curso = $repo->buscar($id);
    if (!$curso) {
        flash('danger', 'Curso não encontrado.');
        redirecionar('cursos/index.php');
    }
    $valores = $curso;
    $valores['data_inicio'] = data_formatada($curso['data_inicio'] ?? null, 'Y-m-d');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post_com_csrf();
    $valores = $_POST;
    [$doc, $erros] = $repo->validar($_POST);

    if (!$erros) {
        try {
            if ($id) {
                $repo->atualizar($id, $doc);
                flash('success', 'Curso atualizado com sucesso.');
            } else {
                $id = $repo->inserir($doc);
                flash('success', 'Curso cadastrado com sucesso.');
            }
            redirecionar('cursos/ver.php?id=' . $id);
        } catch (Throwable $ex) {
            if (!erro_duplicado($ex)) {
                throw $ex;
            }
            $erros['codigo'] = 'Já existe um curso com este código.';
        }
    }
}

$titulo = $id ? 'Editar curso' : 'Novo curso';
$menu = 'cursos';
require __DIR__ . '/../../templates/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('cursos/index.php') ?>">Cursos</a></li>
        <li class="breadcrumb-item active"><?= e($titulo) ?></li>
    </ol>
</nav>

<form method="post" novalidate>
    <?= csrf_campo() ?>
    <div class="card mb-3">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-journal-bookmark me-1"></i> Dados do curso</div>
        <div class="card-body row g-3">
            <div class="col-md-3"><?= campo_input('codigo', 'Código *', $valores, $erros, ['placeholder' => 'ex.: PHP-101', 'maxlength' => 15, 'style' => 'text-transform:uppercase']) ?></div>
            <div class="col-md-9"><?= campo_input('nome', 'Nome do curso *', $valores, $erros, ['maxlength' => 120]) ?></div>
            <div class="col-12">
                <label class="form-label" for="descricao">Descrição *</label>
                <textarea class="form-control<?= campo_classe($erros, 'descricao') ?>" id="descricao" name="descricao" rows="3"><?= e($valores['descricao'] ?? '') ?></textarea>
                <?= campo_erro($erros, 'descricao') ?>
            </div>
            <div class="col-md-6"><?= campo_input('professor', 'Professor responsável *', $valores, $erros) ?></div>
            <div class="col-md-6">
                <label class="form-label" for="modalidade">Modalidade *</label>
                <select class="form-select<?= campo_classe($erros, 'modalidade') ?>" id="modalidade" name="modalidade">
                    <?php foreach (MODALIDADES as $m): ?>
                        <option <?= ($valores['modalidade'] ?? '') === $m ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
                <?= campo_erro($erros, 'modalidade') ?>
            </div>
            <div class="col-md-4"><?= campo_input('carga_horaria', 'Carga horária (horas) *', $valores, $erros, ['type' => 'number', 'min' => 1]) ?></div>
            <div class="col-md-4"><?= campo_input('vagas', 'Vagas *', $valores, $erros, ['type' => 'number', 'min' => 1]) ?></div>
            <div class="col-md-4"><?= campo_input('data_inicio', 'Data de início *', $valores, $erros, ['type' => 'date']) ?></div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-success"><i class="bi bi-check-lg"></i> Salvar</button>
        <a href="<?= url('cursos/index.php') ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
