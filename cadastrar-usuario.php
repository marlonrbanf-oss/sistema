<?php
@session_start();
// Caminho corrigido para funcionar tanto local quanto no servidor Railway
if (file_exists("conexao.php")) {
    require_once("conexao.php");
} else {
    require_once("../conexao.php");
}

// Captura de variáveis com proteção para campos não preenchidos
$nome = $_POST['nome'];
$cpf = $_POST['cpf'];
$email = $_POST['email']; // Este é o valor vindo do formulário
$senha = $_POST['senha'];
$nivel = isset($_POST['nivel']) ? $_POST['nivel'] : 'Cliente';
$telefone = $_POST['telefone'];
$endereco = $_POST['endereco'];
$data_venc = @$_POST['data_venc'];
$id = $_POST['id'];

// --- CORREÇÃO: No seu banco a coluna chama-se 'usuario' em vez de 'email' ---

// VALIDAR USUÁRIO (E-MAIL) DUPLICADO
$query = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
$query->bindValue(":usuario", $email);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0 && $res[0]['id'] != $id) {
    echo 'E-mail (Usuário) já Cadastrado, escolha outro!';
    exit();
}

// VALIDAR CPF DUPLICADO
$query = $pdo->prepare("SELECT * FROM usuarios WHERE cpf = :cpf");
$query->bindValue(":cpf", $cpf);
$query->execute();
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
    // Se for edição e não mudar a foto, mantém a antiga. Se for novo, usa padrão.
    if ($id == "") {
        $nome_img = 'sem-perfil.jpg';
    } else {
        // Busca a foto atual para não sobrescrever com 'sem-perfil' ao editar sem subir foto nova
        $query_foto = $pdo->query("SELECT foto FROM usuarios WHERE id = '$id'");
        $res_foto = $query_foto->fetch(PDO::FETCH_ASSOC);
        $nome_img = $res_foto['foto'];
    }
}

// --- SALVAMENTO NO BANCO (Nomes de colunas conforme sua imagem do Railway) ---

if ($id == "") {
    // INSERIR NOVO USUÁRIO (Coluna 'usuario' recebe o $email do form)
    $res = $pdo->prepare("INSERT INTO usuarios SET nome = :nome, cpf = :cpf, usuario = :usuario, senha = :senha, nivel = :nivel, telefone = :telefone, endereco = :endereco, foto = :foto, ativo = 'Sim', data_venc = :data_venc, data_cad = curDate()");
} else {
    // EDITAR USUÁRIO EXISTENTE
    $res = $pdo->prepare("UPDATE usuarios SET nome = :nome, cpf = :cpf, usuario = :usuario, senha = :senha, nivel = :nivel, telefone = :telefone, endereco = :endereco, foto = :foto, data_venc = :data_venc WHERE id = :id");
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

// --- FUNCIONALIDADE DE AULA EXPERIMENTAL (Tabela: aulas_particulares) ---
if (@$_POST['experimental'] == 'Sim' && $id == "") {
    $ultimo_id = $pdo->lastInsertId();
    $hoje = date('Y-m-d');

    // Colunas confirmadas na sua imagem: aluno_id, professor_id, tema, data, observacoes
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
