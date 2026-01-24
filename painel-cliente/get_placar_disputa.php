<?php
@session_start();
require_once("../conexao.php");

$meu_id = $_SESSION['id_usuario'];
$parceiro_id = $_POST['parceiro_id'];

// Buscamos apenas os registros onde EU fui o autor da técnica e o colega foi meu parceiro
$res = $pdo->prepare("SELECT 
    COUNT(CASE WHEN acao_combate = 'Finalização' THEN 1 END) as fin,
    COUNT(CASE WHEN acao_combate = 'Raspagem' THEN 1 END) as ras,
    COUNT(CASE WHEN acao_combate = 'Passagem' THEN 1 END) as pas
    FROM tecnicas_estudo 
    WHERE usuario_id = :meu_id 
    AND ultimo_parceiro_id = :p_id 
    AND tipo_registro = 'Combate'");

$res->execute([':meu_id' => $meu_id, ':p_id' => $parceiro_id]);
$dados = $res->fetch(PDO::FETCH_ASSOC);

echo json_encode($dados);
