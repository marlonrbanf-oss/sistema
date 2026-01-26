<?php
require_once("../conexao.php");
@session_start();

// 1. Validação de Segurança (Apenas quem ensina ou administra pode lançar técnicas)
$nivel_logado = isset($_SESSION['nivel_usuario']) ? $_SESSION['nivel_usuario'] : '';
if ($nivel_logado != 'Admin' && $nivel_logado != 'Professor') {
    echo "Acesso negado! Você não tem permissão para lançar técnicas.";
    exit();
}

// 2. Recebe e sanitiza os dados do formulário
$id_aluno = isset($_POST['id_aluno']) ? $_POST['id_aluno'] : '';
$tecnica  = isset($_POST['tecnica']) ? trim($_POST['tecnica']) : '';
$nivel    = isset($_POST['nivel']) ? trim($_POST['nivel']) : '';
$status   = isset($_POST['status']) ? trim($_POST['status']) : 'Concluído';
$hoje     = date('Y-m-d');

// Validação básica de preenchimento
if ($id_aluno == "" || $tecnica == "") {
    echo "Erro: O aluno e a técnica devem ser informados!";
    exit();
}

try {
    // 3. Verificação de Duplicidade (Opcional: evita lançar a mesma técnica para o mesmo aluno hoje)
    $check = $pdo->prepare("SELECT id FROM habilidades WHERE usuario_id = :u AND tecnica = :t AND data_conquista = :d");
    $check->bindValue(":u", $id_aluno);
    $check->bindValue(":t", $tecnica);
    $check->bindValue(":d", $hoje);
    $check->execute();

    if ($check->rowCount() > 0) {
        echo "<script>alert('Este aluno já recebeu este selo hoje!'); window.history.back();</script>";
        exit();
    }

    // 4. Prepara a inserção na tabela 'habilidades'
    $query = $pdo->prepare("INSERT INTO habilidades SET 
                            usuario_id = :usuario_id, 
                            tecnica = :tecnica, 
                            nivel = :nivel, 
                            status = :status, 
                            data_conquista = :data_conquista");

    // 5. Vincula os parâmetros de forma segura
    $query->bindValue(":usuario_id", $id_aluno);
    $query->bindValue(":tecnica", $tecnica);
    $query->bindValue(":nivel", $nivel);
    $query->bindValue(":status", $status);
    $query->bindValue(":data_conquista", $hoje);

    $query->execute();

    // 6. Feedback e retorno
    echo "<script language='javascript'>
            alert('Selo de técnica lançado com sucesso!');
            window.location='index.php?pag=usuarios'; 
          </script>";
} catch (PDOException $e) {
    // Erro detalhado apenas para desenvolvimento, em produção use uma mensagem genérica
    echo "Erro ao salvar no banco de dados: " . $e->getMessage();
}
