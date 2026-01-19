<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE SEGURANÇA MÁXIMA
if (@$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// 2. EXECUTAR A LIMPEZA
// Usamos TRUNCATE para resetar a tabela e o contador de IDs (Auto Increment)
try {
    $pdo->query("TRUNCATE TABLE logs");

    // 3. REGISTRAR QUE O LOG FOI LIMPO (Opcional, mas recomendado para auditoria)
    $acao = "Limpou todo o histórico de logs do sistema";
    $usuario = $_SESSION['nome_usuario'];
    $query_log = $pdo->prepare("INSERT INTO logs SET acao = :acao, data = now(), usuario = :usuario");
    $query_log->bindValue(":acao", $acao);
    $query_log->bindValue(":usuario", $usuario);
    $query_log->execute();

    echo "<script language='javascript'>alert('Histórico de logs esvaziado com sucesso!'); </script>";
} catch (Exception $e) {
    echo "<script language='javascript'>alert('Erro ao limpar logs: " . $e->getMessage() . "'); </script>";
}

echo "<script language='javascript'>window.location='logs.php'; </script>";
