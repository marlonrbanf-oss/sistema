<?php
require_once("conexao.php");

// Definir o cabeçalho para que o navegador entenda que o retorno é um JSON puro
header('Content-Type: application/json');

try {
    // Busca apenas id e biometria de clientes que já possuem rosto cadastrado
    $query = $pdo->query("SELECT id, biometria FROM usuarios WHERE biometria IS NOT NULL AND nivel = 'Cliente'");
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($res);
} catch (Exception $e) {
    // Caso ocorra erro na query, retorna um array vazio para não quebrar o JS do terminal
    echo json_encode([]);
}
