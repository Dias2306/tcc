<?php
// PASSO 2: só o aluno logado entra nesta página
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

// h() protege o texto que vem do banco antes de colocar na página
function h($texto)
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function formatarData($data)
{
    return $data ? date('d/m/Y H:i', strtotime($data)) : 'Sem prazo';
}

// plural(1, 'atividade', 'atividades') -> "1 atividade"
function plural($n, $singular, $plural)
{
    return $n . ' ' . ($n == 1 ? $singular : $plural);
}

$rm = $_SESSION['rm'];


// Nome e turma do aluno logado (aparecem no topo)
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


// PASSO 4: a página tem duas telas, escolhidas pelo endereço:
//   atividade_alun.php            -> cards das matérias
//   atividade_alun.php?materia=3  -> atividades daquela matéria
// (o número é o id do vínculo professor/turma/matéria)
$idMateria = (int) ($_GET['materia'] ?? 0);

$materias   = [];
$materia    = null;
$atividades = [];


if ($idMateria === 0)
{
    // TELA 1: um card para cada matéria da turma do aluno,
    // com o total de atividades e quantas ainda estão com o prazo aberto
    $agora = date('Y-m-d H:i:s');

    $stmt = $con->prepare(
        "SELECT ptm.id, m.nome AS materia, p.nome AS professor,
                COUNT(a.num_atividade) AS total,
                SUM(CASE WHEN a.num_atividade IS NOT NULL
                          AND (a.prazo IS NULL OR a.prazo >= ?)
                         THEN 1 ELSE 0 END) AS abertas
           FROM aluno al
           JOIN professor_turma_materia ptm ON ptm.id_turma = al.id_turma
           JOIN materia m   ON m.id_materia = ptm.id_materia
           JOIN professor p ON p.rm = ptm.rm_professor
           LEFT JOIN atividade a ON a.id_professor_turma_materia = ptm.id
          WHERE al.rm = ?
          GROUP BY ptm.id, m.nome, p.nome
          ORDER BY m.nome"
    );
    $stmt->bind_param('ss', $agora, $rm);
    $stmt->execute();
    $materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
else
{
    // TELA 2: as atividades de uma matéria.
    // Antes, confere se essa matéria é mesmo da turma do aluno.
    $stmt = $con->prepare(
        "SELECT m.nome AS materia, p.nome AS professor
           FROM aluno al
           JOIN professor_turma_materia ptm ON ptm.id_turma = al.id_turma
           JOIN materia m   ON m.id_materia = ptm.id_materia
           JOIN professor p ON p.rm = ptm.rm_professor
          WHERE al.rm = ? AND ptm.id = ?"
    );
    $stmt->bind_param('si', $rm, $idMateria);
    $stmt->execute();
    $materia = $stmt->get_result()->fetch_assoc();

    if (!$materia)
    {
        header('Location: ./atividade_alun.php');
        exit;
    }

    // ENTREGA (passo 1): agora a consulta traz também o RM do professor e o número
    // da atividade (que identificam a atividade) e a entrega deste aluno, se existir
    $stmt = $con->prepare(
        "SELECT a.rm_professor, a.num_atividade, a.titulo, a.descricao,
                a.data_publicacao, a.prazo, e.id_entrega, e.nota
           FROM atividade a
           LEFT JOIN entrega e ON e.rm_professor = a.rm_professor
                              AND e.num_atividade = a.num_atividade
                              AND e.rm_aluno = ?
          WHERE a.id_professor_turma_materia = ?
          ORDER BY a.data_publicacao DESC"
    );
    $stmt->bind_param('si', $rm, $idMateria);
    $stmt->execute();
    $atividades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>