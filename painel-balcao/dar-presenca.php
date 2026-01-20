<?php
require_once("../conexao.php");
@session_start();

// 1. Validação de Segurança (Incluindo o Professor agora!)
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Balconista' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "Acesso Negado!";
    exit();
}

$id_aluno = @$_GET['id'];
$usuario_logado = $_SESSION['nome_usuario'];

if ($id_aluno == "") {
    echo "<script>window.location='usuarios.php';</script>";
    exit();
}

try {
    // 2. Busca o nome do aluno para o Log
    $query_u = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
    $query_u->bindValue(":id", $id_aluno);
    $query_u->execute();
    $dados_u = $query_u->fetch(PDO::FETCH_ASSOC);
    $nome_aluno = ($dados_u) ? $dados_u['nome'] : "Desconhecido";

    // 3. Atualiza o total de aulas na tabela graduacoes
    // Nota: Verifique se a coluna 'usuario_id' é a correta na sua tabela graduacoes
    $res = $pdo->prepare("UPDATE graduacoes SET total_aulas = total_aulas + 1 WHERE usuario_id = :id");
    $res->bindValue(":id", $id_aluno);
    $res->execute();

    // 4. REGISTRA NO LOG DE ATIVIDADES
    $acao = "Presença confirmada: " . $nome_aluno;
    $res_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:usuario_logado, :acao, NOW())");
    $res_log->bindValue(":usuario_logado", $usuario_logado);
    $res_log->bindValue(":acao", $acao);
    $res_log->execute();

    // 5. Feedback e Redirecionamento
    echo "<script language='javascript'>
            window.alert('Presença de $nome_aluno registrada com sucesso!'); 
            window.location='usuarios.php';
          </script>";
} catch (PDOException $e) {
    echo "Erro ao registrar presença: " . $e->getMessage();
}
