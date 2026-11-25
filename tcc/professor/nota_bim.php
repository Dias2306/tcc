<?php require './control/nota_bim.php'  ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Notas do <?= $bim ?>º bimestre - <?= h($turma['materia']) ?> - <?= h($turma['turma']) ?></title>
<link rel="stylesheet" href="./css/entregas_prof.css">
<style>
  .bimestres { display: flex; gap: 8px; flex-wrap: wrap; margin: 12px 0; }


  .linha-nota { display: flex; gap: 16px; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; }
  .campo-nota { display: flex; flex-direction: column; gap: 4px; font-size: .85em; }
  .campo-nota input { width: 110px; }

  .detalhe { flex: 1 1 280px; }
  .detalhe summary { cursor: pointer; list-style: none; display: flex; flex-direction: column; gap: 4px; }
  .detalhe summary::-webkit-details-marker { display: none; }
  .detalhe summary::after { content: "Ver atividades ▾"; font-size: .8em; opacity: .7; }
  .detalhe[open] summary::after { content: "Esconder atividades ▴"; }

  .lista-ativ { list-style: none; margin: 12px 0 0; padding: 0; display: grid; gap: 8px; }
  .lista-ativ li {
    display: flex; gap: 10px; align-items: center; justify-content: space-between; flex-wrap: wrap;
    padding: 8px 10px; border-radius: 10px; border: 1px solid rgba(128, 128, 128, .3);
  }
  .lista-ativ .direita { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
</style>
</head>
<body>

<div class="pagina">

  <div class="cabecalho">
    <h1>Notas do bimestre</h1>
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
      <span class="etiqueta"><?= h($turma['materia']) ?></span>
      <span class="etiqueta turma">Turma <?= h($turma['turma']) ?></span>
    </div>

    <div class="rodape">
      <span><?= $bim ?>º bimestre</span>
      <span><?= $lancadas ?> de <?= $total ?> notas lançadas</span>
    </div>
  </div>


  <div class="bimestres">
    <?php for ($b = 1; $b <= 4; $b++): ?>
      <a class="botao <?= $b === $bim ? '' : 'claro' ?>" href="./nota_bim.php?ptm=<?= $ptm ?>&amp;bim=<?= $b ?>"><?= $b ?>º bimestre</a>
    <?php endfor; ?>
  </div>

  <?php if ($total === 0): ?>
    <div class="vazio">Esta turma ainda não tem alunos cadastrados.</div>
  <?php else: ?>

  <form method="post" action="./nota_bim.php?ptm=<?= $ptm ?>&amp;bim=<?= $bim ?>">


    <?php foreach ($alunos as $al): ?>
      <?php

        $entregouQtd = 0;
        $somaNotas   = 0;
        $qtdNotas    = 0;

        foreach ($atividades as $at)
        {
            $ent = $entregas[$al['rm']][(int) $at['num_atividade']] ?? null;

            if ($ent !== null)
            {
                $entregouQtd++;

                if ($ent['nota'] !== null)
                {
                    $somaNotas += (float) $ent['nota'];
                    $qtdNotas++;
                }
            }
        }

        $media = $qtdNotas > 0 ? $somaNotas / $qtdNotas : null;
      ?>

      <div class="aluno" id="aluno-<?= h($al['rm']) ?>">
        <div class="linha-nota">

          <details class="detalhe">
            <summary>
              <span><strong><?= h($al['nome']) ?></strong> <small>RM <?= h($al['rm']) ?></small></span>
              <small>
                <?= $entregouQtd ?> de <?= count($atividades) ?> atividade(s) entregue(s)
                <?php if ($media !== null): ?>
                  · média das atividades: <?= formatarNota($media) ?>
                <?php endif; ?>
              </small>
            </summary>

            <?php if (count($atividades) === 0): ?>
              <p class="descricao">Nenhuma atividade publicada para esta turma/matéria.</p>
            <?php else: ?>
              <ul class="lista-ativ">
                <?php foreach ($atividades as $at): ?>
                  <?php $ent = $entregas[$al['rm']][(int) $at['num_atividade']] ?? null; ?>
                  <li>
                    <span><?= h($at['titulo']) ?></span>

                    <span class="direita">
                      <?php if ($ent === null): ?>
                        <span class="status nao">Não entregou</span>
                      <?php elseif ($ent['nota'] === null): ?>
                        <span class="status aguardando">Aguardando correção</span>
                        <a class="botao claro" href="./entregas_prof.php?num=<?= (int) $at['num_atividade'] ?>#aluno-<?= h($al['rm']) ?>">Corrigir</a>
                      <?php else: ?>
                        <span class="status ok">Nota <?= formatarNota($ent['nota']) ?></span>
                        <a class="botao claro" href="./entregas_prof.php?num=<?= (int) $at['num_atividade'] ?>#aluno-<?= h($al['rm']) ?>">Abrir</a>
                      <?php endif; ?>
                    </span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </details>

          <label class="campo-nota">Nota do bimestre (0 a 10)
            <input type="number" name="nota[<?= h($al['rm']) ?>]" min="0" max="10" step="0.01" inputmode="decimal"
                   value="<?= $al['nota'] !== null ? number_format((float) $al['nota'], 2, '.', '') : '' ?>">
          </label>

        </div>
      </div>
    <?php endforeach; ?>

    <button class="botao" type="submit">Salvar notas do <?= $bim ?>º bimestre</button>

  </form>

  <?php endif; ?>

</div>

</body>
</html>