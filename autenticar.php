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
	// Buscamos o usuário. Note que não filtramos o nível no WHERE para poder identificar o erro depois.
	$res = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND senha = :senha");
	$res->bindValue(":usuario", $usuario);
	$res->bindValue(":senha", $senha);
	$res->execute();

	$dados = $res->fetchAll(PDO::FETCH_ASSOC);
	$linhas = count($dados);

	if ($linhas > 0) {
		$row = $dados[0];

		// --- LÓGICA DE COMPATIBILIDADE ---
		// Verifica qual nome de coluna seu banco está usando (nivel ou nivel_usuario)
		// Isso evita que o login quebre se você renomear a coluna no banco.
		$nivel = '';
		if (isset($row['nivel_usuario'])) {
			$nivel = $row['nivel_usuario'];
		} elseif (isset($row['nivel'])) {
			$nivel = $row['nivel'];
		}

		// 4. Define as variáveis de sessão essenciais
		$_SESSION['id_usuario'] = $row['id'];
		$_SESSION['nome_usuario'] = $row['nome'];
		$_SESSION['email_usuario'] = $row['usuario'];
		$_SESSION['nivel_usuario'] = $nivel; // Padronizamos a sessão como 'nivel_usuario'

		// 5. Redirecionamento baseado no nível
		if ($nivel == 'Admin') {
			echo "<script language='javascript'>window.location='painel-adm/index.php'; </script>";
			exit();
		} else if ($nivel == 'Balconista' || $nivel == 'Professor') {
			echo "<script language='javascript'>window.location='painel-balcao/index.php'; </script>";
			exit();
		} else if ($nivel == 'Cliente') {
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
	echo "Erro no banco de dados: " . $e->getMessage();
	exit();
}
