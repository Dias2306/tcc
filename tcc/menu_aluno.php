<?php
require './banco/verifica_login.php';
exigirLogin('aluno');   // 'professor' no index, 'administracao' no cadastro
?>

<!DOCTYPE html>


<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="./css/menu.css">

</head>

<body>
  <div class="cabecalho">

    <div class="texto"><h3>P.A.E</h3></div>

    <span class="botao" onclick="abrirMenu()"><img src="./icon/do-utilizador.png" alt=""></span>
  </div>


  <div id="meuMenuLateral" class="lateral-menu">
    <a href="javascript:void(0)" class="botao-fechar" onclick="fecharMenu()">&times;</a>
    <a href="#">Perfil</a>
    <a href="./atividade_alun.php">Atividades</a>
    <a href="./material_alun.php">Material</a>
    <a href="#">Horário</a>
  </div>

</body>

<script>
  function abrirMenu() {
    document.querySelector(".lateral-menu").style.width = "250px";
    document.getElementById("principal").style.marginRight = "250px";  // era marginLeft
  }

  function fecharMenu() {
    document.querySelector(".lateral-menu").style.width = "0";
    document.getElementById("principal").style.marginRight = "0";      // era marginLeft
  }

</script>

</html>