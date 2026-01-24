<?php
require_once("../conexao.php");
@session_start();
$id_usuario = $_SESSION['id_usuario'];

$busca = '%' . ($_POST['query'] ?? '') . '%';

$query = $pdo->prepare("SELECT id, nome FROM usuarios WHERE nome LIKE :busca AND id != :id AND nivel_usuario = 'Cliente' LIMIT 5");
$query->bindValue(":busca", $busca);
$query->bindValue(":id", $id_usuario);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);

foreach ($res as $row) {
    echo "<a href='#' class='list-group-item list-group-item-action item-aluno' data-id='{$row['id']}' data-nome='{$row['nome']}'>{$row['nome']}</a>";
}
