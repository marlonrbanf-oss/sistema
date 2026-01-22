<?php
require_once("conexao.php");
$id = $_POST['id'];
$biometria = $_POST['biometria'];
$pdo->prepare("UPDATE usuarios SET biometria = :b WHERE id = :id")->execute(['b' => $biometria, 'id' => $id]);
echo "Sucesso";
