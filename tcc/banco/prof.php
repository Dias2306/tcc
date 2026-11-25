<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function responder(array $dados): void {
    echo json_encode($dados);
    exit;
}

if (!isset($_SESSION['rm'])) {
    responder(['erro' => 'Professor não está logado.']);
}

try {
    require 'conexao.php';

    $dia = (int) date('w'); 

    $stmt = $conn->prepare(
        "SELECT h.hora_inicio, h.hora_fim, h.materia, h.sala, t.nome AS turma
           FROM horario_aula h
           JOIN turma t ON t.id_turma = h.id_turma
          WHERE h.rm_professor = ? AND h.dia_semana = ?
          ORDER BY h.hora_inicio"
    );
    $stmt->bind_param('si', $_SESSION['rm'], $dia);
    $stmt->execute();

    responder(['aulas' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
} catch (mysqli_sql_exception $e) {
    responder(['erro' => 'Erro ao acessar o banco.']);
}