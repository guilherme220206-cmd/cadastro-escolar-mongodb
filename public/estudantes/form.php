<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../templates/campo.php';

use App\EstudanteRepository;

$repo  = new EstudanteRepository();
$id    = oid($_GET['id'] ?? null);
$erros = [];

// Valores que preenchem o formulário (achatados: endereço no mesmo nível)
$valores = [];
if ($id) {
    $estudante = $repo->buscar($id);
    if (!$estudante) {
        flash('danger', 'Estudante não encontrado.');
        redirecionar('estudantes/index.php');
    }
    $valores = $estudante + ($estudante['endereco'] ?? []);
    $valores['data_nascimento'] = data_formatada($estudante['data_nascimento'] ?? null, 'Y-m-d');
    $valores['cpf'] = formatar_cpf($estudante['cpf']);
    $valores['telefone'] = formatar_telefone($estudante['telefone']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post_com_csrf();
    $valores = $_POST;
    [$doc, $erros] = $repo->validar($_POST);

    if (!$erros) {
        try {
            if ($id) {
                $repo->atualizar($id, $doc);
                flash('success', 'Estudante atualizado com sucesso.');
            } else {
                $id = $repo->inserir($doc);
                flash('success', 'Estudante cadastrado! Agora você pode matriculá-lo em cursos.');
            }
            redirecionar('estudantes/ver.php?id=' . $id);
        } catch (Throwable $ex) {
            if (!erro_duplicado($ex)) {
                throw $ex;
            }
            $mapa = ['cpf_unico' => 'cpf', 'email_unico' => 'email', 'ra_unico' => 'ra'];
            $campo = $mapa[indice_duplicado($ex)] ?? 'nome';
            $erros[$campo] = 'Já existe um estudante com este valor.';
        }
    }
}

$titulo = $id ? 'Editar estudante' : 'Novo estudante';
$menu = 'estudantes';
require __DIR__ . '/../../templates/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('estudantes/index.php') ?>">Estudantes</a></li>
        <li class="breadcrumb-item active"><?= e($titulo) ?></li>
    </ol>
</nav>

<form method="post" novalidate>
    <?= csrf_campo() ?>

    <div class="card mb-3">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-person-vcard me-1"></i> Dados do estudante</div>
        <div class="card-body row g-3">
            <div class="col-md-8"><?= campo_input('nome', 'Nome completo *', $valores, $erros, ['maxlength' => 120]) ?></div>
            <div class="col-md-4"><?= campo_input('ra', 'RA (registro acadêmico) *', $valores, $erros, ['maxlength' => 20]) ?></div>
            <div class="col-md-4"><?= campo_input('cpf', 'CPF *', $valores, $erros, ['placeholder' => '000.000.000-00', 'maxlength' => 14, 'data-mascara' => 'cpf']) ?></div>
            <div class="col-md-4"><?= campo_input('data_nascimento', 'Data de nascimento *', $valores, $erros, ['type' => 'date']) ?></div>
            <div class="col-md-4"><?= campo_input('telefone', 'Telefone *', $valores, $erros, ['placeholder' => '(11) 99999-9999', 'maxlength' => 15, 'data-mascara' => 'telefone']) ?></div>
            <div class="col-md-12"><?= campo_input('email', 'E-mail *', $valores, $erros, ['type' => 'email', 'maxlength' => 120]) ?></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-geo-alt me-1"></i> Endereço</div>
        <div class="card-body row g-3">
            <div class="col-md-3">
                <?= campo_input('cep', 'CEP *', $valores, $erros, ['placeholder' => '00000-000', 'maxlength' => 9, 'data-mascara' => 'cep']) ?>
                <div class="form-text" id="cep-status">Preenche o endereço automaticamente.</div>
            </div>
            <div class="col-md-7"><?= campo_input('logradouro', 'Logradouro *', $valores, $erros) ?></div>
            <div class="col-md-2"><?= campo_input('numero', 'Número *', $valores, $erros) ?></div>
            <div class="col-md-4"><?= campo_input('complemento', 'Complemento', $valores, $erros) ?></div>
            <div class="col-md-3"><?= campo_input('bairro', 'Bairro *', $valores, $erros) ?></div>
            <div class="col-md-3"><?= campo_input('cidade', 'Cidade *', $valores, $erros) ?></div>
            <div class="col-md-2">
                <label class="form-label" for="estado">UF *</label>
                <select class="form-select<?= campo_classe($erros, 'estado') ?>" id="estado" name="estado">
                    <option value="">--</option>
                    <?php foreach (UFS as $uf): ?>
                        <option <?= ($valores['estado'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                    <?php endforeach; ?>
                </select>
                <?= campo_erro($erros, 'estado') ?>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
        <a href="<?= url('estudantes/index.php') ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<?php
$scriptsExtras = <<<'HTML'
<script>
// Máscaras simples
const mascaras = {
    cpf: v => v.replace(/\D/g, '').slice(0, 11)
        .replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2'),
    telefone: v => {
        v = v.replace(/\D/g, '').slice(0, 11);
        return v.length > 10 ? v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3')
                             : v.replace(/(\d{2})(\d{0,4})(\d{0,4})/, '($1) $2-$3').replace(/-$/, '');
    },
    cep: v => v.replace(/\D/g, '').slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2'),
};
document.querySelectorAll('[data-mascara]').forEach(el => {
    const aplicar = () => { if (el.value) el.value = mascaras[el.dataset.mascara](el.value); };
    el.addEventListener('input', aplicar);
    aplicar();
});

// Busca de endereço pelo CEP (ViaCEP)
const cep = document.getElementById('cep');
const cepStatus = document.getElementById('cep-status');
cep.addEventListener('blur', async () => {
    const numero = cep.value.replace(/\D/g, '');
    if (numero.length !== 8) return;
    cepStatus.textContent = 'Buscando CEP...';
    try {
        const r = await fetch(`https://viacep.com.br/ws/${numero}/json/`);
        const d = await r.json();
        if (d.erro) { cepStatus.textContent = 'CEP não encontrado.'; return; }
        document.getElementById('logradouro').value = d.logradouro || '';
        document.getElementById('bairro').value = d.bairro || '';
        document.getElementById('cidade').value = d.localidade || '';
        document.getElementById('estado').value = d.uf || '';
        document.getElementById('numero').focus();
        cepStatus.textContent = 'Endereço preenchido.';
    } catch (e) {
        cepStatus.textContent = 'Não foi possível consultar o CEP.';
    }
});
</script>
HTML;
require __DIR__ . '/../../templates/footer.php';
