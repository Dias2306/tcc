<?php require './control/entrega.php' ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($atividade['titulo']) ?></title>
<link rel="stylesheet" href="./css/entrega.css">
</head>
<body>

<div class="pagina">

  <div class="cabecalho">
    <h1>Entrega de atividade</h1>
    <div class="usuario">
      <?= h($aluno['nome']) ?> • Turma <?= h($aluno['turma']) ?>
      <a class="botao-voltar" href="./atividade_alun.php?materia=<?= (int) $atividade['id_materia'] ?>">Voltar</a>
    </div>
  </div>

  <?php if ($aviso): ?>
    <div class="aviso <?= h($aviso['tipo']) ?>"><?= h($aviso['texto']) ?></div>
  <?php endif; ?>


  <div class="caixa">
    <span class="materia"><?= h($atividade['materia']) ?></span>
    <h2><?= h($atividade['titulo']) ?></h2>

    <?php if ($atividade['descricao']): ?>
      <p class="descricao"><?= nl2br(h($atividade['descricao'])) ?></p>
    <?php endif; ?>

    <div class="rodape">
      <span>Prof. <?= h($atividade['professor']) ?></span>
      <span>Postada em <?= formatarData($atividade['data_publicacao']) ?></span>
      <span>Prazo: <?= formatarData($atividade['prazo']) ?></span>
    </div>
  </div>


  <div class="caixa">
    <h2>Sua entrega</h2>

    <?php if ($corrigida): ?>
      <span class="situacao ok">Nota <?= number_format((float) $entrega['nota'], 2, ',', '') ?></span>
      <p class="descricao">
        Entregue em <?= formatarData($entrega['data_envio']) ?>.
        Esta atividade já foi corrigida e não aceita novo envio.
      </p>

    <?php elseif ($entrega): ?>
      <span class="situacao ok">Entregue</span>
      <p class="descricao">
        Entregue em <?= formatarData($entrega['data_envio']) ?>. Aguardando correção do professor.
        <?php if ($encerrada): ?>
          O prazo terminou e não é mais possível trocar o arquivo.
        <?php endif; ?>
      </p>

    <?php elseif ($encerrada): ?>
      <span class="situacao encerrado">Prazo encerrado</span>
      <p class="descricao">O prazo terminou e esta atividade não foi entregue.</p>

    <?php else: ?>
      <span class="situacao pendente">Pendente</span>
      <p class="descricao">Você ainda não entregou esta atividade.</p>
    <?php endif; ?>

    <?php if ($podeEnviar): ?>
      <form method="post"
            action="../arquivos/salvar_ent.php?prof=<?= urlencode($prof) ?>&num=<?= $num ?>"
            enctype="multipart/form-data">

       
        <input type="hidden" name="enviar" value="1">

        <label>
          <?= $entrega ? 'Enviar outro arquivo (substitui o anterior)' : 'Arquivo da entrega' ?>
          <input type="file" name="arquivo" required
                 accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.jpg,.jpeg,.png,.gif,.webp,.mp4,.webm">
          <span class="dica">PDF, Word, PowerPoint, imagem, vídeo ou ZIP. Máximo de 10 MB.</span>
        </label>

        <button class="botao-entrega" type="submit">
          <?= $entrega ? 'Substituir entrega' : 'Entregar atividade' ?>
        </button>
      </form>
    <?php endif; ?>
  </div>

</div>

</body>
</html>