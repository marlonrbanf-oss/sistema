<?php
require_once("../../conexao.php");
@session_start();

$nome = $_POST['nome_tecnica'];
$licao_id = $_POST['licao_id'];

if ($nome == "") {
    echo "O nome da técnica é obrigatório!";
    exit();
}

$res = $pdo->prepare("INSERT INTO tecnicas (licao_id, nome) VALUES (:licao_id, :nome)");
$res->bindValue(":licao_id", $licao_id);
$res->bindValue(":nome", $nome);
$res->execute();

echo "Salvo com Sucesso";
