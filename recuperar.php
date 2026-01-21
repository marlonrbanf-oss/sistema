<?php
require_once("conexao.php");

// Recupera o e-mail enviado pelo AJAX
$email_usuario = $_POST['email-recuperar'];

// 1. Verifica se o usuário existe no banco de dados
$res = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
$res->bindValue(":usuario", $email_usuario);
$res->execute();

$dados = $res->fetchAll(PDO::FETCH_ASSOC);
$linhas = count($dados);

if ($linhas > 0) {
	$nome_usu = $dados[0]['nome'];
	$senha_usu = $dados[0]['senha'];

	// 2. Configurações do E-mail
	// Certifique-se de que $url_site e $email_adm estejam definidos no seu conexao.php
	$to = $email_usuario;
	$subject = 'Recuperação de Acesso - Nova Arte BJJ';

	// 3. Conteúdo do E-mail em HTML (Layout Moderno)
	$message = "
    <html>
    <head>
        <meta charset='utf-8'>
    </head>
    <body style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;'>
        <div style='max-width: 600px; margin: auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 1px solid #ddd;'>
            <div style='background: #000; padding: 20px; text-align: center;'>
                <h2 style='color: #fff; margin: 0; letter-spacing: 2px;'>NOVA ARTE BJJ</h2>
            </div>
            <div style='padding: 30px; line-height: 1.6; color: #333;'>
                <p style='font-size: 18px;'>Olá, <strong>$nome_usu</strong>!</p>
                <p>Você solicitou a recuperação dos seus dados de acesso ao nosso sistema.</p>
                
                <div style='background: #f9f9f9; border-left: 4px solid #d31b1b; padding: 15px; margin: 20px 0; text-align: center;'>
                    <span style='display: block; font-size: 12px; color: #888; text-transform: uppercase;'>Sua senha atual é:</span>
                    <span style='font-size: 24px; font-weight: bold; color: #000;'>$senha_usu</span>
                </div>

                <p>Para sua segurança, não compartilhe estes dados com ninguém. Caso queira trocar sua senha, você pode fazer isso dentro do seu painel de aluno.</p>
                
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='$url_site' target='_blank' style='background: #000; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>ACESSAR MEU PAINEL</a>
                </div>
            </div>
            <div style='background: #eee; padding: 15px; text-align: center; font-size: 12px; color: #777;'>
                Este e-mail foi gerado automaticamente pelo sistema da Academia Nova Arte BJJ.
            </div>
        </div>
    </body>
    </html>
    ";

	// 4. Cabeçalhos para aceitar HTML e UTF-8
	$remetente = $email_adm;
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type: text/html; charset=utf-8" . "\r\n";
	$headers .= "From: Nova Arte BJJ <" . $remetente . ">" . "\r\n";
	$headers .= "Reply-To: " . $remetente . "\r\n";

	// 5. Envio e Resposta para o Script do Login
	if (@mail($to, $subject, $message, $headers)) {
		echo "Senha enviada para o seu Email!";
	} else {
		echo "Erro no servidor ao enviar e-mail. Tente novamente mais tarde.";
	}
} else {
	// Caso o e-mail não exista no banco
	echo 'Este email não está cadastrado no sistema!';
	exit();
}
