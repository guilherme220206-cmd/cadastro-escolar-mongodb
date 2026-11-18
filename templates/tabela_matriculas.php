<?php
/**
 * Tabela reutilizável de matrículas.
 *
 * @var array  $listaMatriculas  resultado de MatriculaRepository::listar()
 * @var string $origem           "estudante:<id>", "curso:<id>" ou "matriculas"
 * @var bool   $mostrarEstudante
 * @var bool   $mostrarCurso
 */
$mostrarEstudante = $mostrarEstudante ?? true;
$mostrarCurso = $mostrarCurso ?? true;
?>
<div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
        <tr>
            <?php if ($mostrarEstudante): ?><th>Estudante</th><?php endif; ?>
            <?php if ($mostrarCurso): ?><th>Curso</th><?php endif; ?>
            <th>Data da matrícula</th>
            <th>Status</th>
            <th class="text-end">Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($listaMatriculas as $m): $st = STATUS_MATRICULA[$m['status']] ?? STATUS_MATRICULA['ativa']; ?>
            <tr>
                <?php if ($mostrarEstudante): ?>
                    <td>
                        <a href="<?= url('estudantes/ver.php?id=' . $m['estudante_id']) ?>"><?= e($m['estudante']['nome']) ?></a>
                        <div class="small text-muted">RA <?= e($m['estudante']['ra']) ?></div>
                    </td>
                <?php endif; ?>
                <?php if ($mostrarCurso): ?>
                    <td>
                        <a href="<?= url('cursos/ver.php?id=' . $m['curso_id']) ?>"><?= e($m['curso']['nome']) ?></a>
                        <div class="small text-muted"><?= e($m['curso']['codigo']) ?> · <?= e($m['curso']['modalidade']) ?> · <?= (int) $m['curso']['carga_horaria'] ?>h</div>
                    </td>
                <?php endif; ?>
                <td><?= data_formatada($m['data_matricula'], 'd/m/Y H:i') ?></td>
                <td><span class="badge text-bg-<?= $st['cor'] ?>"><?= $st['rotulo'] ?></span></td>
                <td class="text-end text-nowrap">
                    <form action="<?= url('matriculas/status.php') ?>" method="post" class="d-inline-flex gap-1">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id" value="<?= $m['_id'] ?>">
                        <input type="hidden" name="origem" value="<?= e($origem) ?>">
                        <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()" aria-label="Alterar status">
                            <?php foreach (STATUS_MATRICULA as $valor => $info): ?>
                                <option value="<?= $valor ?>" <?= $valor === $m['status'] ? 'selected' : '' ?>><?= $info['rotulo'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <form action="<?= url('matriculas/excluir.php') ?>" method="post" class="d-inline"
                          data-confirmar="Cancelar esta matrícula?">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id" value="<?= $m['_id'] ?>">
                        <input type="hidden" name="origem" value="<?= e($origem) ?>">
                        <button class="btn btn-sm btn-outline-danger" title="Cancelar matrícula"><i class="bi bi-x-lg"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$listaMatriculas): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma matrícula.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
