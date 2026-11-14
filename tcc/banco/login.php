<?php

session_start();

$server   = "localhost";
$user     = "root";
$password = "";
$dbname   = "db_escola";

$con = new mysqli($server, $user, $password, $dbname);

if ($con->connect_error)
{
    die("Erro de conexão: " . $con->connect_error);
}

$con->set_charset("utf8mb4");

$tipos = [
    [
        "tipo"        => "aluno",
        "tabela"      => "aluno",
        "campo_rm"    => "rm",
        "campo_senha" => "senha",
        "destino"     => "../menu_aluno.php"
    ],
    [
        "tipo"        => "professor",
        "tabela"      => "professor",
        "campo_rm"    => "rm",
        "campo_senha" => "senha",
        "destino"     => "../menu_professor2.html"
    ],
    [
        "tipo"        => "administracao",
        "tabela"      => "administracao",
        "campo_rm"    => "rm",
        "campo_senha" => "senha",
        "destino"     => "../cadastro.php"
    ]
];



function senhaCorreta($con, $t, $rm, $digitada, $salva)
{
    if (password_verify($digitada, $salva))
    {
        return true;
    }

    
    $ehHash = password_get_info($salva)['algo'] !== null;

    if (!$ehHash && hash_equals($salva, $digitada))
    {
        $hash = password_hash($digitada, PASSWORD_DEFAULT);

        $sql  = "UPDATE {$t['tabela']} SET {$t['campo_senha']} = ? WHERE {$t['campo_rm']} = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ss", $hash, $rm);
        $stmt->execute();

        return true;
    }

    return false;
}


if (!isset($_POST['rm'], $_POST['senha']))
{
    header("Location: login.html");
    exit();
}

$rm    = trim($_POST['rm']);
$senha = $_POST['senha'];


foreach ($tipos as $t)
{
    $sql  = "SELECT {$t['campo_senha']} AS senha FROM {$t['tabela']} WHERE {$t['campo_rm']} = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $rm);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows !== 1)
    {
        continue;
    }

    $usuario = $resultado->fetch_assoc();

    if (senhaCorreta($con, $t, $rm, $senha, $usuario['senha']))
    {
        session_regenerate_id(true);

        $_SESSION['rm']   = $rm;
        $_SESSION['tipo'] = $t['tipo'];

        header("Location: " . $t['destino']);
        exit();
    }
}



echo "RM ou senha incorretos! <a href='login.html'>Voltar</a>";
?>