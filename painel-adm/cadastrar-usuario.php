<?php
@session_start();

// Gerenciamento de caminho do arquivo de conexão
if (file_exists("conexao.php")) {
    require_once("conexao.php");
} else {
    require_once("../conexao.php");
}

// 1. CAPTURA DE VARIÁVEIS DO FORMULÁRIO E LIMPEZA
$id = isset($_POST['id']) ? $_POST['id'] : '';
$nome = isset($_POST['nome']) ? $_POST['nome'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$senha = isset($_POST['senha']) ? $_POST['senha'] : '';
$nivel = isset($_POST['nivel']) ? $_POST['nivel'] : 'Cliente';
$endereco = isset($_POST['endereco']) ? $_POST['endereco'] : '';

// Limpeza de máscaras (remove tudo que não for número)
$cpf = isset($_POST['cpf']) ? preg_replace('/[^0-9]/', '', $_POST['cpf']) : '';
$telefone = isset($_POST['telefone']) ? preg_replace('/[^0-9]/', '', $_POST['telefone']) : '';

// Tratamento de data nula
$data_venc = (isset($_POST['data_venc']) && $_POST['data_venc'] != "") ? $_POST['data_venc'] : null;

// VARIÁVEIS DE GRADUAÇÃO
$cor_faixa = isset($_POST['cor_faixa']) ? $_POST['cor_faixa'] : 'Branca';
$graus = isset($_POST['graus']) ? (int)$_POST['graus'] : 0;
$total_aulas = isset($_POST['total_aulas']) ? (int)$_POST['total_aulas'] : 0;

// 2. VALIDAÇÃO DE USUÁRIO (E-MAIL)
$query = $pdo->prepare("SELECT id, usuario FROM usuarios WHERE usuario = :usuario");
$query->bindValue(":usuario", $email);
$query->execute();
$res_usu = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($res_usu) > 0 && ($id == "" || $res_usu[0]['id'] != $id)) {
    echo 'Este E-mail/Usuário já está cadastrado!';
    exit();
}

// 3. VALIDAÇÃO DE CPF (Se preenchido)
if ($cpf != "") {
    $query = $pdo->prepare("SELECT id FROM usuarios WHERE cpf = :cpf");
    $query->bindValue(":cpf", $cpf);
    $query->execute();
    $res_cpf = $query->fetchAll(PDO::FETCH_ASSOC);
    if (count($res_cpf) > 0 && ($id == "" || $res_cpf[0]['id'] != $id)) {
        echo 'Este CPF já está cadastrado!';
        exit();
    }
}

// 4. TRATAMENTO DA FOTO (Upload)
$nome_img = 'sem-perfil.jpg';

// Se for edição, busca a foto atual para não sobrescrever com o padrão caso não envie uma nova
if ($id != "") {
    $query_f = $pdo->prepare("SELECT foto FROM usuarios WHERE id = :id");
    $query_f->bindValue(":id", $id);
    $query_f->execute();
    $res_f = $query_f->fetch(PDO::FETCH_ASSOC);
    if ($res_f) {
        $nome_img = $res_f['foto'];
    }
}

// Processamento do Upload
if (isset($_FILES['foto']) && $_FILES['foto']['name'] != "") {
    $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    if (in_array(strtolower($extensao), ['png', 'jpg', 'jpeg', 'gif'])) {

        // Remove foto antiga se não for a padrão
        if ($nome_img != "sem-perfil.jpg" && file_exists("../img/perfil/" . $nome_img)) {
            @unlink("../img/perfil/" . $nome_img);
        }

        $nome_img = date('d-m-Y-H-i-s') . '-' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['foto']['name']);
        $caminho = "../img/perfil/" . $nome_img;
        move_uploaded_file($_FILES['foto']['tmp_name'], $caminho);
    } else {
        echo 'Extensão de imagem não permitida!';
        exit();
    }
}

// 5. SALVAMENTO NA TABELA USUARIOS
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

$ultimo_id = ($id == "") ? $pdo->lastInsertId() : $id;

// 6. GERENCIAMENTO DE GRADUAÇÃO (Apenas nível Cliente/Aluno)
if ($nivel == 'Cliente' || $nivel == 'Aluno') {
    $query_g = $pdo->prepare("SELECT id FROM graduacoes WHERE usuario_id = :id");
    $query_g->bindValue(":id", $ultimo_id);
    $query_g->execute();

    $res_grad = null;

    if ($query_g->rowCount() == 0) {
        $res_grad = $pdo->prepare("INSERT INTO graduacoes SET 
            usuario_id = :id, 
            cor_faixa = :cor, 
            graus = :graus, 
            total_aulas = :aulas");
    } else {
        // Apenas Admin, Professor ou Balconista podem atualizar a faixa de um usuário existente
        $nivel_sessao = isset($_SESSION['nivel_usuario']) ? $_SESSION['nivel_usuario'] : '';
        if (in_array($nivel_sessao, ['Admin', 'Professor', 'Balconista'])) {
            $res_grad = $pdo->prepare("UPDATE graduacoes SET 
                cor_faixa = :cor, 
                graus = :graus, 
                total_aulas = :aulas 
                WHERE usuario_id = :id");
        }
    }

    if ($res_grad) {
        $res_grad->bindValue(":id", $ultimo_id);
        $res_grad->bindValue(":cor", $cor_faixa);
        $res_grad->bindValue(":graus", $graus);
        $res_grad->bindValue(":aulas", $total_aulas);
        $res_grad->execute();
    }
}

echo 'Salvo com Sucesso';
