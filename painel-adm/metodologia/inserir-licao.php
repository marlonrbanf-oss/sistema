<?php
require_once("../../conexao.php");
@session_start();

$titulo = $_GET['titulo'];
$numero = $_GET['numero'];
$id = @$_GET['id']; // Se vier ID, é edição

if ($titulo == "" || $numero == "") {
    echo "Preencha todos os campos!";
    exit();
}

if ($id == "") {
    // Inserir Nova
    $res = $pdo->prepare("INSERT INTO licoes (numero, titulo) VALUES (:numero, :titulo)");
} else {
    // Editar Existente
    $res = $pdo->prepare("UPDATE licoes SET numero = :numero, titulo = :titulo WHERE id = :id");
    $res->bindValue(":id", $id);
}

$res->bindValue(":numero", $numero);
$res->bindValue(":titulo", $titulo);
$res->execute();

echo "<script>window.location='../metodologia.php'</script>";
