<?php
// 1. Inicia a sessão ANTES de qualquer saída de código
@session_start();
require_once("conexao.php");

// 2. Verifica se os campos não estão vazios para evitar erro de variável indefinida
if (empty($_POST['username']) || empty($_POST['pass'])) {
	echo "<script language='javascript'>window.location='login.php'; </script>";
	exit();
}

$usuario = $_POST['username'];
$senha = $_POST['pass'];

// 3. Consulta usando PDO Prepare para evitar SQL Injection
try {
	// Busca o usuário pelo e-mail/usuário
	$res = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND senha = :senha");
	$res->bindValue(":usuario", $usuario);
	$res->bindValue(":senha", $senha);
	$res->execute();

	$dados = $res->fetchAll(PDO::FETCH_ASSOC);
	$linhas = count($dados);

	if ($linhas > 0) {
		// Verifica se o usuário está ativo (Opcional, mas recomendado)
		// Se sua tabela tiver campo 'ativo', adicione a verificação aqui.

		// 4. Define as variáveis de sessão essenciais
		$_SESSION['id_usuario'] = $dados[0]['id'];
		$_SESSION['nome_usuario'] = $dados[0]['nome'];
		$_SESSION['email_usuario'] = $dados[0]['usuario'];
		$_SESSION['nivel_usuario'] = $dados[0]['nivel'];

		$nivel = $dados[0]['nivel'];

		// 5. Redirecionamento baseado no nível (Mantendo sua lógica original)
		if ($nivel == 'Admin') {
			echo "<script language='javascript'>window.location='painel-adm/index.php'; </script>";
			exit();
		} else if ($nivel == 'Balconista' || $nivel == 'Professor') {
			// Ambos os níveis acessam a mesma estrutura de balcão/gestão
			echo "<script language='javascript'>window.location='painel-balcao/index.php'; </script>";
			exit();
		} else if ($nivel == 'Cliente') {
			// Nível 'Cliente' (Atleta) acessa seu portal específico
			echo "<script language='javascript'>window.location='painel-cliente/index.php'; </script>";
			exit();
		} else {
			// Caso exista um nível não mapeado, volta ao login por segurança
			echo "<script language='javascript'>window.location='login.php'; </script>";
			exit();
		}
	} else {
		// 6. Caso os dados estejam incorretos ou não encontrados
		echo "<script language='javascript'>window.alert('Dados Incorretos ou Usuário Inativo!'); window.location='login.php'; </script>";
		exit();
	}
} catch (PDOException $e) {
	// Tratamento de erro de conexão com o banco
	echo "Erro no banco de dados: " . $e->getMessage();
	exit();
}
