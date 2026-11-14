<?php 
require './control/post.php'
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Postar atividade</title>
<link rel="stylesheet" href="./css/post.css">
</head>
<body>

<button><a href="login.html">voltar</a> </button>
<div class="caixa">
  <h2>Postar atividade</h2>

  <?php if ($mensagem): ?>
    <div class="aviso ok"><?= h($mensagem) ?></div>
  <?php endif; ?>
  <?php if ($erro): ?>
    <div class="aviso erro"><?= h($erro) ?></div>
  <?php endif; ?>

  <?php if (count($opcoes) === 0): ?>
    <div class="aviso erro">Você ainda não está vinculado a nenhuma turma e matéria.</div>
  <?php else: ?>
  <form method="post">

    <label>Turma e matéria
      <select name="id_ptm" required>
        <option value="">Selecione...</option>
        <?php foreach ($opcoes as $op): ?>
          <option value="<?= (int) $op['id'] ?>"
            <?= ((int) ($_POST['id_ptm'] ?? 0) === (int) $op['id']) ? 'selected' : '' ?>>
            <?= h($op['turma']) ?> — <?= h($op['materia']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Título
      <input name="titulo" maxlength="150" required value="<?= h($_POST['titulo'] ?? '') ?>">
    </label>

    <label>Descrição
      <textarea name="descricao"><?= h($_POST['descricao'] ?? '') ?></textarea>
    </label>

    <label>Prazo de entrega (opcional)
      <input name="prazo" type="datetime-local" value="<?= h($_POST['prazo'] ?? '') ?>">
    </label>

    <button type="submit">Postar</button>

  </form>
  <?php endif; ?>
</div>

</div>

</body>
</html>