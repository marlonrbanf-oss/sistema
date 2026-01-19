<?php
require_once("../conexao.php");

$id = $_POST['id'];
$token_lido = $_POST['token_professor'];

// A mesma chave que você usou no gerar-qr.php
$chave_secreta = "MINHA_NOVA_ARTE_2026";
$token_correto_hoje = md5(date('Y-m-d') . $chave_secreta);

if ($token_lido === $token_correto_hoje) {
    // Valida a técnica e sobe o nível para 100%
    $res = $pdo->prepare("UPDATE habilidades SET status = 'Dominado', nivel = 100 WHERE id = :id");
    $res->bindValue(":id", $id);
    $res->execute();

    echo "Sucesso";
} else {
    echo "Código Inválido ou Expirado";
}
