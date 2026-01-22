<?php
require_once("../conexao.php");
@session_start();

// 1. Validação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin') {
    echo "Acesso Negado! Somente administradores podem excluir registros.";
    exit();
}

$id = @$_GET['id'];
$usuario_logado = $_SESSION['nome_usuario'];

if ($id != "") {
    try {
        // A. Busca o nome antes de excluir para o Log
        $query_u = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
        $query_u->bindValue(":id", $id);
        $query_u->execute();
        $dados_u = $query_u->fetch(PDO::FETCH_ASSOC);
        $nome_excluido = ($dados_u) ? $dados_u['nome'] : "ID: " . $id;

        // B. LIMPEZA DE DEPENDÊNCIAS (IMPORTANTE)
        // Apagamos primeiro os registros onde esse ID é usado como chave estrangeira

        // Apaga aulas particulares vinculadas a este aluno
        $pdo->query("DELETE FROM aulas_particulares WHERE aluno_id = '$id'");

        // Apaga graduações vinculadas
        $pdo->query("DELETE FROM graduacoes WHERE usuario_id = '$id'");

        // Se houver outras tabelas como 'mensalidades' ou 'frequencias', adicione aqui:
        // $pdo->query("DELETE FROM mensalidades WHERE usuario_id = '$id'");

        // C. Agora sim, exclui o usuário principal
        $pdo->query("DELETE FROM usuarios WHERE id = '$id'");

        // D. Registra no Log de Atividades
        $acao = "Excluiu o usuário: " . $nome_excluido;
        $res_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:usuario_logado, :acao, NOW())");
        $res_log->bindValue(":usuario_logado", $usuario_logado);
        $res_log->bindValue(":acao", $acao);
        $res_log->execute();

        echo "<script>alert('Removido com sucesso!'); window.location='index.php?pag=usuarios';</script>";
    } catch (Exception $e) {
        // Exibe uma mensagem mais amigável em caso de erro
        echo "Erro ao excluir: Verifique se existem outros registros vinculados a este usuário.";
        // echo " Erro técnico: " . $e->getMessage(); // Use para debugar se necessário
    }
} else {
    echo "<script>window.location='index.php?pag=usuarios';</script>";
}
