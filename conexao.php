<?php
include_once("config.php");
date_default_timezone_set('America/Sao_Paulo');

try {
	error_log("Tentando conectar: $dsn");
	error_log("Usuário: $usuario");

	$pdo = new PDO($dsn, $usuario, $senha);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	error_log("✅ Conexão MySQL bem sucedida!");

	// Teste rápido
	$stmt = $pdo->query("SELECT 1 as test");
	$result = $stmt->fetch();
	error_log("Teste SQL: " . $result['test']);
} catch (PDOException $e) {
	error_log("❌ Erro PDO: " . $e->getMessage());
	error_log("DSN: " . $dsn);
	error_log("Host: " . $host . ":" . $porta);

	// Mostra erro detalhado (modo debug)
	if (getenv('RAILWAY_ENVIRONMENT')) {
		die("<h3>Erro de Banco de Dados</h3>
             <p>Mensagem: " . $e->getMessage() . "</p>
             <p>Host: " . $host . ":" . $porta . "</p>
             <p>Banco: " . $banco . "</p>
             <p><small>Contate o administrador: " . $email_adm . "</small></p>");
	}
}
