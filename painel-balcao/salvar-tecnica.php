<?php
require_once("../conexao.php");
// 1. IMPORTAÇÃO DA FUNÇÃO DE NOTIFICAÇÃO
require_once("../enviar-notificacao.php");

@session_start();

// 1. VALIDAÇÃO DE SEGURANÇA
if (@$_SESSION['nivel_usuario'] != 'Balconista' && @$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// 2. RECEBE E TRATA OS DADOS
$id_aluno = $_POST['id_aluno'];
$nivel = $_POST['nivel'];
$status = $_POST['status'];
$posicao = $_POST['posicao']; // Captura a posição/categoria selecionada
$hoje = date('Y-m-d');
$id_prof = $_SESSION['id_usuario'];
$nome_prof = $_SESSION['nome_usuario'];

// Define qual nome de técnica usar (Manual ou Selecionada)
$tecnica = !empty($_POST['tecnica_manual']) ? $_POST['tecnica_manual'] : $_POST['tecnica'];

if (empty($id_aluno) || empty($tecnica)) {
    echo "<script language='javascript'>alert('Selecione uma técnica!'); window.history.back(); </script>";
    exit();
}

try {
    // 3. VERIFICA SE O ALUNO JÁ POSSUI ESSA TÉCNICA
    $check = $pdo->prepare("SELECT id FROM habilidades WHERE usuario_id = :u AND tecnica = :t");
    $check->execute([':u' => $id_aluno, ':t' => $tecnica]);

    if ($check->rowCount() > 0) {
        // ATUALIZA técnica existente
        $query = $pdo->prepare("UPDATE habilidades SET nivel = :nivel, status = :status, posicao = :posicao WHERE usuario_id = :u AND tecnica = :t");
        $query->bindValue(":nivel", $nivel);
        $query->bindValue(":status", $status);
        $query->bindValue(":posicao", $posicao);
        $query->bindValue(":u", $id_aluno);
        $query->bindValue(":t", $tecnica);
    } else {
        // INSERE nova técnica
        $query = $pdo->prepare("INSERT INTO habilidades (usuario_id, tecnica, posicao, nivel, status, data_conquista) 
                                VALUES (:u, :t, :posicao, :nivel, :status, :data_conquista)");
        $query->bindValue(":u", $id_aluno);
        $query->bindValue(":t", $tecnica);
        $query->bindValue(":posicao", $posicao);
        $query->bindValue(":nivel", $nivel);
        $query->bindValue(":status", $status);
        $query->bindValue(":data_conquista", $hoje);
    }

    $query->execute();

    // 4. DISPARO DA NOTIFICAÇÃO PUSH (NOVIDADE)
    // Personaliza a mensagem de acordo com o status
    $titulo_push = "Novo Selo de Técnica! 🥋";
    $msg_push = "OSS! Você recebeu o selo: $tecnica ($status).";

    // Enviamos para o OneSignal
    enviarPush($id_aluno, $titulo_push, $msg_push, "painel-cliente/index.php");

    // 5. REGISTRA NO LOG
    $acao = "Lançou selo: $tecnica ($status) para o aluno ID $id_aluno";
    $ins_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:user, :acao, NOW())");
    $ins_log->execute([':user' => $nome_prof, ':acao' => $acao]);

    // 6. FEEDBACK E RETORNO
    echo "<script language='javascript'>
            alert('Selo de técnica gravado e aluno notificado!');
            window.location='usuarios.php';
          </script>";
} catch (PDOException $e) {
    echo "Erro ao salvar no banco de dados: " . $e->getMessage();
}
