<?php
require_once("../../conexao.php");

$id = $_POST['id'];
$biometria = $_POST['biometria'];

if (!empty($id) && !empty($biometria)) {
    $query = $pdo->prepare("UPDATE usuarios SET biometria = :bio WHERE id = :id");
    $query->bindValue(":bio", $biometria);
    $query->execute();
    echo "sucesso";
} else {
    echo "erro";
}
