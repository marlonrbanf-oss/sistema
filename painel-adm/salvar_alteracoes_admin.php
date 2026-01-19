<?php
require_once("../conexao.php");

$id = $_POST['id'];
$nivel = $_POST['nivel'];
$faixa = $_POST['faixa'];
$graus = $_POST['graus'];
$aulas = $_POST['aulas'];

// 1. Atualiza o nível de acesso na tabela usuarios
$pdo->query("UPDATE usuarios SET nivel_usuario = '$nivel' WHERE id = '$id'");

// 2. Verifica se existe registro de graduação
$check = $pdo->query("SELECT id FROM graduacoes WHERE usuario_id = '$id'");
if ($check->rowCount() > 0) {
    $pdo->query("UPDATE graduacoes SET cor_faixa = '$faixa', graus = '$graus', total_aulas = '$aulas' WHERE usuario_id = '$id'");
} else {
    $pdo->query("INSERT INTO graduacoes (usuario_id, cor_faixa, graus, total_aulas) VALUES ('$id', '$faixa', '$graus', '$aulas')");
}

echo "Dados atualizados com sucesso para o usuário ID: " . $id;
