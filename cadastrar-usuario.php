<?php
require_once("conexao.php");

// 1. Recebe todos os dados do formulário
$nome = trim(@$_POST['nome']);
$cpf = trim(@$_POST['cpf']);
$telefone = trim(@$_POST['telefone']); // Captura o telefone do formulário
$usuario = trim(@$_POST['email']);
$endereco = trim(@$_POST['endereco']); // Captura o endereço
$senha = trim(@$_POST['senha']);
$nivel = 'Cliente';

// 2. Validação de campos obrigatórios
if ($nome == "" || $usuario == "" || $senha == "" || $cpf == "") {
    echo "Erro: Preencha Nome, E-mail, CPF e Senha!";
    exit();
}

// 3. REGRA: Senha não pode ser igual ao E-mail
if (strtolower($senha) === strtolower($usuario)) {
    echo "Erro: Por segurança, sua senha não pode ser igual ao seu e-mail!";
    exit();
}

// 4. REGRA: Senha ÚNICA para o Check-in
$query_senha = $pdo->prepare("SELECT id FROM usuarios WHERE senha = :senha");
$query_senha->bindValue(":senha", $senha);
$query_senha->execute();

if ($query_senha->rowCount() > 0) {
    echo "Erro: Esta senha já está em uso por outro aluno!";
    exit();
}

// 5. Verifica duplicidade de E-mail ou CPF
$query_duplicado = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario OR cpf = :cpf");
$query_duplicado->bindValue(":usuario", $usuario);
$query_duplicado->bindValue(":cpf", $cpf);
$query_duplicado->execute();

if ($query_duplicado->rowCount() > 0) {
    echo "Erro: Este E-mail ou CPF já está cadastrado!";
    exit();
}

// 6. INSERÇÃO COMPLETA: Incluindo telefone e endereço para evitar o erro de 'default value'
$res = $pdo->prepare("INSERT INTO usuarios (nome, cpf, telefone, usuario, endereco, senha, nivel) 
                      VALUES (:nome, :cpf, :telefone, :usuario, :endereco, :senha, :nivel)");

$res->bindValue(":nome", $nome);
$res->bindValue(":cpf", $cpf);
$res->bindValue(":telefone", $telefone);
$res->bindValue(":usuario", $usuario);
$res->bindValue(":endereco", $endereco);
$res->bindValue(":senha", $senha);
$res->bindValue(":nivel", $nivel);

if ($res->execute()) {
    // 7. Criação da graduação inicial
    $ultimo_id = $pdo->lastInsertId();
    $pdo->query("INSERT INTO graduacoes (usuario_id, cor_faixa, graus, total_aulas) VALUES ('$ultimo_id', 'Branca', 0, 0)");
    echo "Cadastrado com Sucesso!";
} else {
    echo "Erro ao processar o cadastro no banco de dados.";
}
