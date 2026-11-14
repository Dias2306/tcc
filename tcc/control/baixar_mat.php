<?php

// Entrega um material (visualizar ou baixar), mas só para quem tem direito:
//   - aluno da turma do material
//   - professor dono do material
// Uso: baixar_material.php?id=7&acao=ver   (abre no navegador)
//      baixar_material.php?id=7&acao=baixar (força o download)
session_start();

if (!isset($_SESSION['tipo'], $_SESSION['rm']))
{
    header('Location: ../login.html');
    exit;
}

function negar($codigo, $mensagem)
{
    http_response_code($codigo);
    echo $mensagem;
    exit;
}

$tipoUsuario = $_SESSION['tipo'];
$rm          = $_SESSION['rm'];

if ($tipoUsuario !== 'aluno' && $tipoUsuario !== 'professor')
{
    negar(403, 'Acesso negado.');
}

$id   = (int) ($_GET['id'] ?? 0);
$acao = (($_GET['acao'] ?? 'ver') === 'baixar') ? 'baixar' : 'ver';

if ($id === 0)
{
    negar(404, 'Material não encontrado.');
}

try
{
    require '../banco/conexao.php';
}
catch (mysqli_sql_exception $e)
{
    negar(500, 'Erro ao conectar no banco.');
}


// ---------- 1. Buscar o material ----------
$stmt = $con->prepare(
    "SELECT mt.titulo, mt.arquivo, ptm.id_turma, ptm.rm_professor
       FROM material mt
       JOIN professor_turma_materia ptm ON ptm.id = mt.id_professor_turma_materia
      WHERE mt.id_material = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$material = $stmt->get_result()->fetch_assoc();

if (!$material)
{
    negar(404, 'Material não encontrado.');
}


// ---------- 2. Conferir se esta pessoa pode ver este material ----------
if ($tipoUsuario === 'aluno')
{
    // o aluno precisa ser da turma do material
    $stmt = $con->prepare("SELECT rm FROM aluno WHERE rm = ? AND id_turma = ?");
    $stmt->bind_param('si', $rm, $material['id_turma']);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0)
    {
        negar(403, 'Você não tem acesso a este material.');
    }
}
else
{
    // o professor precisa ser o dono
    if ($material['rm_professor'] !== $rm)
    {
        negar(403, 'Você não tem acesso a este material.');
    }
}


// ---------- 3. Link: só redireciona ----------
if (preg_match('#^https?://#i', $material['arquivo']))
{
    header('Location: ' . $material['arquivo']);
    exit;
}


// ---------- 4. Achar o arquivo no disco ----------
// realpath + conferência da pasta impedem caminhos como "../../"
$pastaBase = realpath(__DIR__ . '/uploads/materiais');
$caminho   = realpath(__DIR__ . '/' . $material['arquivo']);

if ($pastaBase === false || $caminho === false
    || strpos($caminho, $pastaBase . DIRECTORY_SEPARATOR) !== 0
    || !is_file($caminho))
{
    negar(404, 'Arquivo não encontrado no servidor.');
}


// ---------- 5. Tipo do arquivo ----------
$tiposMime = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'mp4'  => 'video/mp4',
    'webm' => 'video/webm',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'zip'  => 'application/zip'
];

// só estes tipos podem abrir direto no navegador
$podeVisualizar = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'];

$ext  = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
$mime = $tiposMime[$ext] ?? 'application/octet-stream';

$disposicao = ($acao === 'ver' && in_array($ext, $podeVisualizar, true)) ? 'inline' : 'attachment';


// ---------- 6. Nome do arquivo que o aluno vai ver ----------
// no servidor o arquivo tem nome aleatório; aqui usamos o título do material
$nome = trim(preg_replace('/[^\p{L}\p{N}\s._-]/u', '', $material['titulo']));
$nome = ($nome === '' ? 'material' : $nome) . '.' . $ext;

$nomeSimples = preg_replace('/[^A-Za-z0-9._-]+/', '_', $nome);


// ---------- 7. Entregar ----------
session_write_close();   // libera a sessão enquanto o arquivo é enviado

if (ob_get_level())
{
    ob_end_clean();
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($caminho));
header('Content-Disposition: ' . $disposicao . '; filename="' . $nomeSimples . '"; filename*=UTF-8\'\'' . rawurlencode($nome));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private');

readfile($caminho);
exit;