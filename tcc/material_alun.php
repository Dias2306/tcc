<?php

// Só o aluno logado entra nesta página
require './banco/verifica_login.php';
exigirLogin('aluno');

try
{
    require './banco/conexao.php';
}
catch (mysqli_sql_exception $e)
{
    die('Erro ao conectar no banco.');
}

// h() protege o texto que vem do banco antes de colocar na página
function h($texto)
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function formatarData($data)
{
    return date('d/m/Y H:i', strtotime($data));
}

// Descobre o tipo do material olhando o que está na coluna "arquivo":
// um endereço (link) ou o caminho de um arquivo.
// Retorna: rótulo, ícone, se é link e se pode ser visualizado no navegador.
function infoMaterial($arquivo)
{
    if (preg_match('#^https?://#i', $arquivo))
    {
        return ['rotulo' => 'Link', 'icone' => '🔗', 'link' => true, 'ver' => false];
    }

    $tipos = [
        'pdf'  => ['PDF',        '📄', true],
        'jpg'  => ['Imagem',     '🖼️', true],
        'jpeg' => ['Imagem',     '🖼️', true],
        'png'  => ['Imagem',     '🖼️', true],
        'gif'  => ['Imagem',     '🖼️', true],
        'webp' => ['Imagem',     '🖼️', true],
        'mp4'  => ['Vídeo',      '🎬', true],
        'webm' => ['Vídeo',      '🎬', true],
        'doc'  => ['Word',       '📝', false],
        'docx' => ['Word',       '📝', false],
        'ppt'  => ['PowerPoint', '📊', false],
        'pptx' => ['PowerPoint', '📊', false],
        'zip'  => ['ZIP',        '🗜️', false]
    ];

    $ext  = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
    $info = $tipos[$ext] ?? ['Arquivo', '📁', false];

    return ['rotulo' => $info[0], 'icone' => $info[1], 'link' => false, 'ver' => $info[2]];
}

$rm = $_SESSION['rm'];


// Nome do aluno e da turma dele (aparecem no topo)
$stmt = $con->prepare(
    "SELECT al.nome, t.nome AS turma
       FROM aluno al
       JOIN turma t ON t.id_turma = al.id_turma
      WHERE al.rm = ?"
);
$stmt->bind_param('s', $rm);
$stmt->execute();
$aluno = $stmt->get_result()->fetch_assoc();

if (!$aluno)
{
    header('Location: logout.php');
    exit;
}


// Materiais de todas as matérias da turma do aluno, do mais novo para o mais antigo
$stmt = $con->prepare(
    "SELECT mt.id_material, mt.titulo, mt.descricao, mt.arquivo, mt.data_publicacao,
            m.nome AS materia, p.nome AS professor
       FROM aluno al
       JOIN professor_turma_materia ptm ON ptm.id_turma = al.id_turma
       JOIN material mt  ON mt.id_professor_turma_materia = ptm.id
       JOIN materia m    ON m.id_materia = ptm.id_materia
       JOIN professor p  ON p.rm = ptm.rm_professor
      WHERE al.rm = ?
      ORDER BY mt.data_publicacao DESC"
);
$stmt->bind_param('s', $rm);
$stmt->execute();
$materiais = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Materiais</title>
<style>
  body { font-family: Arial, sans-serif; background: #f1f1f5; margin: 0; padding: 40px 20px; }
  .pagina { max-width: 720px; margin: 0 auto; }

  .cabecalho { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
  h1 { color: #4f46e5; margin: 0; }
  .usuario { font-size: 14px; color: #555; }
  .usuario a { color: #4f46e5; margin-left: 8px; }

  .material { background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 16px; box-shadow: 0 4px 12px rgba(0,0,0,.12); border-left: 5px solid #4f46e5; }

  .topo { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 8px; }
  .materia { font-size: 12px; font-weight: bold; color: #4f46e5; background: #ecebff; padding: 4px 10px; border-radius: 20px; }
  .tipo { font-size: 13px; color: #666; }

  .material h3 { margin: 0 0 8px; color: #222; }
  .descricao { color: #444; font-size: 14px; line-height: 1.5; margin: 0 0 12px; }

  .acoes { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
  .botao { display: inline-block; padding: 8px 16px; border-radius: 8px; background: #4f46e5; color: #fff; text-decoration: none; font-size: 14px; }
  .botao.claro { background: #ecebff; color: #4f46e5; }

  .rodape { font-size: 13px; color: #777; display: flex; flex-wrap: wrap; gap: 4px 16px; }

  .vazio { text-align: center; color: #888; padding: 40px 0; }
</style>
</head>
<body>

<div class="pagina">

  <div class="cabecalho">
    <h1>Materiais</h1>
    <div class="usuario">
      <?= h($aluno['nome']) ?> • Turma <?= h($aluno['turma']) ?>
      <a href="./atividades_aluno.php">Atividades</a>
      <a href="logout.php">Sair</a>
    </div>
  </div>

  <?php if (count($materiais) === 0): ?>
    <div class="vazio">Nenhum material postado ainda.</div>
  <?php endif; ?>

  <?php foreach ($materiais as $mt): ?>
    <?php
      $info = infoMaterial($mt['arquivo']);
      $id   = (int) $mt['id_material'];
    ?>

    <div class="material">
      <div class="topo">
        <span class="materia"><?= h($mt['materia']) ?></span>
        <span class="tipo"><?= $info['icone'] ?> <?= h($info['rotulo']) ?></span>
      </div>

      <h3><?= h($mt['titulo']) ?></h3>

      <?php if ($mt['descricao']): ?>
        <p class="descricao"><?= nl2br(h($mt['descricao'])) ?></p>
      <?php endif; ?>

      <div class="acoes">
        <?php if ($info['link']): ?>
          <a class="botao" href="<?= h($mt['arquivo']) ?>" target="_blank" rel="noopener noreferrer">Abrir link</a>
        <?php else: ?>
          <?php if ($info['ver']): ?>
            <a class="botao" href="./control/baixar_mat.php?id=<?= $id ?>&acao=ver" target="_blank">Visualizar</a>
          <?php endif; ?>
          <a class="botao claro" href="./control/baixar_mat.php?id=<?= $id ?>&acao=baixar">Baixar</a>
        <?php endif; ?>
      </div>

      <div class="rodape">
        <span>Prof. <?= h($mt['professor']) ?></span>
        <span>Postado em <?= formatarData($mt['data_publicacao']) ?></span>
      </div>
    </div>
  <?php endforeach; ?>

</div>

</body>
</html>