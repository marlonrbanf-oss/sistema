<?php
require_once("../conexao.php");
@session_start();

// 1. Receber e Limpar Dados
$id = isset($_POST['id']) ? $_POST['id'] : '';
$nome = isset($_POST['nome']) ? $_POST['nome'] : '';
$cpf = isset($_POST['cpf']) ? $_POST['cpf'] : '';
$usuario = isset($_POST['usuario']) ? $_POST['usuario'] : '';
$senha = isset($_POST['senha']) ? $_POST['senha'] : '';
$nivel = isset($_POST['nivel']) ? $_POST['nivel'] : '';

// IMPORTANTE: Normalizar para minúsculo para bater com o mapa de cores do index.php
$cor_faixa = isset($_POST['cor_faixa']) ? mb_strtolower(trim($_POST['cor_faixa'])) : 'branca';
$graus = isset($_POST['graus']) ? (int)$_POST['graus'] : 0;

// 2. Validações Básicas
if ($cpf == '' || $nome == '' || $usuario == '') {
    echo "<script>alert('Campos obrigatórios preenchidos!'); history.back();</script>";
    exit();
}

// 3. Validar Duplicidade de E-mail
$query = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario AND id != :id");
$query->bindValue(":usuario", $usuario);
$query->bindValue(":id", $id);
$query->execute();
if ($query->rowCount() > 0) {
    echo "<script>alert('Este e-mail já está cadastrado!'); history.back();</script>";
    exit();
}

// 4. Inserir ou Atualizar na Tabela USUARIOS
if ($id == "" || $id == 0) {
    $res = $pdo->prepare("INSERT INTO usuarios (nome, cpf, usuario, senha, nivel, ativo) VALUES (:nome, :cpf, :usuario, :senha, :nivel, 'Sim')");
} else {
    $res = $pdo->prepare("UPDATE usuarios SET nome = :nome, cpf = :cpf, usuario = :usuario, senha = :senha, nivel = :nivel WHERE id = :id");
    $res->bindValue(":id", $id);
}

$res->bindValue(":nome", $nome);
$res->bindValue(":cpf", $cpf);
$res->bindValue(":usuario", $usuario);
$res->bindValue(":senha", $senha);
$res->bindValue(":nivel", $nivel);
$res->execute();

$id_usuario = ($id == "" || $id == 0) ? $pdo->lastInsertId() : $id;

// 5. Gerenciar Tabela de Graduações (PONTO CRÍTICO)
if ($nivel == 'Cliente') {
    // Verifica se já existe
    $query_g = $pdo->prepare("SELECT id FROM graduacoes WHERE usuario_id = :user_id");
    $query_g->bindValue(":user_id", $id_usuario);
    $query_g->execute();

    if ($query_g->rowCount() > 0) {
        $res_f = $pdo->prepare("UPDATE graduacoes SET cor_faixa = :faixa, graus = :graus WHERE usuario_id = :user_id");
    } else {
        $res_f = $pdo->prepare("INSERT INTO graduacoes (usuario_id, cor_faixa, graus) VALUES (:user_id, :faixa, :graus)");
    }

    $res_f->bindValue(":user_id", $id_usuario);
    $res_f->bindValue(":faixa", $cor_faixa);
    $res_f->bindValue(":graus", $graus);
    $res_f->execute();
}

// 6. Atualizar Sessão se for o próprio usuário logado
if (isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] == $id_usuario) {
    $_SESSION['nome_usuario'] = $nome;
    $_SESSION['nivel_usuario'] = $nivel;
}

echo "<script>alert('Salvo com Sucesso!'); window.location='usuarios.php';</script>";
