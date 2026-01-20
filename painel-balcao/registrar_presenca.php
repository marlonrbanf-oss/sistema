<?php
require_once("../conexao.php");
@session_start();

// CORREÇÃO: Adicionado o nível 'Professor' na validação de segurança
if (@$_SESSION['nivel_usuario'] != 'Balconista' && @$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "Acesso Negado!";
    exit();
}

$id_aluno = @$_GET['id'];
$usuario_logado = $_SESSION['nome_usuario'];

if ($id_aluno != "") {
    try {
        // 1. Busca nome do aluno para o Log
        $query_u = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
        $query_u->bindValue(":id", $id_aluno);
        $query_u->execute();
        $dados_u = $query_u->fetch(PDO::FETCH_ASSOC);
        $nome_aluno = ($dados_u) ? $dados_u['nome'] : "Aluno ID: " . $id_aluno;

        // 2. Incrementa aula na tabela graduacoes
        $res = $pdo->prepare("UPDATE graduacoes SET total_aulas = total_aulas + 1 WHERE usuario_id = :id");
        $res->bindValue(":id", $id_aluno);
        $res->execute();

        // 3. Registra Log de Atividade
        $acao = "Professor registrou presença: " . $nome_aluno;
        $res_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:usuario_logado, :acao, NOW())");
        $res_log->bindValue(":usuario_logado", $usuario_logado);
        $res_log->bindValue(":acao", $acao);
        $res_log->execute();

        echo "<script>alert('Presença confirmada para $nome_aluno!'); window.location='usuarios.php';</script>";
    } catch (Exception $e) {
        echo "Erro ao registrar: " . $e->getMessage();
    }
} else {
    echo "<script>window.location='usuarios.php';</script>";
}
