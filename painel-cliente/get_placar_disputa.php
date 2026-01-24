<?php
@session_start();
require_once("../conexao.php");

$meu_id = $_SESSION['id_usuario'];
$p_id = $_POST['parceiro_id'];

// O que EU marquei contra ELE
$q_meu = $pdo->prepare("SELECT 
    COUNT(CASE WHEN acao_combate = 'Finalização' THEN 1 END) as fin,
    COUNT(CASE WHEN acao_combate = 'Raspagem' THEN 1 END) as ras,
    COUNT(CASE WHEN acao_combate = 'Passagem' THEN 1 END) as pas
    FROM tecnicas_estudo WHERE usuario_id = :meu AND ultimo_parceiro_id = :dele AND tipo_registro = 'Combate'");
$q_meu->execute([':meu' => $meu_id, ':dele' => $p_id]);
$meu = $q_meu->fetch(PDO::FETCH_ASSOC);

// O que ELE marcou contra MIM
$q_dele = $pdo->prepare("SELECT 
    COUNT(CASE WHEN acao_combate = 'Finalização' THEN 1 END) as fin,
    COUNT(CASE WHEN acao_combate = 'Raspagem' THEN 1 END) as ras,
    COUNT(CASE WHEN acao_combate = 'Passagem' THEN 1 END) as pas
    FROM tecnicas_estudo WHERE usuario_id = :dele AND ultimo_parceiro_id = :meu AND tipo_registro = 'Combate'");
$q_dele->execute([':dele' => $p_id, ':meu' => $meu_id]);
$dele = $q_dele->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'meu' => ['fin' => $meu['fin'] ?? 0, 'ras' => $meu['ras'] ?? 0, 'pas' => $meu['pas'] ?? 0],
    'dele' => ['fin' => $dele['fin'] ?? 0, 'ras' => $dele['ras'] ?? 0, 'pas' => $dele['pas'] ?? 0]
]);
