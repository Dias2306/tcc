<?php

require '../banco/verifica_login.php';
exigirLogin('aluno');

// garante o horário de Brasília nas comparações de prazo
date_default_timezone_set('America/Sao_Paulo');

// Endereços para o navegador. Eles são relativos a ESTA pasta (tcc/aluno/control/),
// por isso precisam do "../" para chegar em tcc/aluno/.
define('PAGINA_ENTREGA',    '../aluno/entrega_alun.php');
define('PAGINA_ATIVIDADES', '../aluno/atividade_alun.php');


// ---------- Regras de envio ----------
$tamanhoMaximo = 10 * 1024 * 1024;   // 10 MB

$extensoesPermitidas = [
    'pdf',
    'doc', 'docx',
    'ppt', 'pptx',
    'zip',
    'jpg', 'jpeg', 'png', 'gif', 'webp',
    'mp4', 'webm'
];

$extensoesImagem = ['jpg', 'jpeg', 'png', 'gif', 'webp'];


// Em caso de ERRO: guarda o aviso e volta para a página da entrega,
// para o aluno corrigir e tentar de novo.
function voltar($tipo, $texto)
{
    $prof = trim($_GET['prof'] ?? '');
    $num  = (int) ($_GET['num'] ?? 0);

    $_SESSION['aviso_entrega'] = ['tipo' => $tipo, 'texto' => $texto];

    if ($prof !== '' && $num > 0)
    {
        header('Location: ' . PAGINA_ENTREGA . '?prof=' . urlencode($prof) . '&num=' . $num);
    }
    else
    {
        header('Location: ' . PAGINA_ATIVIDADES);
    }

    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    header('Location: ' . PAGINA_ATIVIDADES);
    exit;
}

if (!isset($_POST['enviar']) && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0)
{
    voltar('erro', 'O arquivo é maior que o permitido (máximo de 10 MB).');
}

try
{
    require '../banco/conexao.php';
}
catch (mysqli_sql_exception $e)
{
    voltar('erro', 'Erro ao conectar no banco.');
}


// ---------- 1. Ler os dados ----------
$rm   = $_SESSION['rm'];
$prof = trim($_GET['prof'] ?? '');
$num  = (int) ($_GET['num'] ?? 0);

if ($prof === '' || $num === 0)
{
    header('Location: ' . PAGINA_ATIVIDADES);
    exit;
}


try
{
    // ---------- 2. A atividade é da turma deste aluno? ----------
    // (também pega o id da matéria, para saber para onde voltar no final)
    $stmt = $con->prepare(
        "SELECT a.prazo, ptm.id AS id_materia
           FROM atividade a
           JOIN professor_turma_materia ptm ON ptm.id = a.id_professor_turma_materia
           JOIN aluno al ON al.id_turma = ptm.id_turma
          WHERE al.rm = ? AND a.rm_professor = ? AND a.num_atividade = ?"
    );
    $stmt->bind_param('ssi', $rm, $prof, $num);
    $stmt->execute();
    $atividade = $stmt->get_result()->fetch_assoc();

    if (!$atividade)
    {
        header('Location: ' . PAGINA_ATIVIDADES);
        exit;
    }

    // ---------- 3. O prazo ainda não acabou? ----------
    if ($atividade['prazo'] && strtotime($atividade['prazo']) < time())
    {
        voltar('erro', 'O prazo de entrega já terminou.');
    }

    // ---------- 4. Já existe entrega deste aluno? ----------
    $stmt = $con->prepare(
        "SELECT id_entrega, arquivo, nota
           FROM entrega
          WHERE rm_professor = ? AND num_atividade = ? AND rm_aluno = ?"
    );
    $stmt->bind_param('sis', $prof, $num, $rm);
    $stmt->execute();
    $entregaAnterior = $stmt->get_result()->fetch_assoc();

    if ($entregaAnterior && $entregaAnterior['nota'] !== null)
    {
        voltar('erro', 'Esta atividade já foi corrigida e não aceita novo envio.');
    }
}
catch (mysqli_sql_exception $e)
{
    voltar('erro', 'Erro ao consultar o banco.');
}


// ---------- 5. Validar o arquivo ----------
$envio = $_FILES['arquivo'] ?? null;

if ($envio === null || $envio['error'] === UPLOAD_ERR_NO_FILE)
{
    voltar('erro', 'Escolha um arquivo para entregar.');
}

if ($envio['error'] === UPLOAD_ERR_INI_SIZE || $envio['error'] === UPLOAD_ERR_FORM_SIZE)
{
    voltar('erro', 'O arquivo é maior que o permitido (máximo de 10 MB).');
}

if ($envio['error'] !== UPLOAD_ERR_OK)
{
    voltar('erro', 'Erro ao enviar o arquivo. Tente de novo.');
}

if ($envio['size'] > $tamanhoMaximo)
{
    voltar('erro', 'O arquivo é maior que o permitido (máximo de 10 MB).');
}

$extensao = strtolower(pathinfo($envio['name'], PATHINFO_EXTENSION));

if (!in_array($extensao, $extensoesPermitidas, true))
{
    voltar('erro', 'Tipo de arquivo não permitido. Envie PDF, Word, PowerPoint, imagem, vídeo ou ZIP.');
}

if (in_array($extensao, $extensoesImagem, true) && @getimagesize($envio['tmp_name']) === false)
{
    voltar('erro', 'O arquivo não é uma imagem válida.');
}


// ---------- 6. Guardar o arquivo ----------
$pastaUploads  = __DIR__ . '/uploads';
$pastaEntregas = $pastaUploads . '/entregas';

if (!is_dir($pastaEntregas) && !mkdir($pastaEntregas, 0755, true))
{
    voltar('erro', 'Não foi possível criar a pasta de arquivos.');
}

// Bloqueia o acesso direto a qualquer arquivo da pasta uploads
$htaccess = $pastaUploads . '/.htaccess';
$regra    = "Require all denied\n";

if (!file_exists($htaccess) || file_get_contents($htaccess) !== $regra)
{
    file_put_contents($htaccess, $regra);
}

// Nome novo, gerado pelo sistema (nunca o nome que veio do aluno)
$nomeNovo = bin2hex(random_bytes(16)) . '.' . $extensao;

if (!move_uploaded_file($envio['tmp_name'], $pastaEntregas . '/' . $nomeNovo))
{
    voltar('erro', 'Não foi possível guardar o arquivo.');
}

$caminho = 'uploads/entregas/' . $nomeNovo;
$agora   = date('Y-m-d H:i:s');


// ---------- 7. Gravar no banco ----------
try
{
    if ($entregaAnterior)
    {
        // o aluno está trocando o arquivo
        $stmt = $con->prepare(
            "UPDATE entrega SET arquivo = ?, data_envio = ? WHERE id_entrega = ?"
        );
        $stmt->bind_param('ssi', $caminho, $agora, $entregaAnterior['id_entrega']);
        $stmt->execute();
    }
    else
    {
        $stmt = $con->prepare(
            "INSERT INTO entrega (rm_professor, num_atividade, rm_aluno, arquivo, data_envio)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sisss', $prof, $num, $rm, $caminho, $agora);
        $stmt->execute();
    }
}
catch (mysqli_sql_exception $e)
{
    // se não gravou no banco, não deixa o arquivo sobrando na pasta
    unlink($pastaEntregas . '/' . $nomeNovo);
    voltar('erro', 'Erro ao registrar a entrega.');
}


// ---------- 8. Apagar o arquivo antigo (se houve troca) ----------
if ($entregaAnterior)
{
    $antigo     = realpath(__DIR__ . '/' . $entregaAnterior['arquivo']);
    $pastaBase  = realpath($pastaEntregas);

    if ($antigo !== false && strpos($antigo, $pastaBase . DIRECTORY_SEPARATOR) === 0 && is_file($antigo))
    {
        unlink($antigo);
    }
}


// ---------- 9. Sucesso: volta para a tela de atividades da matéria ----------
// O aviso verde fica numa chave própria da sessão (aviso_atividades) e é mostrado
// pelo atividade_alun.php. O card da atividade já aparece como "Entregue".
$_SESSION['aviso_atividades'] = [
    'tipo'  => 'ok',
    'texto' => $entregaAnterior ? 'Entrega substituída com sucesso!' : 'Atividade entregue com sucesso!'
];

header('Location: ' . PAGINA_ATIVIDADES . '?materia=' . (int) $atividade['id_materia']);
exit;