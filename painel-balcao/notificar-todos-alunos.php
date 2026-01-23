<?php
require_once("../conexao.php");
require_once("enviar-notificacao.php"); // Onde está a função mestre que criamos

// 1. Busca todos os alunos que possuem um ID do OneSignal vinculado
$query = $pdo->query("SELECT onesignal_id FROM usuarios WHERE nivel = 'Cliente' AND onesignal_id IS NOT NULL");
$alunos = $query->fetchAll(PDO::FETCH_ASSOC);

$ids_onesignal = [];
foreach ($alunos as $aluno) {
    $ids_onesignal[] = $aluno['onesignal_id'];
}

if (count($ids_onesignal) > 0) {
    // Título e Mensagem personalizados
    $titulo = "🥋 Nova Técnica de Estudo!";
    $mensagem = "O Professor adicionou um novo conteúdo. Acesse seu portal para conferir!";
    $url = "painel-cliente/index.php";

    // Disparo em massa via API do OneSignal
    $fields = array(
        'app_id' => "SEU_APP_ID_DO_ONESIGNAL",
        'include_player_ids' => $ids_onesignal, // Enviando para o array de IDs
        'url' => $url,
        'headings' => array("en" => $titulo),
        'contents' => array("en" => $mensagem)
    );

    // [Código do cURL igual ao da função enviarPush que passamos antes...]
    // (O OneSignal permite enviar até 2.000 IDs por vez neste array)
}
