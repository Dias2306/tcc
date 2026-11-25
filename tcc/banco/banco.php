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

function criarRotina($con, $nome, $sql)
{
    if ($con->query($sql) === true)
    {
        echo " $nome criado<br>";
    }
    else
    {
        echo " ERRO em $nome: " . $con->error . "<br>";
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
  data_publicacao             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
    data_publicacao             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
  data_envio     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
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

$sql = "CREATE TABLE IF NOT EXISTS presenca (
    id_horario  INT        NOT NULL,
    data_aula   DATE       NOT NULL,
    rm_aluno    CHAR(5)    NOT NULL,
    presente    TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_horario, data_aula, rm_aluno),
    FOREIGN KEY (id_horario) REFERENCES horario_aula(id_horario) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (rm_aluno)   REFERENCES aluno(rm)                ON DELETE CASCADE ON UPDATE CASCADE
);";
criarTabela($con, "presenca", $sql);

$sql = "CREATE TABLE IF NOT EXISTS nota_bimestre (
    id_professor_turma_materia  INT           NOT NULL,
    bimestre                    TINYINT       NOT NULL,
    rm_aluno                    CHAR(5)       NOT NULL,
    nota                        DECIMAL(4,2)  NOT NULL,
    data_lancamento             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_professor_turma_materia, bimestre, rm_aluno),
    FOREIGN KEY (id_professor_turma_materia) REFERENCES professor_turma_materia(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (rm_aluno)                   REFERENCES aluno(rm)                   ON DELETE CASCADE ON UPDATE CASCADE,
    CHECK (bimestre BETWEEN 1 AND 4),
    CHECK (nota >= 0 AND nota <= 10)
);";
criarTabela($con, "nota_bimestre", $sql);
 
 
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
  DECLARE v_msg     VARCHAR(128);
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_materia = NULL;

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
    SET v_msg = CONCAT(
      'A turma já tem aula de ', v_materia, ' das ',
      TIME_FORMAT(v_inicio, '%H:%i'), ' às ',
      TIME_FORMAT(v_fim, '%H:%i'), ' nesse dia.'
    );
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
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

function inserir($con, $nome, $sql)
{
    if ($con->query($sql) === true)
    {
        echo " $nome inserido(s)<br>";
    }
    else
    {
        echo " ERRO em $nome: " . $con->error . "<br>";
    }
}


// a senha 123456 já salva com hash
$hash = password_hash("123456", PASSWORD_DEFAULT);


// ---------- administracao ----------
$sql = "INSERT INTO administracao (rm, nome, cpf, senha) VALUES
    ('90001', 'Secretaria Escolar', '00000000001', '$hash')";
inserir($con, "administracao", $sql);


// ---------- curso ----------
$sql = "INSERT INTO curso (codigo, nome) VALUES
    (1, 'Desenvolvimento de Sistemas'),
    (2, 'Informática para Internet')";
inserir($con, "curso", $sql);


// ---------- materia ----------
$sql = "INSERT INTO materia (id_materia, nome) VALUES
    (1, 'Programação Web'),
    (2, 'Banco de Dados'),
    (3, 'Lógica de Programação'),
    (4, 'Análise de Sistemas')";
inserir($con, "materia", $sql);


// ---------- curso_materia ----------
$sql = "INSERT INTO curso_materia (codigo_curso, id_materia) VALUES
    (1, 1), (1, 2), (1, 3), (1, 4),
    (2, 1), (2, 3)";
inserir($con, "curso_materia", $sql);


// ---------- turma ----------
$sql = "INSERT INTO turma (id_turma, nome, codigo_curso, ano_letivo, capacidade) VALUES
    (1, 'DS-2A', 1, 2026, 40),
    (2, 'DS-3B', 1, 2026, 40),
    (3, 'II-1A', 2, 2026, 35)";
inserir($con, "turma", $sql);


// ---------- professor ----------
$sql = "INSERT INTO professor (rm, nome, cpf, telefone, senha) VALUES
    ('10001', 'Carlos Mendes', '00000000002', '11900000001', '$hash'),
    ('10002', 'Ana Souza',     '00000000003', '11900000002', '$hash')";
inserir($con, "professor", $sql);


// ---------- aluno ----------
$sql = "INSERT INTO aluno (rm, nome, cpf, senha, cep, rua, numero, bairro, cidade, estado, id_turma) VALUES
    ('20001', 'João Silva',    '00000000011', '$hash', '01001000', 'Rua das Flores', '100', 'Centro', 'São Paulo', 'SP', 1),
    ('20002', 'Maria Santos',  '00000000012', '$hash', '01001000', 'Rua das Flores', '120', 'Centro', 'São Paulo', 'SP', 1),
    ('20003', 'Pedro Lima',    '00000000013', '$hash', '01001000', 'Rua do Sol',     '45',  'Centro', 'São Paulo', 'SP', 2),
    ('20004', 'Lucas Ferreira','00000000014', '$hash', '01001000', 'Rua do Sol',     '60',  'Centro', 'São Paulo', 'SP', 3)";
inserir($con, "aluno", $sql);


// ---------- professor_turma_materia ----------
// (cada turma + matéria só pode ter um professor)
$sql = "INSERT INTO professor_turma_materia (id, rm_professor, id_turma, id_materia) VALUES
    (1, '10001', 1, 1),
    (2, '10001', 2, 2),
    (3, '10002', 1, 2),
    (4, '10002', 3, 1),
    (5, '10002', 2, 3)";
inserir($con, "professor_turma_materia", $sql);


// ---------- atividade ----------
// Um INSERT por atividade: o num_atividade é preenchido pelo trigger
// (não informe esse campo).
$sql = "INSERT INTO atividade (rm_professor, id_professor_turma_materia, titulo, descricao, data_publicacao, prazo) VALUES
    ('10001', 1, 'Lista de exercícios de HTML', 'Resolva os exercícios 1 a 10 da apostila e envie o arquivo.', '2026-11-09 14:30:00', '2026-11-27 23:59:00')";
inserir($con, "atividade 1", $sql);

$sql = "INSERT INTO atividade (rm_professor, id_professor_turma_materia, titulo, descricao, data_publicacao, prazo) VALUES
    ('10001', 1, 'Mini projeto: página pessoal', 'Crie uma página pessoal com HTML e CSS (cabeçalho, menu e rodapé).', '2026-11-12 09:00:00', '2026-12-04 23:59:00')";
inserir($con, "atividade 2", $sql);

$sql = "INSERT INTO atividade (rm_professor, id_professor_turma_materia, titulo, descricao, data_publicacao, prazo) VALUES
    ('10001', 2, 'Modelagem do banco da biblioteca', 'Entregue o diagrama entidade-relacionamento da biblioteca.', '2026-11-10 08:15:00', '2026-11-20 23:59:00')";
inserir($con, "atividade 3", $sql);

$sql = "INSERT INTO atividade (rm_professor, id_professor_turma_materia, titulo, descricao, data_publicacao, prazo) VALUES
    ('10002', 3, 'Consultas com JOIN', 'Escreva 5 consultas usando INNER JOIN no banco da escola.', '2026-11-11 16:00:00', '2026-11-25 23:59:00')";
inserir($con, "atividade 4", $sql);

$sql = "INSERT INTO atividade (rm_professor, id_professor_turma_materia, titulo, descricao, data_publicacao, prazo) VALUES
    ('10002', 4, 'Formulário de contato', 'Monte um formulário de contato com validação.', '2026-11-03 10:00:00', NULL)";
inserir($con, "atividade 5", $sql);

$sql = "INSERT INTO atividade (rm_professor, id_professor_turma_materia, titulo, descricao, data_publicacao, prazo) VALUES
    ('10002', 5, 'Exercícios de lógica', 'Resolva os problemas de fluxograma da página 12.', '2026-10-30 11:00:00', '2026-11-06 23:59:00')";
inserir($con, "atividade 6", $sql);


// ---------- material ----------
$sql = "INSERT INTO material (id_professor_turma_materia, titulo, descricao, arquivo) VALUES
    (1, 'Apostila de HTML', 'Material de apoio da primeira unidade.', 'uploads/apostila_html.pdf'),
    (2, 'Slides de modelagem', 'Slides da aula de modelagem de dados.', 'uploads/slides_modelagem.pdf')";
inserir($con, "material", $sql);


// ---------- entrega ----------
// atividade 1 do professor 10001 (Lista de exercícios de HTML)
$sql = "INSERT INTO entrega (rm_professor, num_atividade, rm_aluno, arquivo, data_envio, nota) VALUES
    ('10001', 1, '20001', 'uploads/entregas/20001_lista_html.pdf', '2026-11-11 20:10:00', 8.50),
    ('10001', 1, '20002', 'uploads/entregas/20002_lista_html.pdf', '2026-11-12 18:45:00', NULL)";
inserir($con, "entrega", $sql);


// ---------- horario_aula ----------
// dia_semana: 0=domingo ... 3=quarta, 5=sexta
// A trigger recusa aula que se sobrepõe a outra da mesma turma.
$sql = "INSERT INTO horario_aula (rm_professor, id_turma, materia, dia_semana, hora_inicio, hora_fim, sala) VALUES

    -- domingo (0)
    ('10001', 1, 'Programação Web',       0, '13:00:00', '13:50:00', 'Lab 3'),
    ('10001', 2, 'Banco de Dados',        0, '14:00:00', '14:50:00', 'Sala 12'),
    ('10002', 3, 'Programação Web',       0, '13:00:00', '13:50:00', 'Lab 2'),
    ('10002', 2, 'Lógica de Programação', 0, '16:00:00', '16:50:00', 'Sala 5'),

    -- segunda (1)
    ('10001', 1, 'Programação Web',       1, '13:00:00', '13:50:00', 'Lab 3'),
    ('10001', 2, 'Banco de Dados',        1, '14:00:00', '14:50:00', 'Sala 12'),
    ('10002', 3, 'Programação Web',       1, '13:00:00', '13:50:00', 'Lab 2'),
    ('10002', 2, 'Lógica de Programação', 1, '16:00:00', '16:50:00', 'Sala 5'),

    -- terça (2)
    ('10001', 1, 'Programação Web',       2, '13:00:00', '13:50:00', 'Lab 3'),
    ('10001', 2, 'Banco de Dados',        2, '14:00:00', '14:50:00', 'Sala 12'),
    ('10002', 3, 'Programação Web',       2, '13:00:00', '13:50:00', 'Lab 2'),
    ('10002', 2, 'Lógica de Programação', 2, '16:00:00', '16:50:00', 'Sala 5'),

    -- quarta (3)
    ('10001', 1, 'Programação Web',       3, '13:00:00', '13:50:00', 'Lab 3'),
    ('10001', 2, 'Banco de Dados',        3, '14:00:00', '14:50:00', 'Sala 12'),
    ('10002', 3, 'Programação Web',       3, '13:00:00', '13:50:00', 'Lab 2'),
    ('10002', 2, 'Lógica de Programação', 3, '16:00:00', '16:50:00', 'Sala 5'),

    -- quinta (4)
    ('10001', 1, 'Programação Web',       4, '13:00:00', '13:50:00', 'Lab 3'),
    ('10001', 2, 'Banco de Dados',        4, '14:00:00', '14:50:00', 'Sala 12'),
    ('10002', 3, 'Programação Web',       4, '13:00:00', '13:50:00', 'Lab 2'),
    ('10002', 2, 'Lógica de Programação', 4, '16:00:00', '16:50:00', 'Sala 5'),

    -- sexta (5)
    ('10001', 1, 'Programação Web',       5, '13:00:00', '13:50:00', 'Lab 3'),
    ('10001', 2, 'Banco de Dados',        5, '14:00:00', '14:50:00', 'Sala 12'),
    ('10002', 3, 'Programação Web',       5, '13:00:00', '13:50:00', 'Lab 2'),
    ('10002', 2, 'Lógica de Programação', 5, '16:00:00', '16:50:00', 'Sala 5'),

    -- sábado (6)
    ('10001', 1, 'Programação Web',       6, '13:00:00', '13:50:00', 'Lab 3'),
    ('10001', 2, 'Banco de Dados',        6, '14:00:00', '14:50:00', 'Sala 12'),
    ('10002', 3, 'Programação Web',       6, '13:00:00', '13:50:00', 'Lab 2'),
    ('10002', 2, 'Lógica de Programação', 6, '16:00:00', '16:50:00', 'Sala 5');";
inserir($con, "horario_aula", $sql);



$con->close();

echo "<br> pronto!";