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


function h($texto)
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function formatarData($data)
{
    return $data ? date('d/m/Y H:i', strtotime($data)) : '';
}

// plural(1, 'material', 'materiais') -> "1 material"
function plural($n, $singular, $plural)
{
    return $n . ' ' . ($n == 1 ? $singular : $plural);
}


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


// Nome e turma do aluno logado (aparecem no topo)
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
    // a sessão existe, mas o aluno não está mais no banco
    header('Location: logout.php');
    exit;
}


// A página tem duas telas, escolhidas pelo endereço:
//   material_alun.php            -> cards das matérias
//   material_alun.php?materia=3  -> materiais daquela matéria
// (o número é o id do vínculo professor/turma/matéria)
$idMateria = (int) ($_GET['materia'] ?? 0);

$materias  = [];
$materia   = null;
$materiais = [];


if ($idMateria === 0)
{
    // TELA 1: um card para cada matéria da turma do aluno, com quantos materiais existem
    $stmt = $con->prepare(
        "SELECT ptm.id, m.nome AS materia, p.nome AS professor,
                COUNT(mt.id_material) AS total
           FROM aluno al
           JOIN professor_turma_materia ptm ON ptm.id_turma = al.id_turma
           JOIN materia m   ON m.id_materia = ptm.id_materia
           JOIN professor p ON p.rm = ptm.rm_professor
           LEFT JOIN material mt ON mt.id_professor_turma_materia = ptm.id
          WHERE al.rm = ?
          GROUP BY ptm.id, m.nome, p.nome
          ORDER BY m.nome"
    );
    $stmt->bind_param('s', $rm);
    $stmt->execute();
    $materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
else
{
    // TELA 2: os materiais de uma matéria.
    // Antes, confere se essa matéria é mesmo da turma do aluno.
    $stmt = $con->prepare(
        "SELECT m.nome AS materia, p.nome AS professor
           FROM aluno al
           JOIN professor_turma_materia ptm ON ptm.id_turma = al.id_turma
           JOIN materia m   ON m.id_materia = ptm.id_materia
           JOIN professor p ON p.rm = ptm.rm_professor
          WHERE al.rm = ? AND ptm.id = ?"
    );
    $stmt->bind_param('si', $rm, $idMateria);
    $stmt->execute();
    $materia = $stmt->get_result()->fetch_assoc();

    if (!$materia)
    {
        header('Location: ./material_alun.php');
        exit;
    }

    $stmt = $con->prepare(
        "SELECT id_material, titulo, descricao, arquivo, data_publicacao
           FROM material
          WHERE id_professor_turma_materia = ?
          ORDER BY data_publicacao DESC"
    );
    $stmt->bind_param('i', $idMateria);
    $stmt->execute();
    $materiais = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Materiais</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #f1f1f5;
    margin: 0;
    padding: 40px 20px;
  }

  .pagina {
    max-width: 720px;
    margin: 0 auto;
  }

  /* Topo: título à esquerda, nome e turma do aluno à direita */
  .cabecalho {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
  }

  h1 {
    margin: 0;
    color: #4f46e5;
  }

  .usuario {
    font-size: 14px;
    color: #555;
  }

  .usuario a {
    color: #4f46e5;
    margin-left: 8px;
  }

  .vazio {
    text-align: center;
    color: #888;
    padding: 40px 0;
  }

  /* Link "voltar" e título da matéria (tela 2) */
  .voltar {
    display: inline-block;
    margin-bottom: 12px;
    color: #4f46e5;
    text-decoration: none;
    font-size: 14px;
  }

  .subtitulo {
    margin: 0 0 16px;
    color: #222;
  }

  .subtitulo small {
    margin-left: 8px;
    font-size: 14px;
    font-weight: normal;
    color: #777;
  }

  /* Cards das matérias (tela 1): ficam lado a lado e quebram de linha */
  .grade {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
  }

  .card-materia {
    display: flex;
    flex-direction: column;
    gap: 6px;
    background: #fff;
    border-radius: 16px;
    border-top: 5px solid #4f46e5;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    text-decoration: none;
    color: #222;
    transition: transform 0.15s, box-shadow 0.15s;
  }

  .card-materia:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 18px rgba(0,0,0,0.18);
  }

  .card-materia .nome {
    font-size: 18px;
    font-weight: bold;
  }

  .card-materia .prof {
    font-size: 13px;
    color: #777;
  }

  .card-materia .contagem {
    margin-top: 6px;
    font-size: 14px;
    color: #555;
  }

  /* Cada material (tela 2) */
  .material {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    border-left: 5px solid #4f46e5;
  }

  .material .topo {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 8px;
  }

  .material h3 {
    margin: 0;
    color: #222;
  }

  .tipo {
    font-size: 13px;
    color: #666;
    white-space: nowrap;
  }

  .descricao {
    margin: 0 0 12px;
    font-size: 14px;
    line-height: 1.5;
    color: #444;
  }

  .acoes {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
  }

  .botao {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 8px;
    background: #4f46e5;
    color: #fff;
    text-decoration: none;
    font-size: 14px;
  }

  .botao.claro {
    background: #ecebff;
    color: #4f46e5;
  }

  .rodape {
    font-size: 13px;
    color: #777;
  }
</style>
</head>
<body>

<div class="pagina">

  <div class="cabecalho">
    <h1>Materiais</h1>
    <div class="usuario">
      <?= h($aluno['nome']) ?> • Turma <?= h($aluno['turma']) ?>
      <a href="./atividade_alun.php">Atividades</a>
      <a href="logout.php">Sair</a>
    </div>
  </div>

  <?php if ($idMateria === 0): ?>

    <!-- TELA 1: um card para cada matéria -->
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

    <!-- TELA 2: materiais da matéria escolhida -->
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
              <a class="botao" href="./control/baixar_mat.php?id=<?= $id ?>&acao=ver" target="_blank">Visualizar</a>
            <?php endif; ?>
            <a class="botao claro" href="./control/baixar_mat.php?id=<?= $id ?>&acao=baixar">Baixar</a>
          <?php endif; ?>
        </div>

        <div class="rodape">Postado em <?= formatarData($mt['data_publicacao']) ?></div>
      </div>
    <?php endforeach; ?>

  <?php endif; ?>

</div>

</body>
</html>