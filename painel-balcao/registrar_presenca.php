<?php
require_once("../conexao.php");
@session_start();

// Validação: Somente Balconista ou Admin
if (@$_SESSION['nivel_usuario'] != 'Balconista' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "Acesso Negado!";
    exit();
}

$id_aluno = @$_GET['id'];
$usuario_logado = $_SESSION['nome_usuario'];

if ($id_aluno != "") {
    // 1. Busca nome do aluno para o Log
    $query_u = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
    $query_u->bindValue(":id", $id_aluno);
    $query_u->execute();
    $dados_u = $query_u->fetch(PDO::FETCH_ASSOC);
    $nome_aluno = $dados_u['nome'];

    // 2. Incrementa aula
    $res = $pdo->prepare("UPDATE graduacoes SET total_aulas = total_aulas + 1 WHERE usuario_id = :id");
    $res->bindValue(":id", $id_aluno);
    $res->execute();

    // 3. Registra Log
    $acao = "Professor registrou presença: " . $nome_aluno;
    $res_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:usuario_logado, :acao, NOW())");
    $res_log->bindValue(":usuario_logado", $usuario_logado);
    $res_log->bindValue(":acao", $acao);
    $res_log->execute();

    echo "<script>alert('Presença confirmada!'); window.location='usuarios.php';</script>";
}
