<?php
require_once("conexao.php"); // Como ele está na raiz, o caminho é direto
@session_start();

$id_usuario = $_SESSION['id_usuario'] ?? null;
$onesignal_id = $_POST['onesignal_id'] ?? null;

if ($id_usuario && $onesignal_id) {
    // Atualiza o ID do OneSignal para o usuário logado
    $res = $pdo->prepare("UPDATE usuarios SET onesignal_id = :oid WHERE id = :id");
    $res->execute([':oid' => $onesignal_id, ':id' => $id_usuario]);
    echo "ID Vinculado com sucesso!";
} else {
    echo "Erro: Dados insuficientes.";
}
