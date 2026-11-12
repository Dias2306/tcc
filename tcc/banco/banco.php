<?php

$server   = "localhost";
$user     = "root";
$password = "";
$dbname   = "db_escola";


$con = new mysqli($server, $user, $password);

if ($con->connect_error)
{
    die("Erro de conexão: " . $con->connect_error);
}

$sql = "CREATE DATABASE  $dbname";
if ($con->query($sql) == true)
{
    echo " banco criado <br>";
}
else
{
    die("Erro ao criar o banco: " . $con->error);
}


$con = new mysqli($server, $user, $password, $dbname);

if ($con->connect_error)
{
    die("Erro de conexão: " . $con->connect_error);
}



function criarTabela($con, $nome, $sql)
{
    if ($con->query($sql) == true)
    {
        echo " tabela $nome criada<br>";
    }
    else
    {
        echo " ERRO na tabela $nome: " . $con->error . "<br>";
    }
}


$sql = "CREATE TABLE administracao (
    rm     CHAR(5)      NOT NULL,
    nome   VARCHAR(100) NOT NULL,
    cpf    CHAR(11)     NOT NULL,
    senha  VARCHAR(255) NOT NULL,
    PRIMARY KEY (rm)
);";
criarTabela($con, "administracao", $sql);

$sql = "CREATE TABLE  curso (
    codigo  INT          NOT NULL AUTO_INCREMENT,
    nome    VARCHAR(100) NOT NULL,
    PRIMARY KEY (codigo),
    UNIQUE (nome)
);";
criarTabela($con, "curso", $sql);


$sql = "CREATE TABLE  materia (
    id_materia  INT          NOT NULL AUTO_INCREMENT,
    nome        VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_materia),
    UNIQUE (nome)
);";
criarTabela($con, "materia", $sql);


$sql = "CREATE TABLE  curso_materia (
    codigo_curso  INT NOT NULL,
    id_materia    INT NOT NULL,
    PRIMARY KEY (codigo_curso, id_materia),
    FOREIGN KEY (codigo_curso) REFERENCES curso(codigo) ON DELETE CASCADE,
    FOREIGN KEY (id_materia)   REFERENCES materia(id_materia) ON DELETE CASCADE
);";
criarTabela($con, "curso_materia", $sql);


$sql = "CREATE TABLE  turma (
    id_turma      INT              NOT NULL AUTO_INCREMENT,
    nome          VARCHAR(50)      NOT NULL,
    codigo_curso  INT              NOT NULL,
    ano_letivo    YEAR             NOT NULL,
    capacidade    TINYINT UNSIGNED NOT NULL DEFAULT 40,
    PRIMARY KEY (id_turma),
    FOREIGN KEY (codigo_curso) REFERENCES curso(codigo),
    CHECK (capacidade > 0)
);";
criarTabela($con, "turma", $sql);


$sql = "CREATE TABLE  aluno (
    rm        CHAR(5)      NOT NULL,
    nome      VARCHAR(100) NOT NULL,
    cpf       CHAR(11)     NOT NULL,
    senha     VARCHAR(255) NOT NULL,
    cep       CHAR(8)      NOT NULL,
    rua       VARCHAR(100) NOT NULL,
    numero    VARCHAR(10)  NOT NULL,
    bairro    VARCHAR(60)  NOT NULL,
    cidade    VARCHAR(60)  NOT NULL,
    estado    CHAR(2)      NOT NULL,
    id_turma  INT          NOT NULL,
    PRIMARY KEY (rm),
    FOREIGN KEY (id_turma) REFERENCES turma(id_turma)
);";
criarTabela($con, "aluno", $sql);

$sql = "CREATE TABLE  professor (
    rm        CHAR(5)      NOT NULL,
    nome      VARCHAR(100) NOT NULL,
    cpf       CHAR(11)     NOT NULL,
    telefone  VARCHAR(11)  NOT NULL,
    senha     VARCHAR(255) NOT NULL,
    PRIMARY KEY (rm)
);";
criarTabela($con, "professor", $sql);

// 8. professor_turma_materia
$sql = "CREATE TABLE  professor_turma_materia (
    id            INT     NOT NULL AUTO_INCREMENT,
    rm_professor  CHAR(5) NOT NULL,
    id_turma      INT     NOT NULL,
    id_materia    INT     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (id_turma, id_materia),
    FOREIGN KEY (rm_professor) REFERENCES professor(rm),
    FOREIGN KEY (id_turma)     REFERENCES turma(id_turma),
    FOREIGN KEY (id_materia)   REFERENCES materia(id_materia)
);";
criarTabela($con, "professor_turma_materia", $sql);


$sql = "CREATE TABLE atividade (
  rm_professor                CHAR(5)      NOT NULL,
  num_atividade               INT          NOT NULL DEFAULT 0,
  id_professor_turma_materia  INT          NOT NULL,
  titulo                      VARCHAR(150) NOT NULL,
  descricao                   TEXT         NULL,
  data_publicacao             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  prazo                       DATETIME     NULL,
  PRIMARY KEY (rm_professor, num_atividade),
  FOREIGN KEY (rm_professor) REFERENCES professor(rm),
  FOREIGN KEY (id_professor_turma_materia) REFERENCES professor_turma_materia(id)
);";
criarTabela($con, "atividade", $sql);


$sql = "CREATE TABLE  material (
    id_material                 INT          NOT NULL AUTO_INCREMENT,
    id_professor_turma_materia  INT          NOT NULL,
    titulo                      VARCHAR(150) NOT NULL,
    descricao                   TEXT         NULL,
    arquivo                     VARCHAR(255) NOT NULL,
    data_publicacao             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_material),
    FOREIGN KEY (id_professor_turma_materia) REFERENCES professor_turma_materia(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
criarTabela($con, "material", $sql);


$sql = "CREATE TABLE entrega (
  id_entrega     INT          NOT NULL AUTO_INCREMENT,
  rm_professor   CHAR(5)      NOT NULL,
  num_atividade  INT          NOT NULL,
  rm_aluno       CHAR(5)      NOT NULL,
  arquivo        VARCHAR(255) NOT NULL,
  data_envio     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  nota           DECIMAL(4,2) NULL,
  PRIMARY KEY (id_entrega),
  UNIQUE (rm_professor, num_atividade, rm_aluno),
  FOREIGN KEY (rm_professor, num_atividade)
    REFERENCES atividade(rm_professor, num_atividade),
  FOREIGN KEY (rm_aluno) REFERENCES aluno(rm),
  CHECK (nota IS NULL OR (nota >= 0 AND nota <= 10))
);";
criarTabela($con, "entrega", $sql);


$con->query("DROP TRIGGER IF EXISTS trg_atividade_numero");

$sql = "CREATE TRIGGER trg_atividade_numero
BEFORE INSERT ON atividade
FOR EACH ROW
BEGIN
  SET NEW.num_atividade = (
    SELECT COALESCE(MAX(num_atividade), 0) + 1
    FROM atividade
    WHERE rm_professor = NEW.rm_professor
  );
END";

if ($con->query($sql) === true)
{
    echo " trigger trg_atividade_numero criado<br>";
}
else
{
    echo " ERRO no trigger: " . $con->error . "<br>";
}



$sql = "CREATE TABLE IF NOT EXISTS horario_aula (
    id_horario    INT          NOT NULL AUTO_INCREMENT,
    rm_professor  CHAR(5)      NOT NULL,
    id_turma      INT          NOT NULL,
    materia       VARCHAR(60)  NOT NULL,
    dia_semana    TINYINT      NOT NULL,
    hora_inicio   TIME         NOT NULL,
    hora_fim      TIME         NOT NULL,
    sala          VARCHAR(20)  NULL,
    PRIMARY KEY (id_horario),
    UNIQUE (rm_professor, dia_semana, hora_inicio),
    FOREIGN KEY (rm_professor) REFERENCES professor(rm)       ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_turma)     REFERENCES turma(id_turma)     ON DELETE CASCADE ON UPDATE CASCADE,
    CHECK (dia_semana BETWEEN 0 AND 6),
    CHECK (hora_fim > hora_inicio)
);";
criarTabela($con, "horario_aula", $sql);
 
 
// procedure: a turma não pode ter duas aulas que se sobrepõem
$con->query("DROP PROCEDURE IF EXISTS verificar_conflito_turma");
 
$sql = "CREATE PROCEDURE verificar_conflito_turma(
  IN p_turma  INT,
  IN p_dia    TINYINT,
  IN p_inicio TIME,
  IN p_fim    TIME,
  IN p_id     INT
)
BEGIN
  DECLARE v_materia VARCHAR(60) DEFAULT NULL;
  DECLARE v_inicio  TIME;
  DECLARE v_fim     TIME;
 
  SELECT materia, hora_inicio, hora_fim
    INTO v_materia, v_inicio, v_fim
    FROM horario_aula
   WHERE id_turma = p_turma
     AND dia_semana = p_dia
     AND hora_inicio < p_fim
     AND hora_fim > p_inicio
     AND id_horario <> p_id
   LIMIT 1;
 
  IF v_materia IS NOT NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = CONCAT(
        'A turma já tem aula de ', v_materia, ' das ',
        TIME_FORMAT(v_inicio, '%H:%i'), ' às ',
        TIME_FORMAT(v_fim, '%H:%i'), ' nesse dia.'
      );
  END IF;
END";
criarRotina($con, "procedure verificar_conflito_turma", $sql);
 
 
$con->query("DROP TRIGGER IF EXISTS trg_horario_insert");
 
$sql = "CREATE TRIGGER trg_horario_insert
BEFORE INSERT ON horario_aula
FOR EACH ROW
BEGIN
  CALL verificar_conflito_turma(NEW.id_turma, NEW.dia_semana, NEW.hora_inicio, NEW.hora_fim, 0);
END";
criarRotina($con, "trigger trg_horario_insert", $sql);
 
 

$con->query("DROP TRIGGER IF EXISTS trg_horario_update");
 
$sql = "CREATE TRIGGER trg_horario_update
BEFORE UPDATE ON horario_aula
FOR EACH ROW
BEGIN
  CALL verificar_conflito_turma(NEW.id_turma, NEW.dia_semana, NEW.hora_inicio, NEW.hora_fim, NEW.id_horario);
END";
criarRotina($con, "trigger trg_horario_update", $sql);

$con->close();

echo "<br> pronto!";