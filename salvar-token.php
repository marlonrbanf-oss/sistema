<?php
require_once("conexao.php");
@session_start();

$id_usuario = $_SESSION['id_usuario'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);

if ($id_usuario && !empty($input['endpoint'])) {
    $endpoint = $input['endpoint'];
    $token_json = json_encode($input);

    // 1. Limpa tokens antigos desse mesmo dispositivo (endpoint) para evitar duplicados
    $pdo->prepare("DELETE FROM tokens_push WHERE endpoint = :end")->execute([':end' => $endpoint]);

    // 2. Insere o novo token
    $res = $pdo->prepare("INSERT INTO tokens_push (id_usuario, token_json, endpoint) VALUES (:id, :token, :end)");
    $res->execute([
        ':id' => $id_usuario,
        ':token' => $token_json,
        ':end' => $endpoint
    ]);
}
