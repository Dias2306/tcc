<?php


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


function h($texto)
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function formatarData($data)
{
    return $data ? date('d/m/Y H:i', strtotime($data)) : '';
}


function plural($n, $singular, $plural)
{
    return $n . ' ' . ($n == 1 ? $singular : $plural);
}


function infoMaterial($arquivo)
{
    if (preg_match('#^https?://#i', $arquivo))
    {
        return ['rotulo' => 'Link', 'icone' => '🔗', 'link' => true, 'ver' => false];
    }

    $tipos = [
        'pdf'  => ['PDF',        '📄', true],
        'jpg'  => ['Imagem',     '🖼️', true],
        'jpeg' => ['Imagem',     '🖼️', true],
        'png'  => ['Imagem',     '🖼️', true],
        'gif'  => ['Imagem',     '🖼️', true],
        'webp' => ['Imagem',     '🖼️', true],
        'mp4'  => ['Vídeo',      '🎬', true],
        'webm' => ['Vídeo',      '🎬', true],
        'doc'  => ['Word',       '📝', false],
        'docx' => ['Word',       '📝', false],
        'ppt'  => ['PowerPoint', '📊', false],
        'pptx' => ['PowerPoint', '📊', false],
        'zip'  => ['ZIP',        '🗜️', false]
    ];

    $ext  = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
    $info = $tipos[$ext] ?? ['Arquivo', '📁', false];

    return ['rotulo' => $info[0], 'icone' => $info[1], 'link' => false, 'ver' => $info[2]];
}

$rm = $_SESSION['rm'];



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



$idMateria = (int) ($_GET['materia'] ?? 0);

$materias  = [];
$materia   = null;
$materiais = [];


if ($idMateria === 0)
{
    
    $stmt = $con->prepare(
        "SELECT ptm.id, m.nome AS materia, p.nome AS professor,
                COUNT(mt.id_material) AS total
           FROM aluno al
           JOIN professor_turma_materia ptm ON ptm.id_turma = al.id_turma
           JOIN materia m   ON m.id_materia = ptm.id_materia
           JOIN professor p ON p.rm = ptm.rm_professor
           LEFT JOIN material mt ON mt.id_professor_turma_materia = ptm.id
          WHERE al.rm = ?
          GROUP BY ptm.id, m.nome, p.nome
          ORDER BY m.nome"
    );
    $stmt->bind_param('s', $rm);
    $stmt->execute();
    $materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
else
{

    $stmt = $con->prepare(
        "SELECT m.nome AS materia, p.nome AS professor
           FROM aluno al
           JOIN professor_turma_materia ptm ON ptm.id_turma = al.id_turma
           JOIN materia m   ON m.id_materia = ptm.id_materia
           JOIN professor p ON p.rm = ptm.rm_professor
          WHERE al.rm = ? AND ptm.id = ?"
    );
    $stmt->bind_param('si', $rm, $idMateria);
    $stmt->execute();
    $materia = $stmt->get_result()->fetch_assoc();

    if (!$materia)
    {
        header('Location: ./material_alun.php');
        exit;
    }

    $stmt = $con->prepare(
        "SELECT id_material, titulo, descricao, arquivo, data_publicacao
           FROM material
          WHERE id_professor_turma_materia = ?
          ORDER BY data_publicacao DESC"
    );
    $stmt->bind_param('i', $idMateria);
    $stmt->execute();
    $materiais = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>