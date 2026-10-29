<?php

$server = "localhost";
$user = "root";
$pasword = "";
$dbname = "db_escola";

$con = new mysqli($server, $user,$pasword);

if($con-> connect_error)
{
    die();
}

$sql = "CREATE DATABASE $dbname";
if($con -> query($sql) === true)
{
    echo " banco criado <br>";
}

$con = new mysqli($server,$user,$pasword,$dbname);

$sql = "CREATE TABLE Alunos (
    rm varchar(6) not null primary key,
    nome varchar(80) not null,
    senha varchar(8) not null,
    cep varchar(12),
    endereco varchar(90),
    bairro varchar(50),
    estado varchar(30));";

if($con -> query($sql) === true)
{
    echo " tabela Aluno criado<br>";
}

$sql = "CREATE TABLE professor (
    rm varchar(6) not null primary key,
    nome varchar(80) not null, 
    senha varchar(8) not null,
    graduacao varchar(80),
    cep varchar(12),
    endereco varchar(90),
    bairro varchar(50),
    estado varchar(30));";

if($con -> query($sql) === true)
{
    echo " tabela Professor criado<br>";
}

$sql = "CREATE TABLE curso (
    cur_codigo int auto_increment not null primary key,
    cur_nome varchar(80) not null);";


if($con -> query($sql) === true)
{
    echo " tabela Curso criado<br>";
}

$sql = "CREATE TABLE administracao (
    adm_nome varchar(80) not null,
    adm_rm varchar(6) not null primary key,
    adm_senha varchar(8) not null);";

    if($con -> query($sql) === true)
{
    echo " tabela administração criado<br>";
}
?>

