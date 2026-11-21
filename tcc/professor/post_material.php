<?php
require './control/material.php'
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Postar material</title>
<link rel="stylesheet" href="./css/post_mat.css">
</head>
<body>
<div class="caixa">
  <h2>Postar material</h2>

  <?php if ($aviso): ?>
    <div class="aviso <?= h($aviso['tipo']) ?>"><?= h($aviso['texto']) ?></div>
  <?php endif; ?>


  <?php if (count($opcoes) === 0): ?>
    <div class="aviso erro">Você ainda não está vinculado a nenhuma turma e matéria.</div>
  <?php else: ?>
  <form method="post" action="./control/salvar_mat.php" enctype="multipart/form-data">

    <label>Turma e matéria
      <select name="id_ptm" required>
        <option value="">Selecione...</option>
        <?php foreach ($opcoes as $op): ?>
          <option value="<?= (int) $op['id'] ?>"
            <?= ((int) ($dados['id_ptm'] ?? 0) === (int) $op['id']) ? 'selected' : '' ?>>
            <?= h($op['turma']) ?> — <?= h($op['materia']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Título
      <input type="text" name="titulo" maxlength="150" required value="<?= h($dados['titulo'] ?? '') ?>">
    </label>

    <label>Descrição
      <textarea name="descricao"><?= h($dados['descricao'] ?? '') ?></textarea>
    </label>

    
    <div class="tipo">
      <label><input type="radio" name="tipo" value="arquivo" <?= $tipoAtual === 'arquivo' ? 'checked' : '' ?>> Enviar arquivo</label>
      <label><input type="radio" name="tipo" value="link" <?= $tipoAtual === 'link' ? 'checked' : '' ?>> Link de vídeo</label>
    </div>

    <div id="bloco-arquivo">
      <label>Arquivo
        <input type="file" name="arquivo" id="campo-arquivo" required
               accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.jpg,.jpeg,.png,.gif,.webp,.mp4,.webm">
        <span class="dica">PDF, Word, PowerPoint, imagem, vídeo ou ZIP. Máximo de 10 MB.</span>
      </label>
    </div>

    <div id="bloco-link" style="display:none">
      <label>Link do vídeo
        <input type="url" name="link" id="campo-link" placeholder="https://www.youtube.com/watch?v=..." value="<?= h($dados['link'] ?? '') ?>">
        <span class="dica">Para vídeos grandes, prefira colar o link (YouTube, Google Drive etc.).</span>
      </label>
    </div>

    <button type="submit">Postar</button>

  </form>
  <?php endif; ?>
</div>

<script src="./js/post_mat.js" ></script>

</body>
</html>