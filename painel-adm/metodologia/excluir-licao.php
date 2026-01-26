<?php
require_once("../../conexao.php");
@session_start();

$id = $_GET['id'];

$pdo->query("DELETE FROM licoes WHERE id = '$id'");

echo "<script>window.location='../metodologia.php'</script>";
