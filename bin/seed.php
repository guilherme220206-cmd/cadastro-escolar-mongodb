<?php

declare(strict_types=1);

/*
 * Popula o banco com dados de exemplo.
 *   php bin/seed.php           -> insere exemplos
 *   php bin/seed.php --limpar  -> apaga tudo antes de inserir
 */

require __DIR__ . '/../src/bootstrap.php';

use App\CursoRepository;
use App\Database;
use App\EstudanteRepository;
use App\MatriculaRepository;

$db = Database::get();

if (in_array('--limpar', $argv, true)) {
    foreach (['matriculas', 'estudantes', 'cursos'] as $col) {
        $db->selectCollection($col)->deleteMany([]);
    }
    echo "Coleções limpas.\n";
}

/** Gera um CPF válido (apenas para dados fictícios). */
function gerar_cpf(int $semente): string
{
    mt_srand($semente);
    $n = [];
    for ($i = 0; $i < 9; $i++) {
        $n[] = mt_rand(0, 9);
    }
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += $n[$i] * (($t + 1) - $i);
        }
        $n[] = ((10 * $soma) % 11) % 10;
    }
    return implode('', $n);
}

$estudantesRepo = new EstudanteRepository();
$cursosRepo     = new CursoRepository();
$matriculasRepo = new MatriculaRepository();

$estudantes = [
    ['Ana Beatriz Souza',   '2000-03-14', 'Rua das Flores', '120', 'Centro',          'São Paulo',      'SP', '01001000'],
    ['Bruno Henrique Lima', '1999-07-22', 'Av. Brasil',     '455', 'Jardim América',  'Rio de Janeiro', 'RJ', '20040002'],
    ['Carla Mendes',        '2001-11-02', 'Rua XV de Novembro', '89', 'Centro',       'Curitiba',       'PR', '80020310'],
    ['Diego Fernandes',     '2002-01-30', 'Rua da Bahia',   '1000', 'Lourdes',        'Belo Horizonte', 'MG', '30160011'],
    ['Eduarda Rocha',       '1998-05-17', 'Av. Sete de Setembro', '210', 'Vitória',   'Salvador',       'BA', '40060001'],
    ['Felipe Araújo',       '2000-09-09', 'Rua Padre Chagas', '33', 'Moinhos de Vento', 'Porto Alegre', 'RS', '90570080'],
];

$idsEstudantes = [];
foreach ($estudantes as $i => [$nome, $nasc, $rua, $num, $bairro, $cidade, $uf, $cep]) {
    $usuario = strtolower(strtok(iconv('UTF-8', 'ASCII//TRANSLIT', $nome), ' '));
    [$doc, $erros] = $estudantesRepo->validar([
        'nome' => $nome, 'ra' => sprintf('RA2026%03d', $i + 1), 'cpf' => gerar_cpf($i + 100),
        'email' => "$usuario" . ($i + 1) . '@exemplo.com', 'telefone' => '1199999' . sprintf('%04d', $i),
        'data_nascimento' => $nasc, 'cep' => $cep, 'logradouro' => $rua, 'numero' => $num,
        'complemento' => '', 'bairro' => $bairro, 'cidade' => $cidade, 'estado' => $uf,
    ]);
    if ($erros) {
        echo "Erro em $nome: " . implode(', ', $erros) . "\n";
        continue;
    }
    try {
        $idsEstudantes[] = $estudantesRepo->inserir($doc);
    } catch (Throwable $e) {
        echo "Ignorado (já existe): $nome\n";
    }
}

$cursos = [
    ['PHP-101', 'PHP para Web',           'Fundamentos de PHP, formulários, sessões e acesso a banco de dados.', 'Prof. Marcos Silva',  'Presencial', 60, 30, '2027-02-01'],
    ['MDB-201', 'MongoDB na Prática',     'Modelagem de documentos, índices, agregações e relacionamentos.',      'Profa. Júlia Costa',  'EAD',        40, 50, '2027-02-15'],
    ['JS-110',  'JavaScript Moderno',     'ES6+, DOM, fetch, módulos e boas práticas no front-end.',              'Prof. Renato Alves',  'Híbrido',    48, 25, '2027-03-01'],
    ['UX-100',  'Design de Interfaces',   'Princípios de UX/UI, prototipação e acessibilidade.',                  'Profa. Camila Torres','EAD',        30, 40, '2027-03-10'],
    ['BD-150',  'Banco de Dados Relacional', 'SQL, normalização, joins e comparação com NoSQL.',                  'Prof. André Ribeiro', 'Presencial', 60, 3,  '2027-02-08'],
];

$idsCursos = [];
foreach ($cursos as [$codigo, $nome, $desc, $prof, $mod, $ch, $vagas, $inicio]) {
    [$doc, $erros] = $cursosRepo->validar([
        'codigo' => $codigo, 'nome' => $nome, 'descricao' => $desc, 'professor' => $prof,
        'modalidade' => $mod, 'carga_horaria' => $ch, 'vagas' => $vagas, 'data_inicio' => $inicio,
    ]);
    if ($erros) {
        echo "Erro em $nome: " . implode(', ', $erros) . "\n";
        continue;
    }
    try {
        $idsCursos[] = $cursosRepo->inserir($doc);
    } catch (Throwable $e) {
        echo "Ignorado (já existe): $nome\n";
    }
}

// Matrículas N:N — cada estudante em vários cursos, cada curso com vários estudantes
$total = 0;
foreach ($idsEstudantes as $i => $estId) {
    foreach ($idsCursos as $j => $cursoId) {
        if (($i + $j) % 2 === 0 && $matriculasRepo->matricular($estId, $cursoId) === null) {
            $total++;
        }
    }
}

printf("Inseridos: %d estudantes, %d cursos, %d matrículas.\n", count($idsEstudantes), count($idsCursos), $total);
