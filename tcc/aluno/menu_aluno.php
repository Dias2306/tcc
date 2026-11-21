<?php
require '../banco/verifica_login.php';
exigirLogin('aluno');   
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="./css/menu.css">
  
</head>


<body>

  <div id="meuMenuLateral" class="lateral-menu">
    <a href="javascript:void(0)" class="botao-fechar" onclick="fecharMenu()">&times;</a>
    <a href="#">Perfil</a>
    <a href="./atividade_alun.php">Atividades</a>
    <a href="./material_alun.php">Material</a>
    <a href="#">Horário</a>
  </div>

  <main id="principal">
    <section class="banner-hero">
      <div class="marca-pae">
        <h1>P.A.E.</h1>
        <p>PROJETO DE AUXÍLIO ESCOLAR</p>
      </div>

      <div class="frase-hero">
        <p>
          PROMOVENDO<br>
          EDUCAÇÃO ACESSÍVEL E<br>
          APOIO CONTÍNUO PARA<br>
          TRANSFORMAR O<br>
          APRENDIZADO EM<br>
          OPORTUNIDADE.
        </p>
      </div>
    </section>

    <section class="barra-atalhos">
      <div class="lista-botoes">
        <a href="#" class="card-atalho">FREQUÊNCIA</a>
        <a href="./atividade_alun.php" class="card-atalho">ATIVIDADES</a>
        <a href="./material_alun.php" class="card-atalho">MATERIAL</a>
        <a href="#" class="card-atalho">HORÁRIO</a>
      </div>
    </section>
  </main>

</body>


</html>
