<?php
require_once("conexao.php");
@session_start();

// 1. Verifica se os campos não estão vazios
if (empty($_POST['username']) || empty($_POST['pass'])) {
	echo "<script language='javascript'>window.location='login.php'; </script>";
	exit();
}

$usuario = $_POST['username'];
$senha = $_POST['pass'];

// 2. Consulta usando as colunas reais: usuario e senha
$res = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND senha = :senha");
$res->bindValue(":usuario", $usuario);
$res->bindValue(":senha", $senha);
$res->execute();

$dados = $res->fetchAll(PDO::FETCH_ASSOC);
$linhas = count($dados);

if ($linhas > 0) {
	// 3. Define as variáveis de sessão
	$_SESSION['id_usuario'] = $dados[0]['id'];
	$_SESSION['nome_usuario'] = $dados[0]['nome'];
	$_SESSION['email_usuario'] = $dados[0]['usuario'];
	$_SESSION['nivel_usuario'] = $dados[0]['nivel']; // Coluna 'nivel'

	$nivel = $dados[0]['nivel'];

	// 4. Redirecionamento para as pastas da sua imagem
	if ($nivel == 'Admin') {
		echo "<script language='javascript'>window.location='painel-adm/index.php'; </script>";
		exit();
	} else if ($nivel == 'Balconista') {
		echo "<script language='javascript'>window.location='painel-balcao/index.php'; </script>";
		exit();
	} else {
		// Redireciona nível 'Cliente' para a pasta painel-cliente
		echo "<script language='javascript'>window.location='painel-cliente/index.php'; </script>";
		exit();
	}
} else {
	// 5. Caso os dados estejam incorretos
	echo "<script language='javascript'>window.alert('Dados Incorretos!!'); window.location='login.php'; </script>";
}
?>
//Login: admin@novaarte.com

//Senha: admin123

//Destino: O script lerá o nível Admin e te enviará automaticamente para painel-admin/index.php.