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


$aviso = $_SESSION['aviso_chamada'] ?? null;
unset($_SESSION['aviso_chamada']);

$hoje = date('Y-m-d');
$dia  = (int) date('w');  

$nomesDia = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];


$stmt = $con->prepare("SELECT nome FROM professor WHERE rm = ?");
$stmt->bind_param('s', $rm);
$stmt->execute();
$professor = $stmt->get_result()->fetch_assoc();

if (!$professor)
{
    die('Professor não encontrado no banco (RM ' . h($rm) . ').');
}


$stmt = $con->prepare(
    "SELECT h.id_horario, h.materia, h.hora_inicio, h.hora_fim, h.sala,
            t.nome AS turma,
            (SELECT COUNT(*) FROM aluno al
              WHERE al.id_turma = h.id_turma) AS total_alunos,
            (SELECT COUNT(*) FROM presenca p
              WHERE p.id_horario = h.id_horario
                AND p.data_aula = ?) AS registrados,
            (SELECT COUNT(*) FROM presenca p
              WHERE p.id_horario = h.id_horario
                AND p.data_aula = ?
                AND p.presente = 0) AS faltas
       FROM horario_aula h
       JOIN turma t ON t.id_turma = h.id_turma
      WHERE h.rm_professor = ? AND h.dia_semana = ?
      ORDER BY h.hora_inicio"
);
$stmt->bind_param('sssi', $hoje, $hoje, $rm, $dia);
$stmt->execute();
$aulas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>