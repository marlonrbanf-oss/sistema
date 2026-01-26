<?php
require_once("../conexao.php");
// IMPORTAÇÃO DA FUNÇÃO DE NOTIFICAÇÃO
require_once("../enviar-notificacao.php");

@session_start();

// 1. VALIDAÇÃO DE SEGURANÇA
// Adicionado 'Professor' para garantir que ele também possa salvar individualmente se necessário
if (@$_SESSION['nivel_usuario'] != 'Balconista' && @$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// 2. RECEBE E TRATA OS DADOS
$id_aluno = isset($_POST['id_aluno']) ? $_POST['id_aluno'] : null;
$nivel    = isset($_POST['nivel']) ? $_POST['nivel'] : '25'; // Padrão 25% se vazio
$status   = isset($_POST['status']) ? $_POST['status'] : 'Em Estudo';
$posicao  = isset($_POST['posicao']) ? $_POST['posicao'] : 'Geral';
$hoje     = date('Y-m-d');
$id_prof  = $_SESSION['id_usuario'];
$nome_prof = $_SESSION['nome_usuario'];

// Lógica para capturar técnica manual ou selecionada no select
$tecnica = !empty($_POST['tecnica_manual']) ? $_POST['tecnica_manual'] : @$_POST['tecnica'];

if (empty($id_aluno) || empty($tecnica)) {
    echo "<script language='javascript'>alert('Selecione um aluno e uma técnica!'); window.history.back(); </script>";
    exit();
}

try {
    // 3. VERIFICA SE O ALUNO JÁ POSSUI ESSA TÉCNICA (Evita duplicidade)
    $check = $pdo->prepare("SELECT id FROM habilidades WHERE usuario_id = :u AND tecnica = :t");
    $check->execute([':u' => $id_aluno, ':t' => $tecnica]);

    if ($check->rowCount() > 0) {
        // ATUALIZA técnica existente (muda progresso ou status)
        $query = $pdo->prepare("UPDATE habilidades SET nivel = :nivel, status = :status, posicao = :posicao WHERE usuario_id = :u AND tecnica = :t");
        $query->bindValue(":nivel", $nivel);
        $query->bindValue(":status", $status);
        $query->bindValue(":posicao", $posicao);
        $query->bindValue(":u", $id_aluno);
        $query->bindValue(":t", $tecnica);
    } else {
        // INSERE nova técnica no currículo do aluno
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

    // 4. DISPARO DA NOTIFICAÇÃO PUSH
    // Buscamos o nome do aluno para o Push ser personalizado
    $query_n = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
    $query_n->execute([':id' => $id_aluno]);
    $res_n = $query_n->fetch(PDO::FETCH_ASSOC);
    $primeiro_nome = explode(' ', $res_n['nome'])[0];

    $titulo_push = "Selo de Técnica Atualizado! 🥋";
    $msg_push = "OSS $primeiro_nome! Você recebeu o selo: $tecnica ($status).";

    // Envia via OneSignal
    if (function_exists('enviarPush')) {
        enviarPush($id_aluno, $titulo_push, $msg_push, "painel-cliente/index.php");
    }

    // 5. REGISTRA NO LOG DO SISTEMA
    $acao = "Lançou selo avulso: $tecnica ($status) para o aluno $primeiro_nome (ID $id_aluno)";
    $ins_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:user, :acao, NOW())");
    $ins_log->execute([':user' => $nome_prof, ':acao' => $acao]);

    // 6. FEEDBACK E RETORNO
    echo "<script language='javascript'>
            alert('Técnica salva com sucesso! O aluno foi notificado.');
            window.location='index.php?pag=usuarios';
          </script>";
} catch (PDOException $e) {
    echo "Erro técnico ao salvar: " . $e->getMessage();
}
