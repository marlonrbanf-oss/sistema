<?php
require_once("../conexao.php");
@session_start();

// Validação de segurança (Permitindo Admin, Professor e Balconista acessarem a lógica)
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Balconista') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id = $_GET['id'];
$acao = $_GET['acao'];
$usuario_logado = $_SESSION['nome_usuario'];
$msg = "";
$log_acao = "";
$hoje = date('Y-m-d');

// 1. AÇÃO DE RENOVAR MENSALIDADE (EM DIA)
if ($acao == 'renovar') {
    $novo_vencimento = date('Y-m-d', strtotime('+30 days'));
    $query = $pdo->prepare("UPDATE usuarios SET data_venc = :venc, ativo = 'Sim' WHERE id = :id");
    $query->bindValue(":venc", $novo_vencimento);
    $query->bindValue(":id", $id);
    $query->execute();

    $msg = "Mensalidade renovada com sucesso para " . date('d/m/Y', strtotime($novo_vencimento)) . "!";
    $log_acao = "Renovou mensalidade (Em Dia) do aluno ID: $id";
}

// 2. AÇÃO DE DEFINIR COMO ATRASADO (NOVO)
if ($acao == 'atrasar') {
    // Define o vencimento para ONTEM, o que força o status "Atrasado" no sistema
    $vencimento_passado = date('Y-m-d', strtotime('-1 days'));
    $query = $pdo->prepare("UPDATE usuarios SET data_venc = :venc, ativo = 'Sim' WHERE id = :id");
    $query->bindValue(":venc", $vencimento_passado);
    $query->bindValue(":id", $id);
    $query->execute();

    $msg = "Status do aluno alterado para ATRASADO!";
    $log_acao = "Definiu mensalidade como atrasada para o aluno ID: $id";
}

// 3. AÇÃO DE INATIVAR ALUNO
if ($acao == 'inativar') {
    $query = $pdo->prepare("UPDATE usuarios SET ativo = 'Não' WHERE id = :id");
    $query->bindValue(":id", $id);
    $query->execute();

    $msg = "Usuário inativado com sucesso!";
    $log_acao = "Inativou o cadastro do ID: $id";
}

// 4. AÇÃO DE ATIVAR ALUNO
if ($acao == 'ativar') {
    $query = $pdo->prepare("UPDATE usuarios SET ativo = 'Sim' WHERE id = :id");
    $query->bindValue(":id", $id);
    $query->execute();

    $msg = "Usuário reativado com sucesso!";
    $log_acao = "Ativou o cadastro do ID: $id";
}

// --- REGISTRO NO LOG DO SISTEMA ---
if ($log_acao != "") {
    try {
        $query_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:user, :acao, NOW())");
        $query_log->bindValue(":user", $usuario_logado);
        $query_log->bindValue(":acao", $log_acao);
        $query_log->execute();
    } catch (Exception $e) {
        // Ignora erro de log para não travar o sistema
    }
}

// Retorno para a página de usuários
if ($msg != "") {
    echo "<script>alert('$msg'); window.location='usuarios.php';</script>";
} else {
    echo "<script>window.location='usuarios.php';</script>";
}
