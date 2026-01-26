<?php
@session_start();
require_once("../conexao.php");
// IMPORTAÇÃO DA FUNÇÃO DE NOTIFICAÇÃO
require_once("../enviar-notificacao.php");

// Validação de acesso
if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// Recebe os dados via POST
$licao_numero = isset($_POST['licao_id']) ? $_POST['licao_id'] : null;
$alunos = isset($_POST['alunos']) ? $_POST['alunos'] : [];
$status_selecionado = isset($_POST['status']) ? $_POST['status'] : 'Em Estudo';

$id_prof = $_SESSION['id_usuario'];
$nome_prof = $_SESSION['nome_usuario'];

if (!empty($alunos) && $licao_numero !== null) {

    // 1. BUSCA AS TÉCNICAS DA LIÇÃO DIRETAMENTE NO BANCO DE DADOS
    // Primeiro pegamos o ID interno da lição e sua categoria
    $query_l = $pdo->prepare("SELECT id, categoria FROM licoes WHERE numero = :n");
    $query_l->bindValue(":n", $licao_numero);
    $query_l->execute();
    $dados_licao = $query_l->fetch(PDO::FETCH_ASSOC);

    if (!$dados_licao) {
        echo "<script>alert('Erro: Lição não encontrada no sistema.'); window.history.back();</script>";
        exit();
    }

    $id_interno_licao = $dados_licao['id'];
    $categoria_licao = $dados_licao['categoria'];

    // Agora buscamos as técnicas vinculadas a esse ID de lição
    $query_t = $pdo->prepare("SELECT nome FROM tecnicas WHERE licao_id = :id_l");
    $query_t->bindValue(":id_l", $id_interno_licao);
    $query_t->execute();
    $tecnicas_da_aula = $query_t->fetchAll(PDO::FETCH_ASSOC);

    // 2. PROCESSAMENTO POR ALUNO
    foreach ($alunos as $aluno_id) {

        // Busca nome do aluno para Log e Notificação
        $query_u = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
        $query_u->execute([':id' => $aluno_id]);
        $dados_aluno = $query_u->fetch(PDO::FETCH_ASSOC);
        $nome_aluno = $dados_aluno['nome'] ?? 'Aluno';

        foreach ($tecnicas_da_aula as $item) {
            $tec = $item['nome'];
            $pos = $categoria_licao;

            // Verifica se o aluno já possui essa técnica registrada na tabela habilidades
            $check = $pdo->prepare("SELECT id FROM habilidades WHERE usuario_id = :u AND tecnica = :t");
            $check->execute([':u' => $aluno_id, ':t' => $tec]);

            if ($check->rowCount() == 0) {
                // Insere novo registro se não existir
                $ins = $pdo->prepare("INSERT INTO habilidades (usuario_id, tecnica, posicao, nivel, status) VALUES (:u, :t, :p, '25', :s)");
                $ins->execute([
                    ':u' => $aluno_id,
                    ':t' => $tec,
                    ':p' => $pos,
                    ':s' => $status_selecionado
                ]);
            } else {
                // Se já existe, atualiza apenas o status (ex: de Revisão para Concluído)
                $upd = $pdo->prepare("UPDATE habilidades SET status = :s WHERE usuario_id = :u AND tecnica = :t");
                $upd->execute([
                    ':s' => $status_selecionado,
                    ':u' => $aluno_id,
                    ':t' => $tec
                ]);
            }
        }

        // 3. DISPARO DA NOTIFICAÇÃO PUSH
        $titulo_push = "Nova Lição Registrada! 🥋";
        $msg_push = "OSS " . explode(' ', $nome_aluno)[0] . ", você recebeu a Lição $licao_numero como: $status_selecionado.";

        if (function_exists('enviarPush')) {
            enviarPush($aluno_id, $titulo_push, $msg_push, "painel-cliente/index.php");
        }

        // 4. REGISTRO DE LOG
        $acao = "Lançou Lição $licao_numero (Status: $status_selecionado) para o aluno $nome_aluno";
        $ins_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:user, :acao, NOW())");
        $ins_log->execute([':user' => $nome_prof, ':acao' => $acao]);
    }

    echo "<script>alert('Sucesso! Lição aplicada a " . count($alunos) . " aluno(s) e notificações enviadas.'); window.location='index.php';</script>";
} else {
    echo "<script>alert('Erro: Verifique se a lição e os alunos foram selecionados.'); window.history.back();</script>";
}
