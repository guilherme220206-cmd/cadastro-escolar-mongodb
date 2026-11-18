<?php
/**
 * Funções de apoio para desenhar campos de formulário com Bootstrap
 * mostrando a mensagem de erro de validação abaixo do campo.
 */

function campo_classe(array $erros, string $nome): string
{
    return isset($erros[$nome]) ? ' is-invalid' : '';
}

function campo_erro(array $erros, string $nome): string
{
    return isset($erros[$nome]) ? '<div class="invalid-feedback">' . e($erros[$nome]) . '</div>' : '';
}

/**
 * Campo <input> completo (label + input + erro).
 */
function campo_input(string $nome, string $rotulo, array $valores, array $erros, array $attrs = []): string
{
    $tipo  = $attrs['type'] ?? 'text';
    unset($attrs['type']);
    $extra = '';
    foreach ($attrs as $k => $v) {
        $extra .= ' ' . $k . '="' . e($v) . '"';
    }
    return '<label class="form-label" for="' . e($nome) . '">' . e($rotulo) . '</label>'
        . '<input type="' . e($tipo) . '" class="form-control' . campo_classe($erros, $nome) . '" id="' . e($nome)
        . '" name="' . e($nome) . '" value="' . e($valores[$nome] ?? '') . '"' . $extra . '>'
        . campo_erro($erros, $nome);
}
