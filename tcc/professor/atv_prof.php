<?php

// Lista as atividades do professor logado.
// Cada atividade tem um botão que leva para entregas_prof.php?num=...

require '../banco/verifica_login.php';
exigirLogin('professor');

date_default_timezone_set('America/Sao_Paulo');

try
{
    require '../banco/conexao.php';
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
    return $data ? date('d/m/Y H:i', strtotime($data)) : 'Sem prazo';
}

// Para onde o botão "Voltar" leva (ajuste para a página inicial do professor)
$paginaVoltar = './menu_professor2.html';

$rm = $_SESSION['rm'];

// Nome do professor (aparece no topo)
$stmt = $con->prepare("SELECT nome FROM professor WHERE rm = ?");
$stmt->bind_param('s', $rm);
$stmt->execute();
$professor = $stmt->get_result()->fetch_assoc();

if (!$professor)
{
    die('Professor não encontrado no banco (RM ' . h($rm) . ').');
}

// Atividades do professor, com turma, matéria e os números de entrega
$stmt = $con->prepare(
    "SELECT a.num_atividade, a.titulo, a.data_publicacao, a.prazo,
            t.nome AS turma, m.nome AS materia,
            (SELECT COUNT(*) FROM aluno al
              WHERE al.id_turma = ptm.id_turma) AS total_alunos,
            (SELECT COUNT(*) FROM entrega e
              WHERE e.rm_professor = a.rm_professor
                AND e.num_atividade = a.num_atividade) AS entregues,
            (SELECT COUNT(*) FROM entrega e
              WHERE e.rm_professor = a.rm_professor
                AND e.num_atividade = a.num_atividade
                AND e.nota IS NOT NULL) AS corrigidas
       FROM atividade a
       JOIN professor_turma_materia ptm ON ptm.id = a.id_professor_turma_materia
       JOIN turma   t ON t.id_turma   = ptm.id_turma
       JOIN materia m ON m.id_materia = ptm.id_materia
      WHERE a.rm_professor = ?
      ORDER BY a.data_publicacao DESC"
);
$stmt->bind_param('s', $rm);
$stmt->execute();
$atividades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Minhas atividades</title>
<link rel="stylesheet" href="./css/entregas_prof.css">
</head>
<body>

<div class="pagina">

  <div class="cabecalho">
    <h1>Minhas atividades</h1>
    <div class="usuario">
      Prof. <?= h($professor['nome']) ?>
      <a href="<?= h($paginaVoltar) ?>">Voltar</a>
    </div>
  </div>

  <?php if (count($atividades) === 0): ?>
    <div class="vazio">Você ainda não publicou nenhuma atividade.</div>
  <?php endif; ?>

  <?php foreach ($atividades as $at): ?>
    <div class="caixa">
      <div class="etiquetas">
        <span class="etiqueta"><?= h($at['materia']) ?></span>
        <span class="etiqueta turma">Turma <?= h($at['turma']) ?></span>
      </div>

      <h2><?= h($at['titulo']) ?></h2>

      <div class="rodape">
        <span>Postada em <?= formatarData($at['data_publicacao']) ?></span>
        <span>Prazo: <?= formatarData($at['prazo']) ?></span>
      </div>

      <div class="resumo">
        <div><strong><?= (int) $at['entregues'] ?> de <?= (int) $at['total_alunos'] ?></strong><span>entregaram</span></div>
        <div><strong><?= (int) $at['corrigidas'] ?> de <?= (int) $at['entregues'] ?></strong><span>corrigidas</span></div>
      </div>

      <!-- aqui o ?num= é passado para a página de entregas -->
      <a class="botao" href="./entregas_prof.php?num=<?= (int) $at['num_atividade'] ?>">Ver entregas</a>
    </div>
  <?php endforeach; ?>

</div>

</body>
</html>