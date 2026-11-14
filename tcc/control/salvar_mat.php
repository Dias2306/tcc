<?php

require '../banco/verifica_login.php';
exigirLogin('professor');


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



function voltar($tipo, $texto)
{
    $_SESSION['aviso_material'] = ['tipo' => $tipo, 'texto' => $texto];

    if ($tipo === 'erro')
    {
        $_SESSION['dados_material'] = $_POST;
    }

    header('Location: ./postar_material.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    header('Location: postar_material.php');
    exit;
}


if (empty($_POST) && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0)
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



$rm        = $_SESSION['rm'];
$idPtm     = (int) ($_POST['id_ptm'] ?? 0);
$titulo    = trim($_POST['titulo'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$tipo      = ($_POST['tipo'] ?? 'arquivo') === 'link' ? 'link' : 'arquivo';
$link      = trim($_POST['link'] ?? '');

$descricao = ($descricao === '') ? null : $descricao;



if ($idPtm === 0 || $titulo === '')
{
    voltar('erro', 'Escolha a turma e matéria e informe o título.');
}

if (mb_strlen($titulo) > 150)
{
    voltar('erro', 'O título pode ter no máximo 150 caracteres.');
}



try
{
    $stmt = $con->prepare(
        "SELECT id FROM professor_turma_materia WHERE id = ? AND rm_professor = ?"
    );
    $stmt->bind_param('is', $idPtm, $rm);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0)
    {
        voltar('erro', 'Turma ou matéria inválida.');
    }
}
catch (mysqli_sql_exception $e)
{
    voltar('erro', 'Erro ao consultar o banco.');
}



$arquivo = '';

if ($tipo === 'link')
{
    
    if (!preg_match('#^https?://#i', $link) || !filter_var($link, FILTER_VALIDATE_URL))
    {
        voltar('erro', 'Informe um link válido, começando com http:// ou https://');
    }

    if (strlen($link) > 255)
    {
        voltar('erro', 'O link é muito grande (máximo de 255 caracteres).');
    }

    $arquivo = $link;
}
else
{
    $envio = $_FILES['arquivo'] ?? null;

    
    if ($envio === null || $envio['error'] === UPLOAD_ERR_NO_FILE)
    {
        voltar('erro', 'Escolha um arquivo para enviar.');
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


    $pastaUploads   = __DIR__ . '/uploads';
    $pastaMateriais = $pastaUploads . '/materiais';

    if (!is_dir($pastaMateriais) && !mkdir($pastaMateriais, 0755, true))
    {
        voltar('erro', 'Não foi possível criar a pasta de arquivos.');
    }


    $htaccess = $pastaUploads . '/.htaccess';
    $regra    = "Require all denied\n";

    if (!file_exists($htaccess) || file_get_contents($htaccess) !== $regra)
    {
        file_put_contents($htaccess, $regra);
    }

  
    $nomeNovo = bin2hex(random_bytes(16)) . '.' . $extensao;

    if (!move_uploaded_file($envio['tmp_name'], $pastaMateriais . '/' . $nomeNovo))
    {
        voltar('erro', 'Não foi possível guardar o arquivo.');
    }

   
    $arquivo = 'uploads/materiais/' . $nomeNovo;
}


// ---------- 5. Gravar no banco ----------
try
{
    $stmt = $con->prepare(
        "INSERT INTO material (id_professor_turma_materia, titulo, descricao, arquivo)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param('isss', $idPtm, $titulo, $descricao, $arquivo);
    $stmt->execute();
}
catch (mysqli_sql_exception $e)
{
 
    if ($tipo === 'arquivo')
    {
        unlink(__DIR__ . '/' . $arquivo);
    }

    voltar('erro', 'Erro ao postar o material.');
}

voltar('ok', 'Material postado com sucesso!');

?>