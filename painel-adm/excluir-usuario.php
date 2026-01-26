<?php
require_once("../conexao.php");
@session_start();

// 1. Validação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.alert('Acesso Negado! Somente administradores podem excluir registros.'); window.location='index.php?pag=usuarios';</script>";
    exit();
}

$id = isset($_GET['id']) ? $_GET['id'] : "";
$usuario_logado = isset($_SESSION['nome_usuario']) ? $_SESSION['nome_usuario'] : 'Admin';

if ($id != "") {
    try {
        // A. Busca os dados antes de excluir (Para Foto, Log e Identificação)
        $query_u = $pdo->prepare("SELECT nome, nivel, foto FROM usuarios WHERE id = :id");
        $query_u->bindValue(":id", $id);
        $query_u->execute();
        $dados_u = $query_u->fetch(PDO::FETCH_ASSOC);

        if (!$dados_u) {
            echo "<script>alert('Usuário não encontrado!'); window.location='index.php?pag=usuarios';</script>";
            exit();
        }

        $nome_excluido = $dados_u['nome'];
        $nivel_excluido = $dados_u['nivel'];
        $foto_excluida = $dados_u['foto'];

        // B. EXCLUSÃO DE ARQUIVO FÍSICO
        if ($foto_excluida != "sem-perfil.jpg" && $foto_excluida != "") {
            $caminho_foto = "../img/perfil/" . $foto_excluida;
            if (file_exists($caminho_foto)) {
                @unlink($caminho_foto);
            }
        }

        // C. LIMPEZA DE DEPENDÊNCIAS (INTEGRIDADE DO BANCO)

        // 1. Apaga graduações vinculadas
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

        // 4. Limpeza da tabela de técnicas/estudos
        try {
            $res = $pdo->prepare("DELETE FROM tecnicas_estudo WHERE usuario_id = :id OR ultimo_parceiro_id = :id");
            $res->bindValue(":id", $id);
            $res->execute();
        } catch (Exception $e) {
            // Tabela opcional, erro ignorado se não existir
        }

        // D. EXCLUSÃO DO USUÁRIO PRINCIPAL
        $res = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        $res->bindValue(":id", $id);
        $res->execute();

        // E. REGISTRO NO LOG DE ATIVIDADES
        $acao_log = "Excluiu o usuário (" . $nivel_excluido . "): " . $nome_excluido;
        $res_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:usuario_logado, :acao, NOW())");
        $res_log->bindValue(":usuario_logado", $usuario_logado);
        $res_log->bindValue(":acao", $acao_log);
        $res_log->execute();

        echo "<script>alert('Removido com sucesso!'); window.location='index.php?pag=usuarios';</script>";
    } catch (Exception $e) {
        // Tratamento de erro caso haja restrições de chave estrangeira não mapeadas
        echo "<script>alert('Erro ao excluir: Existem registros em outras tabelas vinculados a este usuário.'); window.location='index.php?pag=usuarios';</script>";
    }
} else {
    echo "<script>window.location='index.php?pag=usuarios';</script>";
}
