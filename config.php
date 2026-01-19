<?php
// config.php - VERSÃO RAILWAY CORRETA

$email_adm = 'marlonranf@gmail.com';

// URL do site (Railway já fornece)
if (getenv('RAILWAY_PUBLIC_DOMAIN')) {
    $url_site = 'https://' . getenv('RAILWAY_PUBLIC_DOMAIN');
} else {
    $url_site = 'http://localhost';
}

// BANCO DE DADOS RAILWAY (IMPORTANTE!)
// Railway injeta estas variáveis AUTOMATICAMENTE
$banco = getenv('MYSQLDATABASE') ?: 'railway';
$host = getenv('MYSQLHOST') ?: 'containers-us-west-50.railway.app'; // Exemplo Railway
$usuario = getenv('MYSQLUSER') ?: 'root';
$senha = getenv('MYSQLPASSWORD') ?: '';
$porta = getenv('MYSQLPORT') ?: '7041'; // Railway usa porta diferente

// DSN para PDO
$dsn = "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4";

// Debug (remova em produção)
error_log("Config Railway - Host: $host:$porta, DB: $banco, User: $usuario");
