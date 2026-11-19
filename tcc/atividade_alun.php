
<?php

require './banco/verifica_login.php';
exigirLogin('aluno');

try {
    require './banco/conexao.php';
} catch (mysqli_sql_exception $e) {
    die('Erro ao conectar no banco.');
}

function h($texto) {
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function formatarData($data) {
    return $data ? date('d/m/Y H:i', strtotime($data)) : 'Sem prazo';
}

function plural($n, $singular, $plural) {
    return $n . ' ' . ($n == 1 ? $singular : $plural);
}

$rm = $_SESSION['rm'];

$mensagem = '';
$tipoMensagem = '';

/* DADOS DO ALUNO */

$stmt = $con->prepare(
    "SELECT al.nome, t.nome AS turma
       FROM aluno al
       JOIN turma t ON t.id_turma = al.id_turma
      WHERE al.rm = ?"
);

$stmt->bind_param('s', $rm);
$stmt->execute();

$aluno = $stmt->get_result()->fetch_assoc();

if (!$aluno) {
    header('Location: logout.php');
    exit;
}

/* IDENTIFICA A MATÉRIA */

$idMateria = (int) ($_GET['materia'] ?? 0);

$materias = [];
$materia = null;
$atividades = [];


/* PROCESSAMENTO DA ENTREGA */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entregar'])) {

    $idAtividade = (int) ($_POST['id_atividade'] ?? 0);

    // Confere se a atividade pertence a uma matéria da turma do aluno.
    $stmt = $con->prepare(
        "SELECT a.num_atividade, a.prazo
           FROM atividade a
           JOIN professor_turma_materia ptm
             ON ptm.id = a.id_professor_turma_materia
           JOIN aluno al
             ON al.id_turma = ptm.id_turma
          WHERE a.num_atividade = ?
            AND al.rm = ?"
    );

    $stmt->bind_param('is', $idAtividade, $rm);
    $stmt->execute();

    $atividadeEntrega = $stmt->get_result()->fetch_assoc();

    if (!$atividadeEntrega) {

        $mensagem = 'Atividade não encontrada ou não pertence à sua turma.';
        $tipoMensagem = 'erro';

    } elseif (
        $atividadeEntrega['prazo'] !== null &&
        strtotime($atividadeEntrega['prazo']) < time()
    ) {

        $mensagem = 'O prazo desta atividade já terminou.';
        $tipoMensagem = 'erro';

    } elseif (
        !isset($_FILES['arquivo']) ||
        $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK
    ) {

        $mensagem = 'Selecione um arquivo válido para entregar.';
        $tipoMensagem = 'erro';

    } else {

        $arquivo = $_FILES['arquivo'];

        // Limite de 10 MB.
        $limite = 10 * 1024 * 1024;

        if ($arquivo['size'] > $limite) {

            $mensagem = 'O arquivo deve ter no máximo 10 MB.';
            $tipoMensagem = 'erro';

        } else {

            $extensao = strtolower(
                pathinfo($arquivo['name'], PATHINFO_EXTENSION)
            );

            // Tipos de arquivo permitidos.
            $permitidos = [
                'pdf', 'doc', 'docx',
                'ppt', 'pptx',
                'jpg', 'jpeg', 'png',
                'txt', 'zip'
            ];

            if (!in_array($extensao, $permitidos, true)) {

                $mensagem = 'Formato de arquivo não permitido.';
                $tipoMensagem = 'erro';

            } else {

                // Verifica se o aluno já entregou esta atividade.
                $stmt = $con->prepare(
                    "SELECT id_entrega
                       FROM entrega_atividade
                      WHERE num_atividade = ?
                        AND rm_aluno = ?"
                );

                $stmt->bind_param('is', $idAtividade, $rm);
                $stmt->execute();

                $entregaExistente = $stmt->get_result()->fetch_assoc();

                if ($entregaExistente) {

                    $mensagem = 'Você já entregou esta atividade.';
                    $tipoMensagem = 'erro';

                } else {

                    $pasta = __DIR__ . '/uploads/entregas/';

                    if (!is_dir($pasta)) {
                        mkdir($pasta, 0755, true);
                    }

                    // Nome aleatório para evitar colisões e exposição do nome original.
                    $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;

                    $caminho = $pasta . $nomeArquivo;

                    if (move_uploaded_file($arquivo['tmp_name'], $caminho)) {

                        $stmt = $con->prepare(
                            "INSERT INTO entrega_atividade
                                (num_atividade, rm_aluno, arquivo)
                             VALUES (?, ?, ?)"
                        );

                        $stmt->bind_param(
                            'iss',
                            $idAtividade,
                            $rm,
                            $nomeArquivo
                        );

                        if ($stmt->execute()) {

                            $mensagem = 'Atividade entregue com sucesso!';
                            $tipoMensagem = 'sucesso';

                        } else {

                            unlink($caminho);

                            $mensagem = 'Não foi possível registrar sua entrega.';
                            $tipoMensagem = 'erro';
                        }

                    } else {

                        $mensagem = 'Erro ao salvar o arquivo no servidor.';
                        $tipoMensagem = 'erro';
                    }
                }
            }
        }
    }

    // Evita reenviar o formulário ao atualizar a página.
    header(
        'Location: atividade_alun.php?materia=' .
        (int) ($_GET['materia'] ?? 0) .
        '&mensagem=' . urlencode($mensagem) .
        '&tipo=' . urlencode($tipoMensagem)
    );

    exit;
}


/* MENSAGENS */

$mensagem = $_GET['mensagem'] ?? $mensagem;
$tipoMensagem = $_GET['tipo'] ?? $tipoMensagem;


/* TELA DAS MATÉRIAS */

if ($idMateria === 0) {

    $agora = date('Y-m-d H:i:s');

    $stmt = $con->prepare(
        "SELECT ptm.id,
                m.nome AS materia,
                p.nome AS professor,
                COUNT(a.num_atividade) AS total,
                SUM(
                    CASE
                        WHEN a.num_atividade IS NOT NULL
                        AND (a.prazo IS NULL OR a.prazo >= ?)
                        THEN 1 ELSE 0
                    END
                ) AS abertas

           FROM aluno al

           JOIN professor_turma_materia ptm
             ON ptm.id_turma = al.id_turma

           JOIN materia m
             ON m.id_materia = ptm.id_materia

           JOIN professor p
             ON p.rm = ptm.rm_professor

           LEFT JOIN atividade a
             ON a.id_professor_turma_materia = ptm.id

          WHERE al.rm = ?

          GROUP BY ptm.id, m.nome, p.nome

          ORDER BY m.nome"
    );

    $stmt->bind_param('ss', $agora, $rm);
    $stmt->execute();

    $materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} else {

    /* CONFERE A MATÉRIA */

    $stmt = $con->prepare(
        "SELECT m.nome AS materia,
                p.nome AS professor

           FROM aluno al

           JOIN professor_turma_materia ptm
             ON ptm.id_turma = al.id_turma

           JOIN materia m
             ON m.id_materia = ptm.id_materia

           JOIN professor p
             ON p.rm = ptm.rm_professor

          WHERE al.rm = ?
            AND ptm.id = ?"
    );

    $stmt->bind_param('si', $rm, $idMateria);
    $stmt->execute();

    $materia = $stmt->get_result()->fetch_assoc();

    if (!$materia) {
        header('Location: atividade_alun.php');
        exit;
    }

    /* BUSCA AS ATIVIDADES E AS ENTREGAS DO ALUNO */

    $stmt = $con->prepare(
        "SELECT a.num_atividade,
                a.titulo,
                a.descricao,
                a.data_publicacao,
                a.prazo,
                e.id_entrega,
                e.data_entrega

           FROM atividade a

           LEFT JOIN entrega_atividade e
             ON e.num_atividade = a.num_atividade
            AND e.rm_aluno = ?

          WHERE a.id_professor_turma_materia = ?

          ORDER BY a.data_publicacao DESC"
    );

    $stmt->bind_param('si', $rm, $idMateria);
    $stmt->execute();

    $atividades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Atividades</title>

<link rel="stylesheet" href="./css/atv_alun.css">

</head>

<body>

<div class="pagina">

    <div class="cabecalho">

        <h1>Atividades</h1>

        <div class="usuario">

            <?= h($aluno['nome']) ?>
            • Turma <?= h($aluno['turma']) ?>

            <a href="./menu_aluno.php">Voltar</a>

        </div>

    </div>


    <?php if ($mensagem): ?>

        <div class="mensagem <?= h($tipoMensagem) ?>">

            <?= h($mensagem) ?>

        </div>

    <?php endif; ?>


    <?php if ($idMateria === 0): ?>

        <!-- TELA DAS MATÉRIAS -->

        <?php if (count($materias) === 0): ?>

            <div class="vazio">
                Sua turma ainda não tem matérias cadastradas.
            </div>

        <?php endif; ?>


        <div class="grade">

            <?php foreach ($materias as $m): ?>

                <a
                    class="card-materia"
                    href="./atividade_alun.php?materia=<?= (int) $m['id'] ?>"
                >

                    <span class="nome">
                        <?= h($m['materia']) ?>
                    </span>

                    <span class="prof">
                        Prof. <?= h($m['professor']) ?>
                    </span>

                    <span class="contagem">
                        <?= plural(
                            (int) $m['total'],
                            'atividade',
                            'atividades'
                        ) ?>
                    </span>

                    <?php if ((int) $m['abertas'] > 0): ?>

                        <span class="badge">
                            <?= (int) $m['abertas'] ?> em aberto
                        </span>

                    <?php endif; ?>

                </a>

            <?php endforeach; ?>

        </div>


    <?php else: ?>


        <!-- TELA DAS ATIVIDADES -->

        <a class="voltar" href="./atividade_alun.php">
            ← Voltar às matérias
        </a>


        <h2 class="subtitulo">

            <?= h($materia['materia']) ?>

            <small>
                Prof. <?= h($materia['professor']) ?>
            </small>

        </h2>


        <?php if (count($atividades) === 0): ?>

            <div class="vazio">
                Nenhuma atividade postada nesta matéria ainda.
            </div>

        <?php endif; ?>


        <?php foreach ($atividades as $a): ?>

            <?php

            $encerrada = (
                $a['prazo'] &&
                strtotime($a['prazo']) < time()
            );

            $entregue = !empty($a['id_entrega']);

            ?>


            <div class="atividade <?= $encerrada ? 'encerrada' : '' ?>">

                <div class="topo">

                    <h3><?= h($a['titulo']) ?></h3>

                    <?php if ($entregue): ?>

                        <span class="selo entregue">
                            Entregue
                        </span>

                    <?php elseif ($encerrada): ?>

                        <span class="selo">
                            Encerrada
                        </span>

                    <?php else: ?>

                        <span class="selo aberta">
                            Em aberto
                        </span>

                    <?php endif; ?>

                </div>


                <?php if ($a['descricao']): ?>

                    <p class="descricao">
                        <?= nl2br(h($a['descricao'])) ?>
                    </p>

                <?php endif; ?>


                <div class="rodape">

                    <span>
                        Postada em
                        <?= formatarData($a['data_publicacao']) ?>
                    </span>

                    <span>
                        Prazo:
                        <?= formatarData($a['prazo']) ?>
                    </span>

                </div>


                <!-- ÁREA DE ENTREGA -->

                <div class="area-entrega">

                    <?php if ($entregue): ?>

                        <p class="confirmacao-entrega">
                            ✓ Você já entregou esta atividade.
                        </p>

                        <small>
                            Entregue em:
                            <?= formatarData($a['data_entrega']) ?>
                        </small>

                    <?php elseif (!$encerrada): ?>

                        <form
                            action="atividade_alun.php?materia=<?= $idMateria ?>"
                            method="POST"
                            enctype="multipart/form-data"
                        >

                            <input
                                type="hidden"
                                name="id_atividade"
                                value="<?= (int) $a['num_atividade'] ?>"
                            >

                            <label>
                                Selecione o arquivo da atividade:
                            </label>

                            <input
                                type="file"
                                name="arquivo"
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.txt,.zip"
                                required
                            >

                            <small>
                                Formatos permitidos: PDF, Word, PowerPoint,
                                imagens, TXT e ZIP. Máximo de 10 MB.
                            </small>

                            <button
                                type="submit"
                                name="entregar"
                                value="1"
                                class="btn-entregar"
                            >
                                Entregar atividade
                            </button>

                        </form>

                    <?php else: ?>

                        <p class="prazo-encerrado">
                            O prazo para entrega desta atividade terminou.
                        </p>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>


    <?php endif; ?>

</div>

</body>
</html>
