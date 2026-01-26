<?php
require_once("../../conexao.php");

$id = $_POST['id'];

if ($id != "") {
    $pdo->query("DELETE FROM tecnicas WHERE id = '$id'");
    echo "Excluído com Sucesso";
}
