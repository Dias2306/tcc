<?php require './control/nota_prof.php' ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Notas do bimestre</title>
<link rel="stylesheet" href="./css/entregas_prof.css">
<style>
  .bimestres { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
  .bimestres .botao small { display: block; font-weight: 400; opacity: .8; }
</style>
</head>
<body>

<div class="pagina">

  <div class="cabecalho">
    <h1>Notas do bimestre</h1>
    <div class="usuario">
      Prof. <?= h($professor['nome']) ?>
      <a href="<?= h($paginaVoltar) ?>">Voltar</a>
    </div>
  </div>

  <?php if (count($turmas) === 0): ?>
    <div class="vazio">Você ainda não tem turmas ou matérias vinculadas.</div>
  <?php endif; ?>

  <?php foreach ($turmas as $tm): ?>
    <?php $idPtm = (int) $tm['id']; ?>
    <div class="caixa">
      <div class="etiquetas">
        <span class="etiqueta"><?= h($tm['materia']) ?></span>
        <span class="etiqueta turma">Turma <?= h($tm['turma']) ?></span>
      </div>

      <div class="rodape">
        <span><?= (int) $tm['total_alunos'] ?> aluno(s)</span>
      </div>

      <div class="bimestres">
        <?php for ($b = 1; $b <= 4; $b++): ?>
          <a class="botao claro" href="./nota_bim.php?ptm=<?= $idPtm ?>&amp;bim=<?= $b ?>">
            <?= $b ?>º bimestre
            <small><?= (int) ($lancadas[$idPtm][$b] ?? 0) ?> de <?= (int) $tm['total_alunos'] ?> lançadas</small>
          </a>
        <?php endfor; ?>
      </div>
    </div>
  <?php endforeach; ?>

</div>

</body>
</html>