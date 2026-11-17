<?php

// PASSO 2: só o aluno logado entra nesta página
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

    $stmt = $con->prepare(
        "SELECT titulo, descricao, data_publicacao, prazo
           FROM atividade
          WHERE id_professor_turma_materia = ?
          ORDER BY data_publicacao DESC"
    );
    $stmt->bind_param('i', $idMateria);
    $stmt->execute();
    $atividades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Atividades</title>
<link rel="stylesheet" href="./css/atv_alun.css">
</head>
<body>

<div class="pagina">

  <div class="cabecalho">
    <h1>Atividades</h1>
    <div class="usuario">
      <?= h($aluno['nome']) ?> • Turma <?= h($aluno['turma']) ?>
      <a href="logout.php">Sair</a>
    </div>
  </div>

  <?php if ($idMateria === 0): ?>

    <!-- TELA 1: um card para cada matéria -->
    <?php if (count($materias) === 0): ?>
      <div class="vazio">Sua turma ainda não tem matérias cadastradas.</div>
    <?php endif; ?>

    <div class="grade">
      <?php foreach ($materias as $m): ?>
        <a class="card-materia" href="./atividade_alun.php?materia=<?= (int) $m['id'] ?>">
          <span class="nome"><?= h($m['materia']) ?></span>
          <span class="prof">Prof. <?= h($m['professor']) ?></span>
          <span class="contagem"><?= plural((int) $m['total'], 'atividade', 'atividades') ?></span>
          <?php if ((int) $m['abertas'] > 0): ?>
            <span class="badge"><?= plural((int) $m['abertas'], 'em aberto', 'em aberto') ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>

  <?php else: ?>

    <!-- TELA 2: atividades da matéria escolhida -->
    <a class="voltar" href="./atividade_alun.php">← Voltar às matérias</a>

    <h2 class="subtitulo">
      <?= h($materia['materia']) ?>
      <small>Prof. <?= h($materia['professor']) ?></small>
    </h2>

    <?php if (count($atividades) === 0): ?>
      <div class="vazio">Nenhuma atividade postada nesta matéria ainda.</div>
    <?php endif; ?>

    <?php foreach ($atividades as $a): ?>
      <?php $encerrada = $a['prazo'] && strtotime($a['prazo']) < time(); ?>

      <div class="atividade <?= $encerrada ? 'encerrada' : '' ?>">
        <div class="topo">
          <h3><?= h($a['titulo']) ?></h3>
          <?php if ($encerrada): ?>
            <span class="selo">Encerrada</span>
          <?php endif; ?>
        </div>

        <?php if ($a['descricao']): ?>
          <p class="descricao"><?= nl2br(h($a['descricao'])) ?></p>
        <?php endif; ?>

        <div class="rodape">
          <span>Postada em <?= formatarData($a['data_publicacao']) ?></span>
          <span>Prazo: <?= formatarData($a['prazo']) ?></span>
        </div>
      </div>
    <?php endforeach; ?>

  <?php endif; ?>

</div>

</body>
</html>