<?php
@session_start();
// Ajuste de caminho para o ambiente Railway
if (file_exists("conexao.php")) {
    require_once("conexao.php");
} else {
    require_once("../conexao.php");
}

// 1. CAPTURA DE VARIÁVEIS COM TRATAMENTO (Resolve o Warning: Undefined array key)
$nome = isset($_POST['nome']) ? $_POST['nome'] : '';
$cpf = isset($_POST['cpf']) ? $_POST['cpf'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : ''; // Valor do e-mail vindo do formulário
$senha = isset($_POST['senha']) ? $_POST['senha'] : '';
$nivel = isset($_POST['nivel']) ? $_POST['nivel'] : 'Cliente';
$telefone = isset($_POST['telefone']) ? $_POST['telefone'] : '';
$endereco = isset($_POST['endereco']) ? $_POST['endereco'] : '';
$data_venc = (isset($_POST['data_venc']) && $_POST['data_venc'] != "") ? $_POST['data_venc'] : null;
$id = isset($_POST['id']) ? $_POST['id'] : '';

// 2. VALIDAÇÃO DE USUÁRIO (E-MAIL) DUPLICADO (Coluna 'usuario' do seu banco)
$query = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
$query->bindValue(":usuario", $email);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if (count($res) > 0 && ($id == "" || $res[0]['id'] != $id)) {
    echo 'Este E-mail/Usuário já está cadastrado!';
    exit();
}

// 3. VALIDAÇÃO DE CPF DUPLICADO
$query = $pdo->prepare("SELECT * FROM usuarios WHERE cpf = :cpf");
$query->bindValue(":cpf", $cpf);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if (count($res) > 0 && ($id == "" || $res[0]['id'] != $id)) {
    echo 'Este CPF já está cadastrado!';
    exit();
}

// 4. TRATAMENTO DA FOTO
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
    // Se for edição e não enviou foto nova, mantém a atual
    $query_f = $pdo->prepare("SELECT foto FROM usuarios WHERE id = :id");
    $query_f->bindValue(":id", $id);
    $query_f->execute();
    $res_f = $query_f->fetch(PDO::FETCH_ASSOC);
    $nome_img = $res_f['foto'];
}

// 5. SALVAMENTO (Removido 'data_cad' que não existe no seu banco)
if ($id == "") {
    // Cadastro de Novo Usuário
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
    // Edição de Usuário Existente
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

// 6. AULA EXPERIMENTAL (Sincronizada com tabela: aulas_particulares)
if (isset($_POST['experimental']) && $_POST['experimental'] == 'Sim' && $id == "") {
    $ultimo_id = $pdo->lastInsertId();
    $hoje = date('Y-m-d');

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
