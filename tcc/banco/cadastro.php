<?php

$estado=$nome=$cpf=$rua= $cep=$bairro=$estado="";

$server = "localhost";
$user = "root";
$pasword = "";
$dbname = "db_escola";

$con = new mysqli($server,$user,$pasword,$dbname);


if($_SERVER['REQUEST_METHOD'] == "POST")
{
    $nome = $_POST['nome'];
    $rm = $_POST['rm'];
    $senha = $_POST['senha'];
    $cep = $_POST['cep'];
    $rua = $_POST['rua'];
    $bairro = $_POST['bairro'];
    $estado = $_POST['estado'];

    if($nome =='' && $rm == '' && $senha == '')
        {  
            echo "<h2>NÃO FUNCIONOU</h2>";
       
    }
    else{

    
    $smt = $con-> prepare( "INSERT INTO Alunos (rm, nome, senha,cep, endereco, bairro, estado) VALUES (?, ?, ?, ?,  ?, ?, ?)");
    $smt-> bind_param("sssssss",$rm, $nome,$senha, $cep, $rua, $bairro, $estado);
    $smt-> execute();
    header("location: cadastro.html");
    $con->close();
    $smt->close();
    }

}

?>