<?php
require_once("conexao.php");

function enviarPush($id_usuario, $titulo, $mensagem, $url = "index.php")
{
    global $pdo;

    // Busca o ID do OneSignal do usuário no banco
    $query = $pdo->prepare("SELECT onesignal_id FROM usuarios WHERE id = :id");
    $query->execute([':id' => $id_usuario]);
    $user = $query->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['onesignal_id'])) return;

    $content = array("en" => $mensagem);
    $headings = array("en" => $titulo);

    $fields = array(
        'app_id' => "SEU_APP_ID_DO_ONESIGNAL", // Pegue no painel do OneSignal
        'include_player_ids' => array($user['onesignal_id']),
        'url' => $url,
        'contents' => $content,
        'headings' => $headings,
        'chrome_web_icon' => "https://seusite.com/images/logo_app.png"
    );

    $fields = json_encode($fields);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic SUA_REST_API_KEY' // Pegue no painel do OneSignal
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
