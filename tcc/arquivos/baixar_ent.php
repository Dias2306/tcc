<?php

// Entrega o arquivo de uma ENTREGA (visualizar ou baixar), mas só para quem tem direito:
//   - o professor dono da atividade
//   - o aluno que fez a entrega
// Uso: baixar_ent.php?id=7&acao=ver      (abre no navegador)
//      baixar_ent.php?id=7&acao=baixar   (força o download)
session_start();

function negar($codigo, $mensagem)
{
    http_response_code($codigo);
    echo $mensagem;
    exit;
}

if (!isset($_SESSION['tipo'], $_SESSION['rm']))
{
    negar(403, 'Acesso negado. Faça login.');
}

$tipoUsuario = $_SESSION['tipo'];
$rm          = $_SESSION['rm'];

if ($tipoUsuario !== 'professor' && $tipoUsuario !== 'aluno')
{
    negar(403, 'Acesso negado.');
}

$id   = (int) ($_GET['id'] ?? 0);
$acao = (($_GET['acao'] ?? 'ver') === 'baixar') ? 'baixar' : 'ver';

if ($id === 0)
{
    negar(404, 'Entrega não encontrada.');
}

try
{
    require __DIR__ . '/../banco/conexao.php';
}
catch (mysqli_sql_exception $e)
{
    negar(500, 'Erro ao conectar no banco.');
}


// ---------- 1. Buscar a entrega ----------
$stmt = $con->prepare(
    "SELECT e.arquivo, e.rm_professor, e.rm_aluno,
            al.nome AS aluno, a.titulo
       FROM entrega e
       JOIN aluno al   ON al.rm = e.rm_aluno
       JOIN atividade a ON a.rm_professor = e.rm_professor
                       AND a.num_atividade = e.num_atividade
      WHERE e.id_entrega = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$entrega = $stmt->get_result()->fetch_assoc();

if (!$entrega)
{
    negar(404, 'Entrega não encontrada.');
}


// ---------- 2. Esta pessoa pode ver esta entrega? ----------
if ($tipoUsuario === 'professor' && $entrega['rm_professor'] !== $rm)
{
    negar(403, 'Você não tem acesso a esta entrega.');
}

if ($tipoUsuario === 'aluno' && $entrega['rm_aluno'] !== $rm)
{
    negar(403, 'Você não tem acesso a esta entrega.');
}


// ---------- 3. Achar o arquivo no disco ----------
// realpath + conferência da pasta impedem caminhos como "../../"
$pastaBase = realpath(__DIR__ . '/uploads/entregas');
$caminho   = realpath(__DIR__ . '/' . $entrega['arquivo']);

if ($pastaBase === false || $caminho === false
    || strpos($caminho, $pastaBase . DIRECTORY_SEPARATOR) !== 0
    || !is_file($caminho))
{
    negar(404, 'Arquivo não encontrado no servidor.');
}


// ---------- 4. Tipo do arquivo ----------
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


// ---------- 5. Nome do arquivo que a pessoa vai ver ----------
// no servidor o arquivo tem nome aleatório; aqui usamos "Aluno - Atividade"
$nome = trim(preg_replace('/[^\p{L}\p{N}\s._-]/u', '', $entrega['aluno'] . ' - ' . $entrega['titulo']));
$nome = ($nome === '' ? 'entrega' : $nome) . '.' . $ext;

$nomeSimples = preg_replace('/[^A-Za-z0-9._-]+/', '_', $nome);


// ---------- 6. Entregar ----------
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