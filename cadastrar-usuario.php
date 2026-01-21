<?php
require_once("conexao.php");

$nome = $_POST['nome'];
$cpf = $_POST['cpf'];
$email = $_POST['email'];
$senha = $_POST['senha'];
$nivel = $_POST['nivel'];
$telefone = $_POST['telefone'];
$endereco = $_POST['endereco'];
$data_venc = @$_POST['data_venc'];
$id = $_POST['id'];

// VALIDAR E-MAIL DUPLICADO
$query = $pdo->query("SELECT * FROM usuarios WHERE email = '$email'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0 && $res[0]['id'] != $id) {
    echo 'E-mail já Cadastrado, escolha outro!';
    exit();
}

// VALIDAR CPF DUPLICADO
$query = $pdo->query("SELECT * FROM usuarios WHERE cpf = '$cpf'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0 && $res[0]['id'] != $id) {
    echo 'CPF já Cadastrado!';
    exit();
}

// SCRIPT PARA SUBIR FOTO NO SERVIDOR
$nome_img = date('d-m-Y-H-i-s') . '-' . @$_FILES['foto']['name'];
$nome_img = preg_replace('/[ :]+/', '-', $nome_img);
$caminho = "../img/perfil/" . $nome_img;

$imagem_temp = @$_FILES['foto']['tmp_name'];

if (@$_FILES['foto']['name'] != "") {
    $extensao = pathinfo($nome_img, PATHINFO_EXTENSION);
    if ($extensao == 'png' || $extensao == 'jpg' || $extensao == 'jpeg' || $extensao == 'gif') {
        move_uploaded_file($imagem_temp, $caminho);
    } else {
        echo 'Extensão de Imagem não permitida!';
        exit();
    }
} else {
    $nome_img = 'sem-perfil.jpg';
}

if ($id == "") {
    // INSERIR NOVO USUÁRIO
    $res = $pdo->prepare("INSERT INTO usuarios SET nome = :nome, cpf = :cpf, email = :email, senha = :senha, nivel = :nivel, telefone = :telefone, endereco = :endereco, foto = '$nome_img', ativo = 'Sim', data_venc = :data_venc, data_cad = curDate()");

    $res->bindValue(":data_venc", $data_venc);
} else {
    // EDITAR USUÁRIO EXISTENTE
    if (@$_FILES['foto']['name'] != "") {
        $res = $pdo->prepare("UPDATE usuarios SET nome = :nome, cpf = :cpf, email = :email, senha = :senha, nivel = :nivel, telefone = :telefone, endereco = :endereco, foto = '$nome_img', data_venc = :data_venc WHERE id = :id");
    } else {
        $res = $pdo->prepare("UPDATE usuarios SET nome = :nome, cpf = :cpf, email = :email, senha = :senha, nivel = :nivel, telefone = :telefone, endereco = :endereco, data_venc = :data_venc WHERE id = :id");
    }
    $res->bindValue(":id", $id);
    $res->bindValue(":data_venc", $data_venc);
}

$res->bindValue(":nome", $nome);
$res->bindValue(":cpf", $cpf);
$res->bindValue(":email", $email);
$res->bindValue(":senha", $senha);
$res->bindValue(":nivel", $nivel);
$res->bindValue(":telefone", $telefone);
$res->bindValue(":endereco", $endereco);
$res->execute();

// --- FUNCIONALIDADE DE AULA EXPERIMENTAL (CORRIGIDA) ---
if (@$_POST['experimental'] == 'Sim' && $id == "") {
    $ultimo_id = $pdo->lastInsertId();
    $hoje = date('Y-m-d');

    // Mapeamento exato para as colunas: aluno_id, professor_id, tema, data, observacoes
    $res_exp = $pdo->prepare("INSERT INTO aulas_particulares SET 
        aluno_id = :aluno, 
        professor_id = '0', 
        data = :data, 
        tema = 'Aula Experimental', 
        horario = '00:00',
        observacoes = 'EXPERIMENTAL'");

    $res_exp->bindValue(":aluno", $ultimo_id);
    $res_exp->bindValue(":data", $hoje);
    $res_exp->execute();
}

echo 'Salvo com Sucesso';
