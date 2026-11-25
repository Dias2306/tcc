<?php require './control/chamada_prof.php' ?>


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

  <div class="cabecalho">
    <h1>Chamada</h1>
    <div class="usuario">
      Prof. <?= h($professor['nome']) ?>
      <a href="<?= h($paginaVoltar) ?>">Voltar</a>
    </div>
  </div>

  <?php if ($aviso): ?>
    <div class="aviso <?= h($aviso['tipo']) ?>"><?= h($aviso['texto']) ?></div>
  <?php endif; ?>

  <p class="descricao">Aulas de hoje, <?= h($nomesDia[$dia]) ?>, <?= date('d/m/Y') ?></p>

  <?php if (count($aulas) === 0): ?>
    <div class="vazio">Você não tem aulas cadastradas para hoje.</div>
  <?php endif; ?>

  <?php foreach ($aulas as $au): ?>
    <?php
      $feita = (int) $au['registrados'] > 0;
    ?>
    <div class="caixa">
      <div class="etiquetas">
        <span class="etiqueta"><?= h($au['materia']) ?></span>
        <span class="etiqueta turma">Turma <?= h($au['turma']) ?></span>
      </div>

      <div class="rodape">
        <span><?= h(substr($au['hora_inicio'], 0, 5)) ?> às <?= h(substr($au['hora_fim'], 0, 5)) ?></span>
        <?php if ($au['sala']): ?>
          <span>Sala: <?= h($au['sala']) ?></span>
        <?php endif; ?>
      </div>

      <div class="situacao">
        <?php if ($feita): ?>
          <span class="status ok">Chamada feita</span>
          <small><?= (int) $au['faltas'] ?> falta(s) de <?= (int) $au['total_alunos'] ?> aluno(s)</small>
        <?php else: ?>
          <span class="status aguardando">Chamada pendente</span>
        <?php endif; ?>
      </div>

      <a class="botao" href="./chamada_aula.php?id=<?= (int) $au['id_horario'] ?>">
        <?= $feita ? 'Ver / editar chamada' : 'Fazer chamada' ?>
      </a>
    </div>
  <?php endforeach; ?>

</div>

</body>
</html>