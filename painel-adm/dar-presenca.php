<?php
require_once("../conexao.php");
@session_start();

// 1. Validação de Segurança para Admin ou Balconista
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Balconista') {
    echo "Acesso Negado!";
    exit();
}

$id_aluno = @$_GET['id'];
$usuario_logado = $_SESSION['nome_usuario'];

if ($id_aluno == "") {
    echo "Selecione um aluno válido!";
    exit();
}

// 2. Busca o nome do aluno para o Log (melhora a clareza do histórico)
$query_u = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
$query_u->bindValue(":id", $id_aluno);
$query_u->execute();
$dados_u = $query_u->fetch(PDO::FETCH_ASSOC);
$nome_aluno = ($dados_u) ? $dados_u['nome'] : "Desconhecido";

// 3. Atualiza o total de aulas na tabela graduacoes
$res = $pdo->prepare("UPDATE graduacoes SET total_aulas = total_aulas + 1 WHERE usuario_id = :id");
$res->bindValue(":id", $id_aluno);
$res->execute();

// 4. REGISTRA NO LOG DE ATIVIDADES
// Isso fará com que a ação apareça no seu novo Dashboard e na tela de Logs
$acao = "Registrou presença para o aluno: " . $nome_aluno;
$res_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:usuario_logado, :acao, NOW())");
$res_log->bindValue(":usuario_logado", $usuario_logado);
$res_log->bindValue(":acao", $acao);
$res_log->execute();

// 5. Feedback e Redirecionamento
echo "<script language='javascript'>
        window.alert('Presença de $nome_aluno registrada com sucesso!'); 
        window.location='usuarios.php';
      </script>";
