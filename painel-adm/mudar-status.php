<?php
require_once("../conexao.php");
@session_start();

// Validação de segurança
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Balconista') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// Captura de parâmetros
$id = isset($_GET['id']) ? $_GET['id'] : null;
$acao = isset($_GET['acao']) ? $_GET['acao'] : null;
$usuario_logado = isset($_SESSION['nome_usuario']) ? $_SESSION['nome_usuario'] : 'Sistema';

// --- BLOCO DE MANUTENÇÃO AUTOMÁTICA (REGRAS DE NEGÓCIO) ---

// 1. REGRA: Mais de 30 dias de atraso -> Torna Inativo automaticamente
// (Compara a data de vencimento com a data atual menos 30 dias)
$pdo->query("UPDATE usuarios SET ativo = 'Não' WHERE data_venc < DATE_SUB(NOW(), INTERVAL 30 DAY) AND nivel = 'Cliente' AND ativo = 'Sim'");

// 2. REGRA: Mais de 1 ano (365 dias) inativo -> Exclui o cadastro permanentemente
// (Usando o vencimento como base: se venceu há mais de um ano e está inativo, deleta)
$pdo->query("DELETE FROM usuarios WHERE data_venc < DATE_SUB(NOW(), INTERVAL 365 DAY) AND ativo = 'Não' AND nivel = 'Cliente'");

// ---------------------------------------------------------

if (!$id || !$acao) {
    echo "<script>window.location='usuarios.php';</script>";
    exit();
}

$msg = "";
$log_acao = "";

// --- BUSCA O NOME DO ALUNO PARA O LOG ---
$query_u = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
$query_u->bindValue(":id", $id);
$query_u->execute();
$dados_u = $query_u->fetch(PDO::FETCH_ASSOC);
$nome_aluno = ($dados_u) ? $dados_u['nome'] : "ID: " . $id;


// 1. AÇÃO DE RENOVAR MENSALIDADE
if ($acao == 'renovar') {
    $novo_vencimento = date('Y-m-d', strtotime('+30 days'));
    $query = $pdo->prepare("UPDATE usuarios SET data_venc = :venc, ativo = 'Sim' WHERE id = :id");
    $query->bindValue(":venc", $novo_vencimento);
    $query->bindValue(":id", $id);
    $query->execute();

    $msg = "Mensalidade renovada para " . date('d/m/Y', strtotime($novo_vencimento)) . "!";
    $log_acao = "Renovou mensalidade do aluno: $nome_aluno";
}

// 2. AÇÃO DE DEFINIR COMO ATRASADO
if ($acao == 'atrasar') {
    $vencimento_passado = date('Y-m-d', strtotime('-1 days'));
    $query = $pdo->prepare("UPDATE usuarios SET data_venc = :venc, ativo = 'Sim' WHERE id = :id");
    $query->bindValue(":venc", $vencimento_passado);
    $query->bindValue(":id", $id);
    $query->execute();

    $msg = "Status alterado para ATRASADO!";
    $log_acao = "Definiu mensalidade como atrasada para: $nome_aluno";
}

// 3. AÇÃO DE INATIVAR ALUNO
if ($acao == 'inativar' || $acao == 'desativar') {
    $query = $pdo->prepare("UPDATE usuarios SET ativo = 'Não' WHERE id = :id");
    $query->bindValue(":id", $id);
    $query->execute();

    $msg = "Usuário inativado com sucesso!";
    $log_acao = "Inativou o cadastro de: $nome_aluno";
}

// 4. AÇÃO DE ATIVAR ALUNO
if ($acao == 'ativar') {
    $query = $pdo->prepare("UPDATE usuarios SET ativo = 'Sim' WHERE id = :id");
    $query->bindValue(":id", $id);
    $query->execute();

    $msg = "Usuário reativado com sucesso!";
    $log_acao = "Ativou o cadastro de: $nome_aluno";
}

// --- REGISTRO NO LOG DO SISTEMA ---
if ($log_acao != "") {
    try {
        $query_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:user, :acao, NOW())");
        $query_log->bindValue(":user", $usuario_logado);
        $query_log->bindValue(":acao", $log_acao);
        $query_log->execute();
    } catch (Exception $e) {
    }
}

// Retorno
if ($msg != "") {
    echo "<script>alert('$msg'); window.location='usuarios.php';</script>";
} else {
    echo "<script>window.location='usuarios.php';</script>";
}
