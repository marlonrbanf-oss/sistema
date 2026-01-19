<?php
include_once("config.php");
date_default_timezone_set('America/Sao_Paulo');

try {
	// Usa a string DSN do config.php
	$pdo = new PDO($dsn, $usuario, $senha);

	// Configurar PDO para lançar exceções
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

	// Conexão bem sucedida
	// echo "Conectado com sucesso!";

} catch (PDOException $e) {
	// Modo produção vs desenvolvimento
	if (getenv('RAILWAY_ENVIRONMENT')) {
		// Modo produção - log detalhado, mensagem genérica
		error_log("Erro PDO: " . $e->getMessage());
		error_log("DSN usado: " . $dsn);
		error_log("Host: " . $host . ":" . $porta);

		// Mensagem amigável para usuário
		die("<h3>Sistema temporariamente indisponível</h3>
             <p>Estamos realizando manutenção. Tente novamente em alguns minutos.</p>
             <p>Erro: CONN-001</p>");
	} else {
		// Modo desenvolvimento - mostra erro completo
		echo "<h3>Erro ao conectar com o banco de dados!</h3>";
		echo "<p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>";
		echo "<p><strong>DSN:</strong> " . $dsn . "</p>";
		echo "<p><strong>Arquivo:</strong> " . __FILE__ . "</p>";
		echo "<p><strong>Linha:</strong> " . __LINE__ . "</p>";
		exit();
	}
}
