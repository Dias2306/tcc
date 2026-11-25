<?php require './control/material.php' ?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Materiais</title>
<link rel="stylesheet" href="./css/mat_alun.css">
</head>
<body>

<div class="pagina">

  <div class="cabecalho">
    <h1>Materiais</h1>
    <div class="usuario">
      <?= h($aluno['nome']) ?> • Turma <?= h($aluno['turma']) ?>
      <a href="./atividade_alun.php">Atividades</a>
      <a href="./menu_aluno.php">Voltar</a>
    </div>
  </div>

  <?php if ($idMateria === 0): ?>


    <?php if (count($materias) === 0): ?>
      <div class="vazio">Sua turma ainda não tem matérias cadastradas.</div>
    <?php endif; ?>

    <div class="grade">
      <?php foreach ($materias as $m): ?>
        <a class="card-materia" href="./material_alun.php?materia=<?= (int) $m['id'] ?>">
          <span class="nome"><?= h($m['materia']) ?></span>
          <span class="prof">Prof. <?= h($m['professor']) ?></span>
          <span class="contagem"><?= plural((int) $m['total'], 'material', 'materiais') ?></span>
        </a>
      <?php endforeach; ?>
    </div>

  <?php else: ?>


    <a class="voltar" href="./material_alun.php">← Voltar às matérias</a>

    <h2 class="subtitulo">
      <?= h($materia['materia']) ?>
      <small>Prof. <?= h($materia['professor']) ?></small>
    </h2>

    <?php if (count($materiais) === 0): ?>
      <div class="vazio">Nenhum material postado nesta matéria ainda.</div>
    <?php endif; ?>

    <?php foreach ($materiais as $mt): ?>
      <?php
        $info = infoMaterial($mt['arquivo']);
        $id   = (int) $mt['id_material'];
      ?>

      <div class="material">
        <div class="topo">
          <h3><?= h($mt['titulo']) ?></h3>
          <span class="tipo"><?= $info['icone'] ?> <?= h($info['rotulo']) ?></span>
        </div>

        <?php if ($mt['descricao']): ?>
          <p class="descricao"><?= nl2br(h($mt['descricao'])) ?></p>
        <?php endif; ?>

        <div class="acoes">
          <?php if ($info['link']): ?>
            <a class="botao" href="<?= h($mt['arquivo']) ?>" target="_blank" rel="noopener noreferrer">Abrir link</a>
          <?php else: ?>
            <?php if ($info['ver']): ?>
              <a class="botao" href="../arquivos/baixar_mat.php?id=<?= $id ?>&acao=ver" target="_blank">Visualizar</a>
            <?php endif; ?>
            <a class="botao claro" href="../arquivos/baixar_mat.php?id=<?= $id ?>&acao=baixar">Baixar</a>
          <?php endif; ?>
        </div>

        <div class="rodape">Postado em <?= formatarData($mt['data_publicacao']) ?></div>
      </div>
    <?php endforeach; ?>

  <?php endif; ?>

</div>

</body>
</html>