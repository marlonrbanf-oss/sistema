<?php
require_once("../conexao.php");
@session_start();

// 1. Receber e Limpar Dados
$id = isset($_POST['id']) ? $_POST['id'] : '';
$nome = isset($_POST['nome']) ? $_POST['nome'] : '';
$cpf = isset($_POST['cpf']) ? $_POST['cpf'] : '';
$usuario = isset($_POST['usuario']) ? $_POST['usuario'] : ''; // E-mail/Login
$senha = isset($_POST['senha']) ? $_POST['senha'] : '';
$nivel = isset($_POST['nivel']) ? $_POST['nivel'] : 'Cliente';
$telefone = isset($_POST['telefone']) ? $_POST['telefone'] : '';
$data_venc = (isset($_POST['data_venc']) && $_POST['data_venc'] != "") ? $_POST['data_venc'] : null;

// Dados de Graduação (vindos do ADM)
$cor_faixa = isset($_POST['cor_faixa']) ? mb_strtolower(trim($_POST['cor_faixa'])) : 'branca';
$graus = isset($_POST['graus']) ? (int)$_POST['graus'] : 0;
$total_aulas = isset($_POST['total_aulas']) ? (int)$_POST['total_aulas'] : 0;

// 2. Validações Básicas
if ($nome == '' || $usuario == '') {
    echo "<script>alert('Nome e E-mail são obrigatórios!'); history.back();</script>";
    exit();
}

// 3. Validar Duplicidade de E-mail (coluna 'usuario' no seu banco)
$query = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario AND id != :id");
$query->bindValue(":usuario", $usuario);
$query->bindValue(":id", $id);
$query->execute();
if ($query->rowCount() > 0) {
    echo "<script>alert('Este e-mail/usuário já está cadastrado!'); history.back();</script>";
    exit();
}

// 4. Inserir ou Atualizar na Tabela USUARIOS
if ($id == "" || $id == 0) {
    $res = $pdo->prepare("INSERT INTO usuarios SET 
        nome = :nome, 
        cpf = :cpf, 
        usuario = :usuario, 
        senha = :senha, 
        nivel = :nivel, 
        telefone = :telefone, 
        data_venc = :data_venc, 
        ativo = 'Sim', 
        foto = 'sem-perfil.jpg'");
} else {
    $res = $pdo->prepare("UPDATE usuarios SET 
        nome = :nome, 
        cpf = :cpf, 
        usuario = :usuario, 
        senha = :senha, 
        nivel = :nivel, 
        telefone = :telefone, 
        data_venc = :data_venc 
        WHERE id = :id");
    $res->bindValue(":id", $id);
}

$res->bindValue(":nome", $nome);
$res->bindValue(":cpf", $cpf);
$res->bindValue(":usuario", $usuario);
$res->bindValue(":senha", $senha);
$res->bindValue(":nivel", $nivel);
$res->bindValue(":telefone", $telefone);
$res->bindValue(":data_venc", $data_venc);
$res->execute();

$id_usuario = ($id == "" || $id == 0) ? $pdo->lastInsertId() : $id;

// 5. Gerenciar Tabela de Graduações (Apenas para Clientes)
if ($nivel == 'Cliente') {
    $query_g = $pdo->prepare("SELECT id FROM graduacoes WHERE usuario_id = :user_id");
    $query_g->bindValue(":user_id", $id_usuario);
    $query_g->execute();

    if ($query_g->rowCount() > 0) {
        $res_f = $pdo->prepare("UPDATE graduacoes SET cor_faixa = :faixa, graus = :graus, total_aulas = :aulas WHERE usuario_id = :user_id");
    } else {
        $res_f = $pdo->prepare("INSERT INTO graduacoes SET usuario_id = :user_id, cor_faixa = :faixa, graus = :graus, total_aulas = :aulas");
    }

    $res_f->bindValue(":user_id", $id_usuario);
    $res_f->bindValue(":faixa", $cor_faixa);
    $res_f->bindValue(":graus", $graus);
    $res_f->bindValue(":aulas", $total_aulas);
    $res_f->execute();
}

// 6. Aula Experimental (Apenas se marcado e se for novo cadastro)
if (isset($_POST['experimental']) && $_POST['experimental'] == 'Sim' && ($id == "" || $id == 0)) {
    $hoje = date('Y-m-d');
    $res_exp = $pdo->prepare("INSERT INTO aulas_particulares SET 
        aluno_id = :aluno, 
        professor_id = '0', 
        data = :data, 
        tema = 'Aula Experimental', 
        observacoes = 'EXPERIMENTAL'");
    $res_exp->bindValue(":aluno", $id_usuario);
    $res_exp->bindValue(":data", $hoje);
    $res_exp->execute();
}

// 7. Atualizar Sessão se for o próprio usuário logado
if (isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] == $id_usuario) {
    $_SESSION['nome_usuario'] = $nome;
}

echo "<script>alert('Salvo com Sucesso!'); window.location='usuarios.php';</script>";
