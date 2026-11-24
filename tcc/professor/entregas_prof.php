<?php

// Entregas de UMA atividade: o professor vê quem entregou, abre o arquivo e dá a nota.
// Uso: entregas_prof.php?num=3   (num = número da atividade deste professor)

// Só o professor logado entra nesta página
require '../banco/verifica_login.php';
exigirLogin('professor');

// garante o horário de Brasília nas comparações de prazo
date_default_timezone_set('America/Sao_Paulo');

try
{
    require '../banco/conexao.php';
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
    return $data ? date('d/m/Y H:i', strtotime($data)) : 'Sem prazo';
}

// Mostra uma mensagem clara na tela em vez de redirecionar sem explicar o motivo
function paginaErro($mensagem, $voltar)
{
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entregas</title>
<link rel="stylesheet" href="./css/entregas_prof.css">
</head>
<body>
  <div class="pagina">
    <div class="vazio">
      <?= h($mensagem) ?><br><br>
      <a class="botao claro" href="<?= h($voltar) ?>">Voltar</a>
    </div>
  </div>
</body>
</html>
<?php
    exit;
}

// Para onde o botão "Voltar" leva (ajuste para a página inicial do professor)
$paginaVoltar = './menu_professor2.html';

$rm  = $_SESSION['rm'];
$num = (int) ($_GET['num'] ?? 0);

if ($num === 0)
{
    paginaErro('Nenhuma atividade foi informada no endereço (falta o ?num=...).', $paginaVoltar);
}

// Aviso que o salvar_nota.php deixou na sessão (aparece uma vez só)
$aviso = $_SESSION['aviso_nota'] ?? null;
unset($_SESSION['aviso_nota']);


// Nome do professor (aparece no topo)
$stmt = $con->prepare("SELECT nome FROM professor WHERE rm = ?");
$stmt->bind_param('s', $rm);
$stmt->execute();
$professor = $stmt->get_result()->fetch_assoc();

if (!$professor)
{
    paginaErro('Professor não encontrado no banco (RM ' . $rm . ').', $paginaVoltar);
}


// A atividade (só se for deste professor) com a turma e a matéria dela
$stmt = $con->prepare(
    "SELECT a.titulo, a.descricao, a.data_publicacao, a.prazo,
            ptm.id_turma, t.nome AS turma, m.nome AS materia
       FROM atividade a
       JOIN professor_turma_materia ptm ON ptm.id = a.id_professor_turma_materia
       JOIN turma   t ON t.id_turma   = ptm.id_turma
       JOIN materia m ON m.id_materia = ptm.id_materia
      WHERE a.rm_professor = ? AND a.num_atividade = ?"
);
$stmt->bind_param('si', $rm, $num);
$stmt->execute();
$atividade = $stmt->get_result()->fetch_assoc();

if (!$atividade)
{
    // atividade inexistente ou de outro professor
    paginaErro('Atividade nº ' . $num . ' não encontrada para o professor de RM ' . $rm . '.', $paginaVoltar);
}


// TODOS os alunos da turma, com a entrega de cada um (se existir).
// Quem não entregou aparece com os campos da entrega vazios.
$stmt = $con->prepare(
    "SELECT al.rm, al.nome, e.id_entrega, e.arquivo, e.data_envio, e.nota
       FROM aluno al
       LEFT JOIN entrega e ON e.rm_aluno = al.rm
                          AND e.rm_professor = ?
                          AND e.num_atividade = ?
      WHERE al.id_turma = ?
      ORDER BY al.nome"
);
$stmt->bind_param('sii', $rm, $num, $atividade['id_turma']);
$stmt->execute();
$alunos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


// Resumo: quantos entregaram e quantos já foram corrigidos
$total      = count($alunos);
$entregues  = 0;
$corrigidas = 0;

foreach ($alunos as $al)
{
    if ($al['id_entrega'] !== null)
    {
        $entregues++;

        if ($al['nota'] !== null)
        {
            $corrigidas++;
        }
    }
}

// tipos de arquivo que o navegador consegue abrir (os outros só baixam)
$podeVisualizar = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'];
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

  <!-- A atividade -->
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

  <!-- Um card para cada aluno da turma -->
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

        <!-- abrir o arquivo (passa pelo baixar_ent.php, que confere o dono) -->
        <div class="arquivo">
          <?php if ($podeVer): ?>
            <a class="botao claro" href="../arquivos/baixar_ent.php?id=<?= $idEnt ?>&acao=ver" target="_blank">Visualizar</a>
          <?php endif; ?>
          <a class="botao claro" href="../arquivos/baixar_ent.php?id=<?= $idEnt ?>&acao=baixar">Baixar</a>
        </div>

        <!-- dar a nota -->
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