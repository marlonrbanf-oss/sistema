<?php
require_once("../conexao.php");

// Pega os dados enviados pelo JavaScript do Aluno
$token_lido = @$_POST['token_professor'];
$id_tecnica = @$_POST['id'];

// A CHAVE DEVE SER IDÊNTICA À DO SEU GERAR-QR.PHP
$chave_secreta = "MINHA_NOVA_ARTE_2026";
$token_correto = md5(date('Y-m-d') . $chave_secreta);

// 1. Verifica se o QR Code que o aluno leu é o gerado pelo professor hoje
if ($token_lido !== $token_correto) {
    echo "QR Code Inválido ou Expirado! Peça para o professor atualizar a tela.";
    exit();
}

// 2. Se o token estiver correto, atualiza a técnica de estudo do aluno para 'Dominado'
$res = $pdo->prepare("UPDATE tecnicas_estudo SET status = 'Dominado' WHERE id = :id");
$res->bindValue(":id", $id_tecnica);
$res->execute();

// 3. (Opcional) Registrar nos logs quem validou (Se quiser usar a sessão do aluno ou dados do post)
// Como o token é fixo por dia, sabemos que foi validado por um professor autorizado.

echo "Sucesso";
