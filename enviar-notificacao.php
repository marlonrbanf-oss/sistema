<?php
require_once("conexao.php");

/**
 * Função Mestre para enviar Push Notifications via OneSignal
 * @param int $id_usuario ID do aluno ou professor no banco de dados
 * @param string $titulo O título da notificação (ex: "Nova Técnica!")
 * @param string $mensagem O texto da mensagem (ex: "O professor postou o treino de hoje.")
 * @param string $url O link para onde o usuário será levado (ex: "painel-cliente/index.php")
 */
function enviarPush($id_usuario, $titulo, $mensagem, $url = "index.php")
{
    global $pdo;

    // 1. Busca o ID único do dispositivo do usuário salvo no seu banco
    $query = $pdo->prepare("SELECT onesignal_id FROM usuarios WHERE id = :id");
    $query->execute([':id' => $id_usuario]);
    $user = $query->fetch(PDO::FETCH_ASSOC);

    // Se o usuário não tem o ID do OneSignal registrado, encerra a função
    if (!$user || empty($user['onesignal_id'])) {
        return "Usuário sem OneSignal ID registrado.";
    }

    // 2. Prepara o conteúdo da mensagem
    $content = array("en" => $mensagem, "pt" => $mensagem);
    $headings = array("en" => $titulo, "pt" => $titulo);

    // 3. Monta o pacote de dados (Payload)
    $fields = array(
        'app_id' => "SEU_APP_ID_AQUI", // <--- COLOQUE O APP ID DO ONESIGNAL AQUI
        'include_player_ids' => array($user['onesignal_id']),
        'url' => $url,
        'contents' => $content,
        'headings' => $headings,
        'chrome_web_icon' => "https://vossa-url-no-railway.app/images/logo_app.png",
        'small_icon' => "ic_stat_onesignal_default"
    );

    $fields = json_encode($fields);

    // 4. Disparo via cURL para a API do OneSignal
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic SUA_REST_API_KEY_AQUI' // <--- COLOQUE SUA REST API KEY AQUI
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // Ignora erros de SSL (comum em servidores locais)

    $response = curl_exec($ch);

    // Log de erros para te ajudar a debugar se não chegar
    if (curl_errno($ch)) {
        error_log('Erro cURL OneSignal: ' . curl_error($ch));
    }

    curl_close($ch);

    return $response;
}
