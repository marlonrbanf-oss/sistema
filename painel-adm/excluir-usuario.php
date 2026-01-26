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
        // A. Busca os dados antes de excluir para o Log e para saber o nível
        $query_u = $pdo->prepare("SELECT nome, nivel FROM usuarios WHERE id = :id");
        $query_u->bindValue(":id", $id);
        $query_u->execute();
        $dados_u = $query_u->fetch(PDO::FETCH_ASSOC);

        if (!$dados_u) {
            echo "<script>alert('Usuário não encontrado!'); window.location='index.php?pag=usuarios';</script>";
            exit();
        }

        $nome_excluido = $dados_u['nome'];
        $nivel_excluido = $dados_u['nivel'];

        // B. LIMPEZA DE DEPENDÊNCIAS (PROTEÇÃO CONTRA ERROS DE CHAVE ESTRANGEIRA)

        // 1. Apaga graduações vinculadas (Independente se é aluno ou professor)
        $res = $pdo->prepare("DELETE FROM graduacoes WHERE usuario_id = :id");
        $res->bindValue(":id", $id);
        $res->execute();

        // 2. Apaga registros em aulas_particulares onde ele é o ALUNO
        $res = $pdo->prepare("DELETE FROM aulas_particulares WHERE aluno_id = :id");
        $res->bindValue(":id", $id);
        $res->execute();

        // 3. Se for Professor, apaga as aulas onde ele é o PROFESSOR
        if ($nivel_excluido == 'Professor') {
            $res = $pdo->prepare("DELETE FROM aulas_particulares WHERE professor_id = :id");
            $res->bindValue(":id", $id);
            $res->execute();
        }

        // 4. Se houver tabela de técnicas/estudos (do Dojo Digital), limpa também
        // Nota: Ajuste o nome da tabela se for diferente no seu banco
        try {
            $res = $pdo->prepare("DELETE FROM tecnicas_estudo WHERE usuario_id = :id OR ultimo_parceiro_id = :id");
            $res->bindValue(":id", $id);
            $res->execute();
        } catch (Exception $e) { /* Tabela pode não existir ainda */
        }

        // C. Agora sim, exclui o usuário principal
        $res = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        $res->bindValue(":id", $id);
        $res->execute();

        // D. Registra no Log de Atividades
        $acao = "Excluiu o usuário (" . $nivel_excluido . "): " . $nome_excluido;
        $res_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:usuario_logado, :acao, NOW())");
        $res_log->bindValue(":usuario_logado", $usuario_logado);
        $res_log->bindValue(":acao", $acao);
        $res_log->execute();

        echo "<script>alert('Removido com sucesso!'); window.location='index.php?pag=usuarios';</script>";
    } catch (Exception $e) {
        // Em caso de erro, mostra o erro técnico para o Admin resolver dependências de banco
        echo "Erro ao excluir: Verifique se existem outros registros vinculados a este usuário.";
        // echo " Erro técnico: " . $e->getMessage(); 
    }
} else {
    echo "<script>window.location='index.php?pag=usuarios';</script>";
}
