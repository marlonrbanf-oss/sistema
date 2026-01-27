<?php
require_once("../../conexao.php");
@session_start();

// 1. VALIDAÇÃO DE SEGURANÇA
// Garante que apenas usuários autorizados possam apagar registros do banco
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor') {
    http_response_code(403);
    echo "Acesso negado.";
    exit();
}

// 2. RECEBIMENTO DO ID
$id = $_POST['id'] ?? null;

if ($id != "") {
    try {
        // 3. EXCLUSÃO COM PREPARED STATEMENTS (Segurança contra SQL Injection)
        $query = $pdo->prepare("DELETE FROM tecnicas WHERE id = :id");
        $query->bindValue(":id", $id);
        $query->execute();

        // Resposta para o AJAX processar o sucesso
        echo "Excluído com Sucesso";
    } catch (Exception $e) {
        // Caso ocorra erro de conexão ou restrição de chave estrangeira
        http_response_code(500);
        echo "Erro ao excluir: " . $e->getMessage();
    }
} else {
    echo "ID não fornecido.";
}
