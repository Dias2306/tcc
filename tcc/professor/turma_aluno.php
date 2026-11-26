<?php

// Alunos de UMA turma do professor.
// Uso: turma_alunos.php?id=1   (id = turma.id_turma)

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

// Mostra uma mensagem clara na tela em vez de redirecionar sem explicar o motivo
function paginaErro($mensagem, $voltar)
{
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Alunos da turma</title>
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

$paginaVoltar = './turma_prof.php';

$rm      = $_SESSION['rm'];
$idTurma = (int) ($_GET['id'] ?? 0);

if ($idTurma === 0)
{
    paginaErro('Nenhuma turma foi informada no endereço (falta o ?id=...).', $paginaVoltar);
}


// Nome do professor (aparece no topo)
$stmt = $con->prepare("SELECT nome FROM professor WHERE rm = ?");
$stmt->bind_param('s', $rm);
$stmt->execute();
$professor = $stmt->get_result()->fetch_assoc();

if (!$professor)
{
    paginaErro('Professor não encontrado no banco (RM ' . $rm . ').', $paginaVoltar);
}


// A turma (só se o professor der aula nela)
$stmt = $con->prepare(
    "SELECT t.id_turma, t.nome, t.ano_letivo, c.nome AS curso
       FROM turma t
       JOIN curso c ON c.codigo = t.codigo_curso
      WHERE t.id_turma = ?
        AND EXISTS (SELECT 1 FROM professor_turma_materia ptm
                     WHERE ptm.id_turma = t.id_turma AND ptm.rm_professor = ?)"
);
$stmt->bind_param('is', $idTurma, $rm);
$stmt->execute();
$turma = $stmt->get_result()->fetch_assoc();

if (!$turma)
{
    // turma inexistente ou de outro professor
    paginaErro('Turma não encontrada para o professor de RM ' . $rm . '.', $paginaVoltar);
}


// As matérias que este professor dá nessa turma
$stmt = $con->prepare(
    "SELECT m.nome
       FROM professor_turma_materia ptm
       JOIN materia m ON m.id_materia = ptm.id_materia
      WHERE ptm.id_turma = ? AND ptm.rm_professor = ?
      ORDER BY m.nome"
);
$stmt->bind_param('is', $idTurma, $rm);
$stmt->execute();
$materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


// Alunos da turma (só nome e RM: o professor não precisa de CPF, endereço etc.)
$stmt = $con->prepare("SELECT rm, nome FROM aluno WHERE id_turma = ? ORDER BY nome");
$stmt->bind_param('i', $idTurma);
$stmt->execute();
$alunos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = count($alunos);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Alunos - Turma <?= h($turma['nome']) ?></title>
<link rel="stylesheet" href="./css/entregas_prof.css">
</head>
<body>

<div class="pagina">

  <div class="cabecalho">
    <h1>Alunos da turma</h1>
    <div class="usuario">
      Prof. <?= h($professor['nome']) ?>
      <a href="<?= h($paginaVoltar) ?>">Voltar</a>
    </div>
  </div>

  <!-- A turma -->
  <div class="caixa">
    <div class="etiquetas">
      <span class="etiqueta turma">Turma <?= h($turma['nome']) ?></span>
      <span class="etiqueta"><?= h($turma['curso']) ?></span>
    </div>

    <div class="rodape">
      <span>Suas matérias: <?= h(implode(', ', array_column($materias, 'nome'))) ?></span>
      <span><?= $total ?> aluno(s)</span>
    </div>
  </div>

  <?php if ($total === 0): ?>
    <div class="vazio">Esta turma ainda não tem alunos cadastrados.</div>
  <?php endif; ?>

  <!-- Um card para cada aluno da turma -->
  <?php foreach ($alunos as $al): ?>
    <div class="aluno" id="aluno-<?= h($al['rm']) ?>">
      <div class="quem">
        <strong><?= h($al['nome']) ?></strong>
        <small>RM <?= h($al['rm']) ?></small>
      </div>
    </div>
  <?php endforeach; ?>

</div>

</body>
</html>