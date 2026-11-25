<?php
require './control/entrega.php'
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entregas - <?= h($atividade['titulo']) ?></title>
<link rel="stylesheet" href="./css/entregas_prof.css">
</head>
<body>

<div class="pagina">

  <div class="cabecalho">
    <h1>Entregas</h1>
    <div class="usuario">
      Prof. <?= h($professor['nome']) ?>
      <a href="<?= h($paginaVoltar) ?>">Voltar</a>
    </div>
  </div>

  <?php if ($aviso): ?>
    <div class="aviso <?= h($aviso['tipo']) ?>"><?= h($aviso['texto']) ?></div>
  <?php endif; ?>

 
  <div class="caixa">
    <div class="etiquetas">
      <span class="etiqueta"><?= h($atividade['materia']) ?></span>
      <span class="etiqueta turma">Turma <?= h($atividade['turma']) ?></span>
    </div>

    <h2><?= h($atividade['titulo']) ?></h2>

    <?php if ($atividade['descricao']): ?>
      <p class="descricao"><?= nl2br(h($atividade['descricao'])) ?></p>
    <?php endif; ?>

    <div class="rodape">
      <span>Postada em <?= formatarData($atividade['data_publicacao']) ?></span>
      <span>Prazo: <?= formatarData($atividade['prazo']) ?></span>
    </div>

    <div class="resumo">
      <div><strong><?= $entregues ?> de <?= $total ?></strong><span>entregaram</span></div>
      <div><strong><?= $corrigidas ?> de <?= $entregues ?></strong><span>corrigidas</span></div>
    </div>
  </div>


  <?php if ($total === 0): ?>
    <div class="vazio">Esta turma ainda não tem alunos cadastrados.</div>
  <?php endif; ?>

  <?php foreach ($alunos as $al): ?>
    <?php
      $entregou = $al['id_entrega'] !== null;
      $corrigida = $entregou && $al['nota'] !== null;

      if ($corrigida)
      {
          $status = 'Corrigida';
          $classe = 'ok';
      }
      elseif ($entregou)
      {
          $status = 'Aguardando correção';
          $classe = 'aguardando';
      }
      else
      {
          $status = 'Não entregou';
          $classe = 'nao';
      }

      if ($entregou)
      {
          $ext     = strtolower(pathinfo($al['arquivo'], PATHINFO_EXTENSION));
          $podeVer = in_array($ext, $podeVisualizar, true);
          $idEnt   = (int) $al['id_entrega'];
      }
    ?>

    <div class="aluno" id="aluno-<?= h($al['rm']) ?>">

      <div class="quem">
        <strong><?= h($al['nome']) ?></strong>
        <small>RM <?= h($al['rm']) ?></small>
      </div>

      <div class="situacao">
        <span class="status <?= $classe ?>"><?= $status ?></span>
        <?php if ($entregou): ?>
          <small>Enviada em <?= formatarData($al['data_envio']) ?></small>
        <?php endif; ?>
      </div>

      <?php if ($entregou): ?>


        <div class="arquivo">
          <?php if ($podeVer): ?>
            <a class="botao claro" href="../arquivos/baixar_ent.php?id=<?= $idEnt ?>&acao=ver" target="_blank">Visualizar</a>
          <?php endif; ?>
          <a class="botao claro" href="../arquivos/baixar_ent.php?id=<?= $idEnt ?>&acao=baixar">Baixar</a>
        </div>

        <form class="nota" method="post" action="../arquivos/salvar_nota.php">
          <input type="hidden" name="num" value="<?= $num ?>">
          <input type="hidden" name="rm_aluno" value="<?= h($al['rm']) ?>">

          <label>Nota (0 a 10)
            <input type="number" name="nota" min="0" max="10" step="0.01" inputmode="decimal"
                   value="<?= $al['nota'] !== null ? number_format((float) $al['nota'], 2, '.', '') : '' ?>">
          </label>

          <button class="botao" type="submit">Salvar nota</button>
        </form>

      <?php else: ?>
        <div class="sem-entrega">Sem arquivo para corrigir</div>
      <?php endif; ?>

    </div>
  <?php endforeach; ?>

</div>

</body>
</html>