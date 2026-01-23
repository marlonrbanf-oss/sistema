<?php
@session_start();
require_once("../conexao.php"); // Caminho correto para a raiz

// 1. CAPTURA DE VARIÁVEIS E LIMPEZA DE MÁSCARAS
$id = isset($_POST['id']) ? $_POST['id'] : '';
$nome = isset($_POST['nome']) ? $_POST['nome'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : ''; // Campo 'email' do formulário
$senha = isset($_POST['senha']) ? $_POST['senha'] : '';
$nivel = isset($_POST['nivel']) ? $_POST['nivel'] : 'Cliente';

// Limpeza de CPF e Telefone para evitar o erro "Data too long"
$cpf = isset($_POST['cpf']) ? preg_replace('/[^0-9]/', '', $_POST['cpf']) : '';
$telefone = isset($_POST['telefone']) ? preg_replace('/[^0-9]/', '', $_POST['telefone']) : '';

// CAMPOS DE GRADUAÇÃO (Vindos do Painel ADM)
$cor_faixa = isset($_POST['cor_faixa']) ? $_POST['cor_faixa'] : 'Branca';
$graus = isset($_POST['graus']) ? $_POST['graus'] : 0;
$total_aulas = isset($_POST['total_aulas']) ? $_POST['total_aulas'] : 0;

// 2. VALIDAÇÃO DE USUÁRIO
$query = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
$query->bindValue(":usuario", $email);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($res) > 0 && ($id == "" || $res[0]['id'] != $id)) {
    echo 'Este E-mail/Usuário já está cadastrado!';
    exit();
}

// 3. INSERIR OU ATUALIZAR USUÁRIO
if ($id == "") {
    $res = $pdo->prepare("INSERT INTO usuarios SET 
        nome = :nome, 
        cpf = :cpf, 
        usuario = :usuario, 
        senha = :senha, 
        nivel = :nivel, 
        telefone = :telefone, 
        ativo = 'Sim', 
        foto = 'sem-perfil.jpg'");
} else {
    $res = $pdo->prepare("UPDATE usuarios SET 
        nome = :nome, 
        cpf = :cpf, 
        usuario = :usuario, 
        senha = :senha, 
        nivel = :nivel, 
        telefone = :telefone 
        WHERE id = :id");
    $res->bindValue(":id", $id);
}

$res->bindValue(":nome", $nome);
$res->bindValue(":cpf", $cpf);
$res->bindValue(":usuario", $email);
$res->bindValue(":senha", $senha);
$res->bindValue(":nivel", $nivel);
$res->bindValue(":telefone", $telefone);
$res->execute();

$ultimo_id = ($id == "") ? $pdo->lastInsertId() : $id;

// 4. ATUALIZAR TABELA GRADUAÇÕES (Apenas para Clientes)
if ($nivel == 'Cliente') {
    // Verifica se já existe registro de graduação
    $query_g = $pdo->prepare("SELECT id FROM graduacoes WHERE usuario_id = :id");
    $query_g->bindValue(":id", $ultimo_id);
    $query_g->execute();

    if ($query_g->rowCount() == 0) {
        $res_grad = $pdo->prepare("INSERT INTO graduacoes SET 
            usuario_id = :id, 
            cor_faixa = :cor, 
            graus = :graus, 
            total_aulas = :aulas");
    } else {
        $res_grad = $pdo->prepare("UPDATE graduacoes SET 
            cor_faixa = :cor, 
            graus = :graus, 
            total_aulas = :aulas 
            WHERE usuario_id = :id");
    }

    $res_grad->bindValue(":id", $ultimo_id);
    $res_grad->bindValue(":cor", $cor_faixa);
    $res_grad->bindValue(":graus", $graus);
    $res_grad->bindValue(":aulas", $total_aulas);
    $res_grad->execute();
}

echo 'Salvo com Sucesso';
