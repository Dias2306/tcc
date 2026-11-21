<?php

// Página de uma atividade: mostra os detalhes e permite ao aluno entregar.
// Uso: entrega.php?prof=10001&num=2
// (a atividade é identificada pelo professor + número da atividade)

// Só o aluno logado entra nesta página
require '../banco/verifica_login.php';
exigirLogin('aluno');

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

$rm   = $_SESSION['rm'];
$prof = trim($_GET['prof'] ?? '');
$num  = (int) ($_GET['num'] ?? 0);

if ($prof === '' || $num === 0)
{
    header('Location: ./atividade_alun.php');
    exit;
}

// Aviso que o salvar_entrega.php deixou na sessão (aparece uma vez só)
$aviso = $_SESSION['aviso_entrega'] ?? null;
unset($_SESSION['aviso_entrega']);


// Nome e turma do aluno (aparecem no topo)
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


// A atividade (só se for da turma do aluno)
$stmt = $con->prepare(
    "SELECT a.titulo, a.descricao, a.data_publicacao, a.prazo,
            ptm.id AS id_materia, m.nome AS materia, p.nome AS professor
       FROM atividade a
       JOIN professor_turma_materia ptm ON ptm.id = a.id_professor_turma_materia
       JOIN aluno al    ON al.id_turma = ptm.id_turma AND al.rm = ?
       JOIN materia m   ON m.id_materia = ptm.id_materia
       JOIN professor p ON p.rm = a.rm_professor
      WHERE a.rm_professor = ? AND a.num_atividade = ?"
);
$stmt->bind_param('ssi', $rm, $prof, $num);
$stmt->execute();
$atividade = $stmt->get_result()->fetch_assoc();

if (!$atividade)
{
    // atividade inexistente ou de outra turma
    header('Location: ./atividade_alun.php');
    exit;
}


// A entrega deste aluno (se já enviou)
$stmt = $con->prepare(
    "SELECT data_envio, nota
       FROM entrega
      WHERE rm_professor = ? AND num_atividade = ? AND rm_aluno = ?"
);
$stmt->bind_param('sis', $prof, $num, $rm);
$stmt->execute();
$entrega = $stmt->get_result()->fetch_assoc();


// O que o aluno pode fazer agora?
$encerrada  = $atividade['prazo'] && strtotime($atividade['prazo']) < time();
$corrigida  = $entrega && $entrega['nota'] !== null;
$podeEnviar = !$encerrada && !$corrigida;
?>
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

  <!-- Detalhes da atividade -->
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

  <!-- Entrega -->
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
            action="./control/salvar_ent.php?prof=<?= urlencode($prof) ?>&num=<?= $num ?>"
            enctype="multipart/form-data">

        <!-- campo fixo: o salvar_entrega.php usa para saber que o formulário chegou inteiro -->
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