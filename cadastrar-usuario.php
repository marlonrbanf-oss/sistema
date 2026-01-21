<?php
@session_start();

// Gerenciamento de caminho do arquivo de conexão
if (file_exists("conexao.php")) {
    require_once("conexao.php");
} else {
    require_once("../conexao.php");
}

// 1. CAPTURA DE VARIÁVEIS DO FORMULÁRIO
$nome = isset($_POST['nome']) ? $_POST['nome'] : '';
$cpf = isset($_POST['cpf']) ? $_POST['cpf'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$senha = isset($_POST['senha']) ? $_POST['senha'] : '';
$nivel = isset($_POST['nivel']) ? $_POST['nivel'] : 'Cliente';
$telefone = isset($_POST['telefone']) ? $_POST['telefone'] : '';
$endereco = isset($_POST['endereco']) ? $_POST['endereco'] : '';
$data_venc = (isset($_POST['data_venc']) && $_POST['data_venc'] != "") ? $_POST['data_venc'] : null;
$id = isset($_POST['id']) ? $_POST['id'] : '';

// 2. VALIDAÇÃO DE USUÁRIO (E-MAIL) - Usando coluna 'usuario'
$query = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
$query->bindValue(":usuario", $email);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if (count($res) > 0 && ($id == "" || $res[0]['id'] != $id)) {
    echo 'Este E-mail/Usuário já está cadastrado!';
    exit();
}

// 3. VALIDAÇÃO DE CPF
$query = $pdo->prepare("SELECT * FROM usuarios WHERE cpf = :cpf");
$query->bindValue(":cpf", $cpf);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if (count($res) > 0 && ($id == "" || $res[0]['id'] != $id)) {
    echo 'Este CPF já está cadastrado!';
    exit();
}

// 4. TRATAMENTO DA FOTO (Upload)
$nome_img = 'sem-perfil.jpg';
if (isset($_FILES['foto']) && $_FILES['foto']['name'] != "") {
    $nome_img = date('d-m-Y-H-i-s') . '-' . $_FILES['foto']['name'];
    $nome_img = preg_replace('/[ :]+/', '-', $nome_img);
    $caminho = "../img/perfil/" . $nome_img;

    $extensao = pathinfo($nome_img, PATHINFO_EXTENSION);
    if (in_array(strtolower($extensao), ['png', 'jpg', 'jpeg', 'gif'])) {
        move_uploaded_file($_FILES['foto']['tmp_name'], $caminho);
    } else {
        echo 'Extensão de imagem não permitida!';
        exit();
    }
} elseif ($id != "") {
    // Se for edição sem foto nova, busca a atual para manter
    $query_f = $pdo->prepare("SELECT foto FROM usuarios WHERE id = :id");
    $query_f->bindValue(":id", $id);
    $query_f->execute();
    $res_f = $query_f->fetch(PDO::FETCH_ASSOC);
    if ($res_f) $nome_img = $res_f['foto'];
}

// 5. SALVAMENTO NA TABELA USUARIOS (Ajustado para seu banco)
if ($id == "") {
    $res = $pdo->prepare("INSERT INTO usuarios SET 
        nome = :nome, 
        cpf = :cpf, 
        usuario = :usuario, 
        senha = :senha, 
        nivel = :nivel, 
        telefone = :telefone, 
        endereco = :endereco, 
        foto = :foto, 
        ativo = 'Sim', 
        data_venc = :data_venc");
} else {
    $res = $pdo->prepare("UPDATE usuarios SET 
        nome = :nome, 
        cpf = :cpf, 
        usuario = :usuario, 
        senha = :senha, 
        nivel = :nivel, 
        telefone = :telefone, 
        endereco = :endereco, 
        foto = :foto, 
        data_venc = :data_venc 
        WHERE id = :id");
    $res->bindValue(":id", $id);
}

$res->bindValue(":nome", $nome);
$res->bindValue(":cpf", $cpf);
$res->bindValue(":usuario", $email);
$res->bindValue(":senha", $senha);
$res->bindValue(":nivel", $nivel);
$res->bindValue(":telefone", $telefone);
$res->bindValue(":endereco", $endereco);
$res->bindValue(":foto", $nome_img);
$res->bindValue(":data_venc", $data_venc);
$res->execute();

// 6. AULA EXPERIMENTAL (Sincronizado com tabela: aulas_particulares)
if (isset($_POST['experimental']) && $_POST['experimental'] == 'Sim' && $id == "") {
    $ultimo_id = $pdo->lastInsertId();
    $hoje = date('Y-m-d');

    // CORREÇÃO: Removida a coluna 'horario' que não existe na sua tabela
    $res_exp = $pdo->prepare("INSERT INTO aulas_particulares SET 
        aluno_id = :aluno, 
        professor_id = '0', 
        data = :data, 
        tema = 'Aula Experimental', 
        observacoes = 'EXPERIMENTAL'");

    $res_exp->bindValue(":aluno", $ultimo_id);
    $res_exp->bindValue(":data", $hoje);
    $res_exp->execute();
}

echo 'Salvo com Sucesso';
