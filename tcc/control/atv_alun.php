<?php

// Só o aluno logado entra nesta página
require './banco/verifica_login.php';
exigirLogin('aluno');

try
{
    require './banco/conexao.php';
}
catch (mysqli_sql_exception $e)
{
    die('Erro ao conectar no banco.');
}

// h() protege o texto que vem do banco antes de colocar na página
function h($texto)
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function formatarData($data)
{
    return date('d/m/Y H:i', strtotime($data));
}

$rm = $_SESSION['rm'];


// Nome do aluno e da turma dele (aparecem no topo)
$stmt = $con->prepare(
    "SELECT al.nome, t.nome AS turma
       FROM aluno al
       JOIN turma t ON t.id_turma = al.id_turma
      WHERE al.rm = ?"
);
$stmt->bind_param('s', $rm);
$stmt->execute();
$aluno = $stmt->get_result()->fetch_assoc();

if (!$aluno)
{
    // a sessão existe, mas o aluno não está mais no banco
    header('Location: logout.php');
    exit;
}


// Atividades de todas as matérias da turma do aluno, da mais nova para a mais antiga
$stmt = $con->prepare(
    "SELECT a.titulo, a.descricao, a.data_publicacao, a.prazo,
            m.nome AS materia, p.nome AS professor
       FROM aluno al
       JOIN professor_turma_materia ptm ON ptm.id_turma = al.id_turma
       JOIN atividade a  ON a.id_professor_turma_materia = ptm.id
       JOIN materia m    ON m.id_materia = ptm.id_materia
       JOIN professor p  ON p.rm = a.rm_professor
      WHERE al.rm = ?
      ORDER BY a.data_publicacao DESC"
);
$stmt->bind_param('s', $rm);
$stmt->execute();
$atividades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>