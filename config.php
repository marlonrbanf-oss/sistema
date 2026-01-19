<?php
// config.php - VERSÃO PARA RAILWAY COM PDO
$email_adm = 'marlonranf@gmail.com';

// URL do site - AUTO DETECT NO RAILWAY
if (getenv('RAILWAY_STATIC_URL')) {
    $url_site = getenv('RAILWAY_STATIC_URL');
} elseif (getenv('RAILWAY_PUBLIC_DOMAIN')) {
    $url_site = 'https://' . getenv('RAILWAY_PUBLIC_DOMAIN');
} else {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $url_site = $protocol . "://" . $_SERVER['HTTP_HOST'];
}

// DADOS PARA CONEXÃO COM BD - VARIÁVEIS DE AMBIENTE RAILWAY
$banco = getenv('MYSQLDATABASE') ?: 'delivery';
$host = getenv('MYSQLHOST') ?: 'localhost';
$usuario = getenv('MYSQLUSER') ?: 'root';
$senha = getenv('MYSQLPASSWORD') ?: '';
$porta = getenv('MYSQLPORT') ?: '3306';

// STRING DE CONEXÃO PDO (para usar no seu arquivo atual)
$dsn = "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4";
