<?php  require './control/atv_alun.php'  ?>
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

  <?php if (count($atividades) === 0): ?>
    <div class="vazio">Nenhuma atividade postada ainda.</div>
  <?php endif; ?>

  <?php foreach ($atividades as $a): ?>
    <?php $encerrada = $a['prazo'] && strtotime($a['prazo']) < time(); ?>

    <div class="atividade <?= $encerrada ? 'encerrada' : '' ?>">
      <div class="topo">
        <span class="materia"><?= h($a['materia']) ?></span>
        <?php if ($encerrada): ?>
          <span class="selo">Encerrada</span>
        <?php endif; ?>
      </div>

      <h3><?= h($a['titulo']) ?></h3>

      <?php if ($a['descricao']): ?>
        <p class="descricao"><?= nl2br(h($a['descricao'])) ?></p>
      <?php endif; ?>

      <div class="rodape">
        <span>Prof. <?= h($a['professor']) ?></span>
        <span>Postada em <?= formatarData($a['data_publicacao']) ?></span>
        <span>Prazo: <?= $a['prazo'] ? formatarData($a['prazo']) : 'Sem prazo' ?></span>
      </div>
    </div>
  <?php endforeach; ?>

</div>

</body>
</html>