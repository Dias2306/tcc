<?php
require '../banco/verifica_login.php';
exigirLogin('professor');

date_default_timezone_set('America/Sao_Paulo');

try
{
    require '../banco/conexao.php';
}
catch (mysqli_sql_exception $e)
{
    die('Erro ao conectar no banco.');
}

function h($texto)
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}


$paginaVoltar = './menu_professor2.html';

$rm = $_SESSION['rm'];



$stmt = $con->prepare("SELECT nome FROM professor WHERE rm = ?");
$stmt->bind_param('s', $rm);
$stmt->execute();
$professor = $stmt->get_result()->fetch_assoc();

if (!$professor)
{
    die('Professor não encontrado no banco (RM ' . h($rm) . ').');
}



$stmt = $con->prepare(
    "SELECT ptm.id, t.nome AS turma, m.nome AS materia,
            (SELECT COUNT(*) FROM aluno al
              WHERE al.id_turma = ptm.id_turma) AS total_alunos
       FROM professor_turma_materia ptm
       JOIN turma   t ON t.id_turma   = ptm.id_turma
       JOIN materia m ON m.id_materia = ptm.id_materia
      WHERE ptm.rm_professor = ?
      ORDER BY t.nome, m.nome"
);
$stmt->bind_param('s', $rm);
$stmt->execute();
$turmas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);



$stmt = $con->prepare(
    "SELECT nb.id_professor_turma_materia AS ptm, nb.bimestre, COUNT(*) AS qtd
       FROM nota_bimestre nb
       JOIN professor_turma_materia ptm ON ptm.id = nb.id_professor_turma_materia
      WHERE ptm.rm_professor = ?
      GROUP BY nb.id_professor_turma_materia, nb.bimestre"
);
$stmt->bind_param('s', $rm);
$stmt->execute();

$lancadas = [];

foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $linha)
{
    $lancadas[(int) $linha['ptm']][(int) $linha['bimestre']] = (int) $linha['qtd'];
}
?>