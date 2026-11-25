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

function formatarData($data)
{
    return $data ? date('d/m/Y H:i', strtotime($data)) : 'Sem prazo';
}


function paginaErro($mensagem, $voltar)
{
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entregas</title>
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


$paginaVoltar = './atv_prof.php';

$rm  = $_SESSION['rm'];
$num = (int) ($_GET['num'] ?? 0);

if ($num === 0)
{
    paginaErro('Nenhuma atividade foi informada no endereço (falta o ?num=...).', $paginaVoltar);
}


$aviso = $_SESSION['aviso_nota'] ?? null;
unset($_SESSION['aviso_nota']);



$stmt = $con->prepare("SELECT nome FROM professor WHERE rm = ?");
$stmt->bind_param('s', $rm);
$stmt->execute();
$professor = $stmt->get_result()->fetch_assoc();

if (!$professor)
{
    paginaErro('Professor não encontrado no banco (RM ' . $rm . ').', $paginaVoltar);
}



$stmt = $con->prepare(
    "SELECT a.titulo, a.descricao, a.data_publicacao, a.prazo,
            ptm.id_turma, t.nome AS turma, m.nome AS materia
       FROM atividade a
       JOIN professor_turma_materia ptm ON ptm.id = a.id_professor_turma_materia
       JOIN turma   t ON t.id_turma   = ptm.id_turma
       JOIN materia m ON m.id_materia = ptm.id_materia
      WHERE a.rm_professor = ? AND a.num_atividade = ?"
);
$stmt->bind_param('si', $rm, $num);
$stmt->execute();
$atividade = $stmt->get_result()->fetch_assoc();

if (!$atividade)
{

    paginaErro('Atividade nº ' . $num . ' não encontrada para o professor de RM ' . $rm . '.', $paginaVoltar);
}


$stmt = $con->prepare(
    "SELECT al.rm, al.nome, e.id_entrega, e.arquivo, e.data_envio, e.nota
       FROM aluno al
       LEFT JOIN entrega e ON e.rm_aluno = al.rm
                          AND e.rm_professor = ?
                          AND e.num_atividade = ?
      WHERE al.id_turma = ?
      ORDER BY al.nome"
);
$stmt->bind_param('sii', $rm, $num, $atividade['id_turma']);
$stmt->execute();
$alunos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);



$total      = count($alunos);
$entregues  = 0;
$corrigidas = 0;

foreach ($alunos as $al)
{
    if ($al['id_entrega'] !== null)
    {
        $entregues++;

        if ($al['nota'] !== null)
        {
            $corrigidas++;
        }
    }
}

$podeVisualizar = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'];

?>