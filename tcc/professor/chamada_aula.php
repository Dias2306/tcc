<?php require './control/chamada_au.php' ?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Chamada - <?= h($aula['materia']) ?> - <?= h($aula['turma']) ?></title>
<link rel="stylesheet" href="./css/entregas_prof.css">
<style>

  .opcoes { display: flex; gap: 8px; flex-wrap: wrap; }
  .opcoes label { position: relative; cursor: pointer; }
  .opcoes input { position: absolute; opacity: 0; pointer-events: none; }
  .opcoes span {
    display: inline-block;
    padding: 8px 18px;
    border-radius: 10px;
    border: 1px solid rgba(128, 128, 128, .5);
    font-weight: 600;
  }
  .opcoes input[value="P"]:checked + span { background: #16a34a; border-color: #16a34a; color: #fff; }
  .opcoes input[value="F"]:checked + span { background: #dc2626; border-color: #dc2626; color: #fff; }
  .opcoes input:focus-visible + span { outline: 2px solid #3b82f6; outline-offset: 2px; }
</style>
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


  <div class="caixa">
    <div class="etiquetas">
      <span class="etiqueta"><?= h($aula['materia']) ?></span>
      <span class="etiqueta turma">Turma <?= h($aula['turma']) ?></span>
    </div>

    <div class="rodape">
      <span><?= date('d/m/Y') ?>, <?= h(substr($aula['hora_inicio'], 0, 5)) ?> às <?= h(substr($aula['hora_fim'], 0, 5)) ?></span>
      <?php if ($aula['sala']): ?>
        <span>Sala: <?= h($aula['sala']) ?></span>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($total === 0): ?>
    <div class="vazio">Esta turma ainda não tem alunos cadastrados.</div>
  <?php else: ?>

  <form method="post" action="./chamada_aula.php?id=<?= (int) $id ?>">


    <?php foreach ($alunos as $al): ?>
      <?php
        $falta = $al['presente'] !== null && (int) $al['presente'] === 0;
      ?>
      <div class="aluno" id="aluno-<?= h($al['rm']) ?>">

        <div class="quem">
          <strong><?= h($al['nome']) ?></strong>
          <small>RM <?= h($al['rm']) ?></small>
        </div>

        <div class="opcoes">
          <label>
            <input type="radio" name="presenca[<?= h($al['rm']) ?>]" value="P" <?= $falta ? '' : 'checked' ?>>
            <span>Presente</span>
          </label>
          <label>
            <input type="radio" name="presenca[<?= h($al['rm']) ?>]" value="F" <?= $falta ? 'checked' : '' ?>>
            <span>Falta</span>
          </label>
        </div>

      </div>
    <?php endforeach; ?>

    <button class="botao" type="submit">Salvar chamada</button>

  </form>

  <?php endif; ?>

</div>

</body>
</html>