<?php
require_once("../conexao.php");
@session_start();

// 1. Validação de Segurança (Apenas Admin pode excluir, ou ajuste conforme sua regra)
if (@$_SESSION['nivel_usuario'] != 'Admin') {
    echo "Acesso Negado! Somente administradores podem excluir registros.";
    exit();
}

$id = @$_GET['id'];
$usuario_logado = $_SESSION['nome_usuario'];

if ($id != "") {
    try {
        // A. Busca o nome antes de excluir para o Log
        $query_u = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
        $query_u->bindValue(":id", $id);
        $query_u->execute();
        $dados_u = $query_u->fetch(PDO::FETCH_ASSOC);
        $nome_excluido = ($dados_u) ? $dados_u['nome'] : "ID: " . $id;

        // B. Exclui primeiro as referências em outras tabelas (Graduações)
        // Isso evita erros de chave estrangeira se houver
        $pdo->query("DELETE FROM graduacoes WHERE usuario_id = '$id'");

        // C. Exclui o usuário
        $pdo->query("DELETE FROM usuarios WHERE id = '$id'");

        // D. Registra no Log de Atividades
        $acao = "Excluiu o usuário: " . $nome_excluido;
        $res_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:usuario_logado, :acao, NOW())");
        $res_log->bindValue(":usuario_logado", $usuario_logado);
        $res_log->bindValue(":acao", $acao);
        $res_log->execute();

        echo "<script>alert('Removido com sucesso!'); window.location='usuarios.php';</script>";
    } catch (Exception $e) {
        echo "Erro ao excluir: " . $e->getMessage();
    }
} else {
    echo "<script>window.location='usuarios.php';</script>";
}
