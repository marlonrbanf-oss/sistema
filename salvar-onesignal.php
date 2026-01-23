<?php
require_once("conexao.php");
@session_start();

// O id_usuario vem da sessão para garantir que um aluno não salve no perfil de outro
$id_usuario = $_SESSION['id_usuario'] ?? null;
$onesignal_id = $_POST['onesignal_id'] ?? null;

if ($id_usuario && $onesignal_id) {
    $res = $pdo->prepare("UPDATE usuarios SET onesignal_id = :oid WHERE id = :id");
    $res->execute([':oid' => $onesignal_id, ':id' => $id_usuario]);
    echo "ID Vinculado com Sucesso no Railway!";
} else {
    echo "Erro: Faltando ID do usuário ou do OneSignal.";
}
