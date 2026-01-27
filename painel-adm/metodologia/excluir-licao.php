<?php
require_once("../../conexao.php");
@session_start();

// 1. VALIDAÇÃO DE SEGURANÇA
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "Acesso negado.";
    exit();
}

// 2. CAPTURA DO ID
$id = $_GET['id'];

if ($id != "") {
    try {
        // INÍCIO DA TRANSAÇÃO
        $pdo->beginTransaction();

        // 3. LIMPEZA DE DEPENDÊNCIAS (Técnicas vinculadas a esta lição)
        // Isso evita erros de integridade e mantém o banco limpo
        $pdo->query("DELETE FROM tecnicas WHERE licao_id = '$id'");

        // 4. EXCLUSÃO DA LIÇÃO
        $pdo->query("DELETE FROM licoes WHERE id = '$id'");

        // CONFIRMA AS EXCLUSÕES
        $pdo->commit();
    } catch (Exception $e) {
        // SE DER ERRO, DESFAZ TUDO
        $pdo->rollBack();
        echo "Erro ao excluir: " . $e->getMessage();
        exit();
    }
}

// 5. REDIRECIONAMENTO
echo "<script>window.location='../metodologia.php'</script>";
