<?php
require_once("conexao.php");
$query = $pdo->query("SELECT id, biometria FROM usuarios WHERE biometria IS NOT NULL AND nivel = 'Cliente'");
echo json_encode($query->fetchAll(PDO::FETCH_ASSOC));
