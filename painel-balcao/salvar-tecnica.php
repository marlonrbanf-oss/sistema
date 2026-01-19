<?php
require_once("../conexao.php");
@session_start();

// 1. Recebe os dados do formulário
$id_aluno = $_POST['id_aluno'];
$tecnica = $_POST['tecnica'];
$nivel = $_POST['nivel'];
$status = $_POST['status'];
$hoje = date('Y-m-d');

try {
    // 2. Prepara a inserção na tabela 'habilidades' conforme sua estrutura
    $query = $pdo->prepare("INSERT INTO habilidades (usuario_id, tecnica, nivel, status, data_conquista) 
                            VALUES (:usuario_id, :tecnica, :nivel, :status, :data_conquista)");

    // 3. Vincula os parâmetros corretamente
    $query->bindValue(":usuario_id", $id_aluno);
    $query->bindValue(":tecnica", $tecnica);
    $query->bindValue(":nivel", $nivel);
    $query->bindValue(":status", $status);
    $query->bindValue(":data_conquista", $hoje);

    $query->execute();

    // 4. Feedback e retorno
    echo "<script language='javascript'>
            alert('Selo de técnica lançado com sucesso!');
            window.location='usuarios.php';
          </script>";
} catch (PDOException $e) {
    echo "Erro ao salvar no banco de dados: " . $e->getMessage();
}
