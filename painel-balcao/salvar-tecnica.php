<?php
require_once("../conexao.php");
@session_start();

// 1. VALIDAÇÃO DE SEGURANÇA
// Garante que apenas usuários autorizados possam salvar técnicas
if (@$_SESSION['nivel_usuario'] != 'Balconista' && @$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// 2. Recebe os dados do formulário
if (empty($_POST['id_aluno']) || empty($_POST['tecnica'])) {
    echo "<script language='javascript'>window.location='usuarios.php'; </script>";
    exit();
}

$id_aluno = $_POST['id_aluno'];
$tecnica = $_POST['tecnica'];
$nivel = $_POST['nivel'];
$status = $_POST['status'];
$hoje = date('Y-m-d');

try {
    // 3. Prepara a inserção na tabela 'habilidades'
    $query = $pdo->prepare("INSERT INTO habilidades (usuario_id, tecnica, nivel, status, data_conquista) 
                            VALUES (:usuario_id, :tecnica, :nivel, :status, :data_conquista)");

    // 4. Vincula os parâmetros
    $query->bindValue(":usuario_id", $id_aluno);
    $query->bindValue(":tecnica", $tecnica);
    $query->bindValue(":nivel", $nivel);
    $query->bindValue(":status", $status);
    $query->bindValue(":data_conquista", $hoje);

    $query->execute();

    // 5. Feedback e retorno
    echo "<script language='javascript'>
            alert('Selo de técnica lançado com sucesso!');
            window.location='usuarios.php';
          </script>";
} catch (PDOException $e) {
    echo "Erro ao salvar no banco de dados: " . $e->getMessage();
}
