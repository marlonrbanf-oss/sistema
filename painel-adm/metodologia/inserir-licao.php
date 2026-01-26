<?php
require_once("../../conexao.php");
@session_start();

$titulo = $_GET['titulo'];
$numero = $_GET['numero'];
$categoria = @$_GET['categoria']; // Nova variável capturada
$id = @$_GET['id'];

// Define uma categoria padrão caso não venha nenhuma (evita erros)
if ($categoria == "") {
    $categoria = "Licao";
}

if ($titulo == "" || $numero == "") {
    echo "Preencha todos os campos!";
    exit();
}

if ($id == "") {
    // Inserir Nova Lição com Categoria
    $res = $pdo->prepare("INSERT INTO licoes (numero, titulo, categoria) VALUES (:numero, :titulo, :categoria)");
} else {
    // Editar Existente com Categoria
    $res = $pdo->prepare("UPDATE licoes SET numero = :numero, titulo = :titulo, categoria = :categoria WHERE id = :id");
    $res->bindValue(":id", $id);
}

$res->bindValue(":numero", $numero);
$res->bindValue(":titulo", $titulo);
$res->bindValue(":categoria", $categoria);
$res->execute();

echo "<script>window.location='../metodologia.php'</script>";
