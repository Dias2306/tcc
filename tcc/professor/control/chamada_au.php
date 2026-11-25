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

function paginaErro($mensagem, $voltar)
{
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Chamada</title>
<link rel="stylesheet" href="./css/entregas_prof.css">
</head>
<body>
  <div class="pagina">
    <div class="vazio">
      <?= h($mensagem) ?><br><br>
      <a class="botao claro" href="<?= h($voltar) ?>">Voltar</a>
    </div>
  </div>
</body>
</html>
<?php
    exit;
}

$paginaVoltar = './chamada_prof.php';

$rm = $_SESSION['rm'];
$id = (int) ($_GET['id'] ?? 0);

$hoje = date('Y-m-d');
$dia  = (int) date('w');   



if ($id === 0)
{
    paginaErro('Nenhuma aula foi informada no endereço (falta o ?id=...).', $paginaVoltar);
}



$stmt = $con->prepare("SELECT nome FROM professor WHERE rm = ?");
$stmt->bind_param('s', $rm);
$stmt->execute();
$professor = $stmt->get_result()->fetch_assoc();

if (!$professor)
{
    paginaErro('Professor não encontrado no banco (RM ' . $rm . ').', $paginaVoltar);
}


$stmt = $con->prepare(
    "SELECT h.id_horario, h.id_turma, h.materia, h.hora_inicio, h.hora_fim, h.sala,
            t.nome AS turma
       FROM horario_aula h
       JOIN turma t ON t.id_turma = h.id_turma
      WHERE h.id_horario = ? AND h.rm_professor = ? AND h.dia_semana = ?"
);
$stmt->bind_param('isi', $id, $rm, $dia);
$stmt->execute();
$aula = $stmt->get_result()->fetch_assoc();

if (!$aula)
{
    paginaErro('Aula não encontrada, ou ela não é uma aula de hoje deste professor.', $paginaVoltar);
}



$stmt = $con->prepare(
    "SELECT al.rm, al.nome, p.presente
       FROM aluno al
       LEFT JOIN presenca p ON p.rm_aluno = al.rm
                           AND p.id_horario = ?
                           AND p.data_aula = ?
      WHERE al.id_turma = ?
      ORDER BY al.nome"
);
$stmt->bind_param('isi', $id, $hoje, $aula['id_turma']);
$stmt->execute();
$alunos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);



if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $presentes = 0;
    $faltas    = 0;

    $con->begin_transaction();

    try
    {
        $stmt = $con->prepare(
            "INSERT INTO presenca (id_horario, data_aula, rm_aluno, presente)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE presente = VALUES(presente)"
        );


        foreach ($alunos as $al)
        {
            $marcado  = $_POST['presenca'][$al['rm']] ?? 'P';
            $presente = ($marcado === 'F') ? 0 : 1;

            $stmt->bind_param('issi', $id, $hoje, $al['rm'], $presente);
            $stmt->execute();

            if ($presente === 1)
            {
                $presentes++;
            }
            else
            {
                $faltas++;
            }
        }

        $con->commit();

        $_SESSION['aviso_chamada'] = [
            'tipo'  => 'ok',
            'texto' => 'Chamada salva: ' . $presentes . ' presente(s) e ' . $faltas . ' falta(s).'
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $con->rollback();

        $_SESSION['aviso_chamada'] = [
            'tipo'  => 'erro',
            'texto' => 'Não foi possível salvar a chamada. Tente de novo.'
        ];
    }

    header('Location: ' . $paginaVoltar);
    exit;
}

$total = count($alunos);
?>