<?php
// config.php - VERSÃO FINAL
$email_adm = 'marlonranf@gmail.com';

// URL do site
if (getenv('RAILWAY_PUBLIC_DOMAIN')) {
    $url_site = 'https://' . getenv('RAILWAY_PUBLIC_DOMAIN');
} else {
    $url_site = 'http://localhost';
}

// Conexão MySQL Railway
$host = 'tramway.proxy.rlwy.net';  // HOST EXTERNO
$port = 58531;                     // PORTA EXTERNA
$banco = 'railway';               // Nome do banco no Railway
$usuario = 'root';
$senha = 'cshQRXuQRexZZdJvMsfcuGUWwFaeYkpk';

$dsn = "mysql:host=$host;port=$port;dbname=$banco;charset=utf8mb4";

// Para desenvolvimento local, use variáveis de ambiente
if (getenv('MYSQLHOST')) {
    $host = getenv('MYSQLHOST');
    $port = getenv('MYSQLPORT') ?: 3306;
    $banco = getenv('MYSQLDATABASE');
    $usuario = getenv('MYSQLUSER');
    $senha = getenv('MYSQLPASSWORD');
    $dsn = "mysql:host=$host;port=$port;dbname=$banco;charset=utf8mb4";
}

// Função de conexão
function getPDO()
{
    global $dsn, $usuario, $senha;

    try {
        $pdo = new PDO($dsn, $usuario, $senha);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Erro DB: " . $e->getMessage());
        return null;
    }
}
