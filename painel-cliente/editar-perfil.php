<?php
require_once("../conexao.php");
@session_start();

$nome = $_POST['nome'];
$telefone = $_POST['telefone'];
$usuario = $_POST['email']; // Adicionei para atualizar o e-mail também
$id = $_SESSION['id_usuario']; // Pega o ID direto da sessão por segurança

// 1. Processamento da Imagem
$query = $pdo->query("SELECT foto FROM usuarios WHERE id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$foto_antiga = $res[0]['foto'];

$nome_img = "";
if (@$_FILES['foto']['name'] != "") {
    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    if (in_array(strtolower($ext), ['png', 'jpg', 'jpeg', 'gif'])) {

        // Nome único para a imagem
        $nome_img = date('d-m-Y-H-i-s') . '-' . $id . '.' . $ext;
        $caminho = '../images/perfil/' . $nome_img;

        $temp = $_FILES['foto']['tmp_name'];
        if (move_uploaded_file($temp, $caminho)) {
            // Se subiu a nova, apaga a antiga para não encher o servidor (opcional)
            if ($foto_antiga != "usuario-icone-claro.png" && $foto_antiga != "") {
                @unlink('../images/perfil/' . $foto_antiga);
            }
        } else {
            $nome_img = ""; // Falha no upload
        }
    }
}

// 2. Atualização no Banco de Dados
if ($nome_img != "") {
    $res = $pdo->prepare("UPDATE usuarios SET nome = :nome, telefone = :telefone, usuario = :usuario, foto = :foto WHERE id = :id");
    $res->bindValue(":foto", $nome_img);
} else {
    $res = $pdo->prepare("UPDATE usuarios SET nome = :nome, telefone = :telefone, usuario = :usuario WHERE id = :id");
}

$res->bindValue(":nome", $nome);
$res->bindValue(":telefone", $telefone);
$res->bindValue(":usuario", $usuario);
$res->bindValue(":id", $id);
$res->execute();

// 3. Atualiza as variáveis de sessão para refletir a mudança na hora
$_SESSION['nome_usuario'] = $nome;

echo "Salvo com Sucesso";
