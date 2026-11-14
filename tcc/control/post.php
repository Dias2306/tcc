<?php
require './banco/verifica_login.php';
exigirLogin('professor');

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
    return $data ? date('d/m/Y H:i', strtotime($data)) : 'Sem prazo';
}

$rm = $_SESSION['rm'];

$mensagem = isset($_GET['ok']) ? 'Atividade postada com sucesso!' : '';
$erro     = '';


// PASSO 4: salvar a atividade quando o formulário é enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $idPtm     = (int) ($_POST['id_ptm'] ?? 0);
    $titulo    = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $prazo     = trim($_POST['prazo'] ?? '');

    $descricao = ($descricao === '') ? null : $descricao;
    $prazoSql  = null;

    if ($idPtm === 0 || $titulo === '')
    {
        $erro = 'Escolha a turma e matéria e informe o título.';
    }
    elseif (mb_strlen($titulo) > 150)
    {
        $erro = 'O título pode ter no máximo 150 caracteres.';
    }
    elseif ($prazo !== '')
    {
        // o campo datetime-local envia algo como "2026-11-27T23:59"
        $prazoSql = str_replace('T', ' ', $prazo) . ':00';

        if (strtotime($prazoSql) <= time())
        {
            $erro = 'O prazo precisa ser uma data futura.';
        }
    }

    if ($erro === '')
    {
        try
        {
            $stmt = $con->prepare(
                "SELECT id FROM professor_turma_materia WHERE id = ? AND rm_professor = ?"
            );
            $stmt->bind_param('is', $idPtm, $rm);
            $stmt->execute();

            if ($stmt->get_result()->num_rows === 0)
            {
                $erro = 'Turma ou matéria inválida.';
            }
            else
            {
                
                $stmt = $con->prepare(
                    "INSERT INTO atividade
                       (rm_professor, id_professor_turma_materia, titulo, descricao, prazo)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('sisss', $rm, $idPtm, $titulo, $descricao, $prazoSql);
                $stmt->execute();

                header('Location: ' . $_SERVER['PHP_SELF'] . '?ok=1');
                exit;
            }
        }
        catch (mysqli_sql_exception $e)
        {
            $erro = 'Erro ao postar a atividade.';
        }
    }
}



$stmt = $con->prepare(
    "SELECT ptm.id, t.nome AS turma, m.nome AS materia
       FROM professor_turma_materia ptm
       JOIN turma   t ON t.id_turma   = ptm.id_turma
       JOIN materia m ON m.id_materia = ptm.id_materia
      WHERE ptm.rm_professor = ?
      ORDER BY t.nome, m.nome"
);
$stmt->bind_param('s', $rm);
$stmt->execute();
$opcoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


?>