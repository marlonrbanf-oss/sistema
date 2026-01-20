<?php
require_once("../conexao.php");
@session_start();

// 1. Validação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Balconista' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "Acesso Negado!";
    exit();
}

$id_aluno = @$_GET['id'];
$usuario_logado = $_SESSION['nome_usuario'];

// Captura a quantidade vinda do prompt JS, se não existir, define como 1
$quantidade = (isset($_GET['quantidade']) && $_GET['quantidade'] > 0) ? (int)$_GET['quantidade'] : 1;

if ($id_aluno == "") {
    echo "<script language='javascript'>window.location='usuarios.php';</script>";
    exit();
}

try {
    // 2. Busca o nome do aluno para o Log e verifica se ele existe
    $query_u = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
    $query_u->bindValue(":id", $id_aluno);
    $query_u->execute();
    $dados_u = $query_u->fetch(PDO::FETCH_ASSOC);

    if (!$dados_u) {
        echo "<script language='javascript'>alert('Aluno não encontrado!'); window.location='usuarios.php';</script>";
        exit();
    }

    $nome_aluno = $dados_u['nome'];

    // 3. Atualiza o total de aulas na tabela graduacoes com a QUANTIDADE ESCOLHIDA
    $res = $pdo->prepare("UPDATE graduacoes SET total_aulas = total_aulas + :qtd WHERE usuario_id = :id");
    $res->bindValue(":qtd", $quantidade, PDO::PARAM_INT);
    $res->bindValue(":id", $id_aluno);
    $res->execute();

    // 4. REGISTRA NO LOG DE ATIVIDADES (Com nome de quem deu e para quem foi dado)
    $acao = "Lançou $quantidade presença(s) para o aluno: " . $nome_aluno;
    $res_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:usuario_logado, :acao, NOW())");
    $res_log->bindValue(":usuario_logado", $usuario_logado);
    $res_log->bindValue(":acao", $acao);
    $res_log->execute();

    // 5. Feedback e Redirecionamento
    echo "<script language='javascript'>
            window.alert('Sucesso: $quantidade presença(s) para $nome_aluno registrada(s)!'); 
            window.location='usuarios.php';
          </script>";
} catch (PDOException $e) {
    echo "Erro no banco de dados: " . $e->getMessage();
}
