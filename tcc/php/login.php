<?php


$server = "localhost";
$user = "root";
$pasword = "";
$dbname = "db_escola";

$con = new mysqli($server, $user,$pasword,$dbname);


if($con-> connect_error)
{
    die();
}

if (isset($_POST['rm'], $_POST['senha'])) {
    $rm = $_POST['rm'];
    $senha = $_POST['senha'];

    // Consulta com Prepared Statement para evitar SQL Injection
    $sql = "SELECT rm, senha FROM alunos WHERE rm = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $rm);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        if ($senha == $usuario['senha'] ) {
   
            
            header("Location: menu.html");
            exit();
        }
    }


    $sql = "SELECT rm, senha FROM professor WHERE rm = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $rm);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        if ($senha == $usuario['senha'] ) {
   
            
            header("Location: index.html");
            exit();
        }
    }

    $sql = "SELECT adm_rm, adm_senha FROM administracao WHERE adm_rm = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $rm);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        if ($senha == $usuario['adm_senha'] ) {
   
            
            header("Location: cadastro.html");
            exit();
        }
    }

    // Se falhar o login
    echo "E-mail ou senha incorretos!";
} else {
    header("Location: login.html");
    exit();
}
?>