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

function formatarNota($nota)
{
    return number_format((float) $nota, 2, ',', '.');
}


function paginaErro($mensagem, $voltar)
{
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Notas do bimestre</title>
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

$paginaVoltar = './notas_prof.php';

$rm  = $_SESSION['rm'];
$ptm = (int) ($_GET['ptm'] ?? 0);


$mes = (int) date('n');

if ($mes <= 4)
{
    $bimPadrao = 1;
}
elseif ($mes <= 7)
{
    $bimPadrao = 2;
}
elseif ($mes <= 9)
{
    $bimPadrao = 3;
}
else
{
    $bimPadrao = 4;
}

$bim = (int) ($_GET['bim'] ?? $bimPadrao);

if ($bim < 1 || $bim > 4)
{
    $bim = $bimPadrao;
}

if ($ptm === 0)
{
    paginaErro('Nenhuma turma/matéria foi informada no endereço (falta o ?ptm=...).', $paginaVoltar);
}


$aviso = $_SESSION['aviso_nota_bim'] ?? null;
unset($_SESSION['aviso_nota_bim']);



$stmt = $con->prepare("SELECT nome FROM professor WHERE rm = ?");
$stmt->bind_param('s', $rm);
$stmt->execute();
$professor = $stmt->get_result()->fetch_assoc();

if (!$professor)
{
    paginaErro('Professor não encontrado no banco (RM ' . $rm . ').', $paginaVoltar);
}



$stmt = $con->prepare(
    "SELECT ptm.id, ptm.id_turma, t.nome AS turma, m.nome AS materia
       FROM professor_turma_materia ptm
       JOIN turma   t ON t.id_turma   = ptm.id_turma
       JOIN materia m ON m.id_materia = ptm.id_materia
      WHERE ptm.id = ? AND ptm.rm_professor = ?"
);
$stmt->bind_param('is', $ptm, $rm);
$stmt->execute();
$turma = $stmt->get_result()->fetch_assoc();

if (!$turma)
{
    paginaErro('Turma/matéria não encontrada para o professor de RM ' . $rm . '.', $paginaVoltar);
}



$stmt = $con->prepare(
    "SELECT al.rm, al.nome, nb.nota
       FROM aluno al
       LEFT JOIN nota_bimestre nb ON nb.rm_aluno = al.rm
                                 AND nb.id_professor_turma_materia = ?
                                 AND nb.bimestre = ?
      WHERE al.id_turma = ?
      ORDER BY al.nome"
);
$stmt->bind_param('iii', $ptm, $bim, $turma['id_turma']);
$stmt->execute();
$alunos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);



$stmt = $con->prepare(
    "SELECT a.num_atividade, a.titulo
       FROM atividade a
      WHERE a.id_professor_turma_materia = ? AND a.rm_professor = ?
      ORDER BY a.data_publicacao, a.num_atividade"
);
$stmt->bind_param('is', $ptm, $rm);
$stmt->execute();
$atividades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);



$stmt = $con->prepare(
    "SELECT e.rm_aluno, e.num_atividade, e.nota
       FROM entrega e
       JOIN atividade a ON a.rm_professor  = e.rm_professor
                       AND a.num_atividade = e.num_atividade
      WHERE a.id_professor_turma_materia = ? AND a.rm_professor = ?"
);
$stmt->bind_param('is', $ptm, $rm);
$stmt->execute();

$entregas = [];

foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $linha)
{
    $entregas[$linha['rm_aluno']][(int) $linha['num_atividade']] = $linha;
}



if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $salvar    = [];   
    $apagar    = [];  
    $invalidos = 0;

    foreach ($alunos as $al)
    {
        $valor = $_POST['nota'][$al['rm']] ?? '';

        if (!is_string($valor))
        {
            $invalidos++;
            continue;
        }

        $texto = str_replace(',', '.', trim($valor));

        if ($texto === '')
        {
            $apagar[] = $al['rm'];
            continue;
        }

        if (!is_numeric($texto) || (float) $texto < 0 || (float) $texto > 10)
        {
            $invalidos++;
            continue;
        }

        $salvar[$al['rm']] = round((float) $texto, 2);
    }

    if ($invalidos > 0)
    {
        $_SESSION['aviso_nota_bim'] = [
            'tipo'  => 'erro',
            'texto' => 'Há nota inválida. Use valores de 0 a 10. Nada foi salvo.'
        ];
    }
    else
    {
        $con->begin_transaction();

        try
        {
            $grava = $con->prepare(
                "INSERT INTO nota_bimestre (id_professor_turma_materia, bimestre, rm_aluno, nota)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE nota = VALUES(nota)"
            );

            foreach ($salvar as $rmAluno => $nota)
            {
                $grava->bind_param('iisd', $ptm, $bim, $rmAluno, $nota);
                $grava->execute();
            }

            $remove = $con->prepare(
                "DELETE FROM nota_bimestre
                  WHERE id_professor_turma_materia = ? AND bimestre = ? AND rm_aluno = ?"
            );

            foreach ($apagar as $rmAluno)
            {
                $remove->bind_param('iis', $ptm, $bim, $rmAluno);
                $remove->execute();
            }

            $con->commit();

            $_SESSION['aviso_nota_bim'] = [
                'tipo'  => 'ok',
                'texto' => 'Notas do ' . $bim . 'º bimestre salvas (' . count($salvar) . ' lançada(s)).'
            ];
        }
        catch (mysqli_sql_exception $e)
        {
            $con->rollback();

            $_SESSION['aviso_nota_bim'] = [
                'tipo'  => 'erro',
                'texto' => 'Não foi possível salvar as notas. Tente de novo.'
            ];
        }
    }

    header('Location: ./nota_bim.php?ptm=' . $ptm . '&bim=' . $bim);
    exit;
}

$total      = count($alunos);
$lancadas   = 0;

foreach ($alunos as $al)
{
    if ($al['nota'] !== null)
    {
        $lancadas++;
    }
}
?>