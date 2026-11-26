<?php

// Turmas do professor (as que aparecem em professor_turma_materia).
// Cada turma tem um botão que leva para turma_alunos.php?id=...

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

// Para onde o botão "Voltar" leva (ajuste para a página inicial do professor)
$paginaVoltar = './menu_professor2.html';

$rm = $_SESSION['rm'];


// Nome do professor (aparece no topo)
$stmt = $con->prepare("SELECT nome FROM professor WHERE rm = ?");
$stmt->bind_param('s', $rm);
$stmt->execute();
$professor = $stmt->get_result()->fetch_assoc();

if (!$professor)
{
    die('Professor não encontrado no banco (RM ' . h($rm) . ').');
}


// Uma linha por turma, com o curso, as matérias que ele dá nela e quantos alunos ela tem
$stmt = $con->prepare(
    "SELECT t.id_turma, t.nome, t.ano_letivo, c.nome AS curso,
            GROUP_CONCAT(m.nome ORDER BY m.nome SEPARATOR ', ') AS materias,
            (SELECT COUNT(*) FROM aluno al
              WHERE al.id_turma = t.id_turma) AS total_alunos
       FROM professor_turma_materia ptm
       JOIN turma   t ON t.id_turma   = ptm.id_turma
       JOIN curso   c ON c.codigo     = t.codigo_curso
       JOIN materia m ON m.id_materia = ptm.id_materia
      WHERE ptm.rm_professor = ?
      GROUP BY t.id_turma, t.nome, t.ano_letivo, c.nome
      ORDER BY t.nome"
);
$stmt->bind_param('s', $rm);
$stmt->execute();
$turmas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Minhas turmas</title>
<link rel="stylesheet" href="./css/entregas_prof.css">
</head>
<body>

<div class="pagina">

  <div class="cabecalho">
    <h1>Minhas turmas</h1>
    <div class="usuario">
      Prof. <?= h($professor['nome']) ?>
      <a href="<?= h($paginaVoltar) ?>">Voltar</a>
    </div>
  </div>

  <?php if (count($turmas) === 0): ?>
    <div class="vazio">Você ainda não está vinculado a nenhuma turma.</div>
  <?php endif; ?>

  <?php foreach ($turmas as $t): ?>
    <div class="caixa">
      <div class="etiquetas">
        <span class="etiqueta turma">Turma <?= h($t['nome']) ?></span>
        <span class="etiqueta"><?= h($t['curso']) ?></span>
      </div>

      <h2><?= h($t['materias']) ?></h2>

      <div class="rodape">
        <span><?= (int) $t['total_alunos'] ?> aluno(s)</span>
        <span>Ano letivo <?= h($t['ano_letivo']) ?></span>
      </div>

      <a class="botao" href="./turma_aluno.php?id=<?= (int) $t['id_turma'] ?>">Ver alunos</a>
    </div>
  <?php endforeach; ?>

</div>

</body>
</html>