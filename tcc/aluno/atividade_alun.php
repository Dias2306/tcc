<?php
require './control/atv_alun.php'
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Atividades</title>
<link rel="stylesheet" href="./css/atividade.css">
</head>
<body>

<div class="pagina">

  <div class="cabecalho">
    <h1>Atividades</h1>
    <div class="usuario">
      <?= h($aluno['nome']) ?> • Turma <?= h($aluno['turma']) ?>
      <a href="./menu_aluno.php">Voltar</a>
    </div>
  </div>

  <?php if ($idMateria === 0): ?>


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


    <a class="voltar" href="./atividade_alun.php">← Voltar às matérias</a>

    <h2 class="subtitulo">
      <?= h($materia['materia']) ?>
      <small>Prof. <?= h($materia['professor']) ?></small>
    </h2>

    <?php if (count($atividades) === 0): ?>
      <div class="vazio">Nenhuma atividade postada nesta matéria ainda.</div>
    <?php endif; ?>

    <?php foreach ($atividades as $a): ?>
      <?php
        $encerrada = $a['prazo'] && strtotime($a['prazo']) < time();

        
        $entregue = $a['id_entrega'] !== null;

        if ($entregue)
        {
            $status = ($a['nota'] !== null)
                ? 'Nota ' . number_format((float) $a['nota'], 2, ',', '')
                : 'Entregue';
            $classe = 'ok';
            $botao  = 'Ver entrega';
            $estilo = 'secundario';
        }
        elseif ($encerrada)
        {
            $status = 'Prazo encerrado';
            $classe = 'encerrado';
            $botao  = 'Ver atividade';
            $estilo = 'secundario';
        }
        else
        {
            $status = 'Pendente';
            $classe = 'pendente';
            $botao  = 'Entregar';
            $estilo = '';
        }
      ?>

      <div class="atividade <?= $encerrada ? 'encerrada' : '' ?>">
        <div class="topo">
          <h3><?= h($a['titulo']) ?></h3>
      
          <span class="status <?= $classe ?>"><?= h($status) ?></span>
        </div>

        <?php if ($a['descricao']): ?>
          <p class="descricao"><?= nl2br(h($a['descricao'])) ?></p>
        <?php endif; ?>

        <div class="rodape">
          <span>Postada em <?= formatarData($a['data_publicacao']) ?></span>
          <span>Prazo: <?= formatarData($a['prazo']) ?></span>
        </div>

        <div class="acoes">
          <a class="botao-entrega <?= $estilo ?>"
             href="./entrega_alun.php?prof=<?= urlencode($a['rm_professor']) ?>&num=<?= (int) $a['num_atividade'] ?>">
            <?= h($botao) ?>
          </a>
        </div>
      </div>
    <?php endforeach; ?>

  <?php endif; ?>

</div>

</body>
</html>