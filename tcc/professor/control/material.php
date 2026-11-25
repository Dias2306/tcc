<?php


require '../banco/verifica_login.php';
exigirLogin('professor');

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

$rm = $_SESSION['rm'];


$aviso = $_SESSION['aviso_material'] ?? null;
$dados = $_SESSION['dados_material'] ?? [];
unset($_SESSION['aviso_material'], $_SESSION['dados_material']);

$tipoAtual = (($dados['tipo'] ?? 'arquivo') === 'link') ? 'link' : 'arquivo';


$stmt = $con->prepare(
    "SELECT ptm.id, t.nome AS turma, m.nome AS materia
       FROM professor_turma_materia ptm
       JOIN turma   t ON t.id_turma   = ptm.id_turma
       JOIN materia m ON m.id_materia = ptm.id_materia
      WHERE ptm.rm_professor = ?
      ORDER BY t.nome, m.nome"
);
$stmt->bind_param('s', $rm);
$stmt->execute();
$opcoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>