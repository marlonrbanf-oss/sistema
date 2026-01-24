<?php
// 1. Inicia a sessão ANTES de qualquer saída de código
@session_start();
require_once("conexao.php");

// 2. Verifica se os campos não estão vazios
if (empty($_POST['username']) || empty($_POST['pass'])) {
	echo "<script language='javascript'>window.location='login.php'; </script>";
	exit();
}

$usuario = $_POST['username'];
$senha = $_POST['pass'];

try {
	// 3. Consulta usando PDO Prepare para evitar SQL Injection
	$res = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND senha = :senha");
	$res->bindValue(":usuario", $usuario);
	$res->bindValue(":senha", $senha);
	$res->execute();

	$dados = $res->fetchAll(PDO::FETCH_ASSOC);
	$linhas = count($dados);

	if ($linhas > 0) {
		$row = $dados[0];

		// --- LÓGICA DE COMPATIBILIDADE DE COLUNAS ---
		// Identifica se o banco usa 'nivel_usuario' ou 'nivel'
		$nivel = '';
		if (!empty($row['nivel_usuario'])) {
			$nivel = $row['nivel_usuario'];
		} elseif (!empty($row['nivel'])) {
			$nivel = $row['nivel'];
		}

		// 4. Define as variáveis de sessão essenciais
		$_SESSION['id_usuario'] = $row['id'];
		$_SESSION['nome_usuario'] = $row['nome'];
		$_SESSION['email_usuario'] = $row['usuario'];

		// --- AQUI ESTÁ O SEGREDO DA COMPATIBILIDADE ---
		// Definimos as duas sessões para que painéis antigos e novos funcionem simultaneamente
		$_SESSION['nivel_usuario'] = $nivel;
		$_SESSION['nivel'] = $nivel;

		// 5. Redirecionamento baseado no nível
		// Usamos mb_strtolower para evitar problemas com 'admin' vs 'Admin'
		$nivel_check = mb_strtolower($nivel);

		if ($nivel_check == 'admin') {
			echo "<script language='javascript'>window.location='painel-adm/index.php'; </script>";
			exit();
		} else if ($nivel_check == 'balconista' || $nivel_check == 'professor') {
			echo "<script language='javascript'>window.location='painel-balcao/index.php'; </script>";
			exit();
		} else if ($nivel_check == 'cliente' || $nivel_check == 'atleta') {
			// Se o nível for Atleta, ele também entra no painel do cliente
			echo "<script language='javascript'>window.location='painel-cliente/index.php'; </script>";
			exit();
		} else {
			// Se o nível for vazio ou desconhecido
			echo "<script language='javascript'>alert('Nível de acesso \"$nivel\" não reconhecido.'); window.location='login.php'; </script>";
			exit();
		}
	} else {
		// 6. Dados incorretos
		echo "<script language='javascript'>window.alert('Dados Incorretos ou Usuário Inativo!'); window.location='login.php'; </script>";
		exit();
	}
} catch (PDOException $e) {
	echo "Erro técnico no banco de dados. Por favor, contate o suporte.";
	// Em produção, você pode logar o erro: error_log($e->getMessage());
	exit();
}
