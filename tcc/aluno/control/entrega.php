<?php

require '../banco/verifica_login.php';
exigirLogin('aluno');

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

function formatarData($data)
{
    return $data ? date('d/m/Y H:i', strtotime($data)) : 'Sem prazo';
}

$rm   = $_SESSION['rm'];
$prof = trim($_GET['prof'] ?? '');
$num  = (int) ($_GET['num'] ?? 0);

if ($prof === '' || $num === 0)
{
    header('Location: ./atividade_alun.php');
    exit;
}


$aviso = $_SESSION['aviso_entrega'] ?? null;
unset($_SESSION['aviso_entrega']);



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
    header('Location: logout.php');
    exit;
}



$stmt = $con->prepare(
    "SELECT a.titulo, a.descricao, a.data_publicacao, a.prazo,
            ptm.id AS id_materia, m.nome AS materia, p.nome AS professor
       FROM atividade a
       JOIN professor_turma_materia ptm ON ptm.id = a.id_professor_turma_materia
       JOIN aluno al    ON al.id_turma = ptm.id_turma AND al.rm = ?
       JOIN materia m   ON m.id_materia = ptm.id_materia
       JOIN professor p ON p.rm = a.rm_professor
      WHERE a.rm_professor = ? AND a.num_atividade = ?"
);
$stmt->bind_param('ssi', $rm, $prof, $num);
$stmt->execute();
$atividade = $stmt->get_result()->fetch_assoc();

if (!$atividade)
{
  
    header('Location: ./atividade_alun.php');
    exit;
}


$stmt = $con->prepare(
    "SELECT data_envio, nota
       FROM entrega
      WHERE rm_professor = ? AND num_atividade = ? AND rm_aluno = ?"
);
$stmt->bind_param('sis', $prof, $num, $rm);
$stmt->execute();
$entrega = $stmt->get_result()->fetch_assoc();



$encerrada  = $atividade['prazo'] && strtotime($atividade['prazo']) < time();
$corrigida  = $entrega && $entrega['nota'] !== null;
$podeEnviar = !$encerrada && !$corrigida;
?>