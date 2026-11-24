<?php

// Recebe a nota digitada pelo professor em entregas_prof.php e grava na tabela entrega.
// Não mostra nada na tela: no final, volta para a página das entregas com um aviso.
require __DIR__ . '/../banco/verifica_login.php';
exigirLogin('professor');

date_default_timezone_set('America/Sao_Paulo');

// Endereço para o navegador (relativo a esta pasta: tcc/arquivos/)
define('PAGINA_ENTREGAS', '../professor/entregas_prof.php');


// Guarda o aviso na sessão e volta para a página das entregas,
// já na linha do aluno (#aluno-20001), para a página não "pular" para o topo.
function voltar($tipo, $texto, $rmAluno = '')
{
    $num = (int) ($_POST['num'] ?? 0);

    $_SESSION['aviso_nota'] = ['tipo' => $tipo, 'texto' => $texto];

    $destino = PAGINA_ENTREGAS . '?num=' . $num;

    $ancora = preg_replace('/[^A-Za-z0-9_-]/', '', $rmAluno);
    if ($ancora !== '')
    {
        $destino .= '#aluno-' . $ancora;
    }

    header('Location: ' . $destino);
    exit;
}

// Quem abrir este arquivo direto no navegador volta para a página das entregas
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    header('Location: ' . PAGINA_ENTREGAS);
    exit;
}

try
{
    require __DIR__ . '/../banco/conexao.php';
}
catch (mysqli_sql_exception $e)
{
    voltar('erro', 'Erro ao conectar no banco.');
}


// ---------- 1. Ler os campos ----------
$rm        = $_SESSION['rm'];                       // professor logado
$num       = (int) ($_POST['num'] ?? 0);
$rmAluno   = trim($_POST['rm_aluno'] ?? '');
$textoNota = trim($_POST['nota'] ?? '');

if ($num === 0 || $rmAluno === '')
{
    header('Location: ' . PAGINA_ENTREGAS);
    exit;
}


// ---------- 2. Validar a nota ----------
// Campo vazio = remover a nota. Senão, precisa ser um número de 0 a 10.
$nota = null;

if ($textoNota !== '')
{
    $textoNota = str_replace(',', '.', $textoNota);   // aceita 8,5 e 8.5

    if (!is_numeric($textoNota))
    {
        voltar('erro', 'A nota deve ser um número de 0 a 10.', $rmAluno);
    }

    $valor = (float) $textoNota;

    if ($valor < 0 || $valor > 10)
    {
        voltar('erro', 'A nota deve estar entre 0 e 10.', $rmAluno);
    }

    $nota = number_format($valor, 2, '.', '');
}


// ---------- 3. Gravar ----------
try
{
    // A entrega precisa ser deste professor (rm_professor = quem está logado)
    $stmt = $con->prepare(
        "SELECT id_entrega
           FROM entrega
          WHERE rm_professor = ? AND num_atividade = ? AND rm_aluno = ?"
    );
    $stmt->bind_param('sis', $rm, $num, $rmAluno);
    $stmt->execute();
    $entrega = $stmt->get_result()->fetch_assoc();

    if (!$entrega)
    {
        voltar('erro', 'Entrega não encontrada.', $rmAluno);
    }

    $stmt = $con->prepare("UPDATE entrega SET nota = ? WHERE id_entrega = ?");
    $stmt->bind_param('si', $nota, $entrega['id_entrega']);
    $stmt->execute();
}
catch (mysqli_sql_exception $e)
{
    voltar('erro', 'Erro ao salvar a nota.', $rmAluno);
}

if ($nota === null)
{
    voltar('ok', 'Nota removida.', $rmAluno);
}

voltar('ok', 'Nota salva: ' . number_format((float) $nota, 2, ',', ''), $rmAluno);