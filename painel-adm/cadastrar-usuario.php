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

// VARIÁVEIS DE GRADUAÇÃO (Vindas do Painel ADM)
$cor_faixa = isset($_POST['cor_faixa']) ? $_POST['cor_faixa'] : 'Branca';
$graus = isset($_POST['graus']) ? $_POST['graus'] : 0;
$total_aulas = isset($_POST['total_aulas']) ? $_POST['total_aulas'] : 0;

// 2. VALIDAÇÃO DE USUÁRIO (E-MAIL) - Usando coluna 'usuario'
$query = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
$query->bindValue(":usuario", $email);
$query->execute();
$res_usu = $query->fetchAll(PDO::FETCH_ASSOC);
if (count($res_usu) > 0 && ($id == "" || $res_usu[0]['id'] != $id)) {
    echo 'Este E-mail/Usuário já está cadastrado!';
    exit();
}

// 3. VALIDAÇÃO DE CPF
if ($cpf != "") {
    $query = $pdo->prepare("SELECT * FROM usuarios WHERE cpf = :cpf");
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
    $query_f = $pdo->prepare("SELECT foto FROM usuarios WHERE id = :id");
    $query_f->bindValue(":id", $id);
    $query_f->execute();
    $res_f = $query_f->fetch(PDO::FETCH_ASSOC);
    if ($res_f) $nome_img = $res_f['foto'];
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

// CAPTURA O ID (Se for novo cadastro ou edição)
$ultimo_id = ($id == "") ? $pdo->lastInsertId() : $id;

// 6. GERENCIAMENTO DE GRADUAÇÃO (Apenas se for Nível Cliente)
if ($nivel == 'Cliente') {
    // Verifica se já existe graduação para este usuário
    $query_g = $pdo->prepare("SELECT id FROM graduacoes WHERE usuario_id = :id");
    $query_g->bindValue(":id", $ultimo_id);
    $query_g->execute();

    if ($query_g->rowCount() == 0) {
        // Novo Registro de Graduação
        $res_grad = $pdo->prepare("INSERT INTO graduacoes SET 
            usuario_id = :id, 
            cor_faixa = :cor, 
            graus = :graus, 
            total_aulas = :aulas");
    } else {
        // Atualização (Apenas se quem está salvando for Admin/Professor/Balconista)
        if (@$_SESSION['nivel_usuario'] == 'Admin' || @$_SESSION['nivel_usuario'] == 'Professor' || @$_SESSION['nivel_usuario'] == 'Balconista') {
            $res_grad = $pdo->prepare("UPDATE graduacoes SET 
                cor_faixa = :cor, 
                graus = :graus, 
                total_aulas = :aulas 
                WHERE usuario_id = :id");
        }
    }

    // Só executa se for um insert novo ou se o nível logado permitir o update
    if (isset($res_grad)) {
        $res_grad->bindValue(":id", $ultimo_id);
        $res_grad->bindValue(":cor", $cor_faixa);
        $res_grad->bindValue(":graus", $graus);
        $res_grad->bindValue(":aulas", $total_aulas);
        $res_grad->execute();
    }
}



echo 'Salvo com Sucesso';
