<?php
@session_start();
require_once("../conexao.php");
require_once("../enviar-notificacao.php");

// 1. VALIDAÇÃO DE ACESSO
if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// 2. RECEBIMENTO DOS DADOS
$id_aquecimento = $_POST['aquecimento_id'] ?? null;
$id_educativo   = $_POST['educativo_id']   ?? null;
$licao_numero   = $_POST['licao_id']       ?? null;
$alunos         = $_POST['alunos']         ?? [];
$status_selo    = $_POST['status']         ?? 'Em Estudo';

$nome_prof = $_SESSION['nome_usuario'];

if (empty($alunos)) {
    echo "<script>alert('Erro: Selecione ao menos um aluno.'); window.history.back();</script>";
    exit();
}

// 3. PREPARAÇÃO DOS DADOS DA AULA
$tecnicas_para_lancar = [];

// A. Adiciona o Aquecimento (se houver)
if (!empty($id_aquecimento)) {
    $q = $pdo->prepare("SELECT nome, categoria FROM tecnicas WHERE id = :id");
    $q->execute([':id' => $id_aquecimento]);
    $res = $q->fetch(PDO::FETCH_ASSOC);
    if ($res) $tecnicas_para_lancar[] = ['t' => $res['nome'], 'p' => $res['categoria']];
}

// B. Adiciona o Educativo (se houver)
if (!empty($id_educativo)) {
    $q = $pdo->prepare("SELECT nome, categoria FROM tecnicas WHERE id = :id");
    $q->execute([':id' => $id_educativo]);
    $res = $q->fetch(PDO::FETCH_ASSOC);
    if ($res) $tecnicas_para_lancar[] = ['t' => $res['nome'], 'p' => $res['categoria']];
}

// C. Adiciona todas as técnicas da Lição (Obrigatório conforme seu form)
$query_l = $pdo->prepare("SELECT id, categoria FROM licoes WHERE numero = :n");
$query_l->execute([':n' => $licao_numero]);
$dados_licao = $query_l->fetch(PDO::FETCH_ASSOC);

if ($dados_licao) {
    $query_t = $pdo->prepare("SELECT nome FROM tecnicas WHERE licao_id = :id_l");
    $query_t->execute([':id_l' => $dados_licao['id']]);
    $tecnicas_licao = $query_t->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tecnicas_licao as $tl) {
        $tecnicas_para_lancar[] = ['t' => $tl['nome'], 'p' => $dados_licao['categoria']];
    }
}

// 4. PROCESSAMENTO POR ALUNO
try {
    foreach ($alunos as $aluno_id) {
        // Busca nome do aluno para notificação
        $q_u = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
        $q_u->execute([':id' => $aluno_id]);
        $aluno_nome = $q_u->fetchColumn();

        foreach ($tecnicas_para_lancar as $item) {
            $tec = $item['t'];
            $pos = $item['p'];

            // Verifica se já existe para este aluno
            $check = $pdo->prepare("SELECT id FROM habilidades WHERE usuario_id = :u AND tecnica = :t");
            $check->execute([':u' => $aluno_id, ':t' => $tec]);

            if ($check->rowCount() == 0) {
                $ins = $pdo->prepare("INSERT INTO habilidades (usuario_id, tecnica, posicao, nivel, status, data_conquista) 
                                      VALUES (:u, :t, :p, '25', :s, NOW())");
                $ins->execute([':u' => $aluno_id, ':t' => $tec, ':p' => $pos, ':s' => $status_selo]);
            } else {
                $upd = $pdo->prepare("UPDATE habilidades SET status = :s, posicao = :p WHERE usuario_id = :u AND tecnica = :t");
                $upd->execute([':s' => $status_selo, ':p' => $pos, ':u' => $aluno_id, ':t' => $tec]);
            }
        }

        // 5. NOTIFICAÇÃO PUSH
        $titulo_push = "Treino Registrado! 🥋";
        $msg_push = "OSS! Sua aula de hoje (Lição $licao_numero) foi lançada como $status_selo.";
        enviarPush($aluno_id, $titulo_push, $msg_push, "painel-cliente/index.php");

        // 6. LOG
        $acao = "Lançou Plano de Aula (Lição $licao_numero) para o aluno $aluno_nome";
        $ins_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:user, :acao, NOW())");
        $ins_log->execute([':user' => $nome_prof, ':acao' => $acao]);
    }

    echo "<script>alert('Sucesso! Plano de aula registrado para " . count($alunos) . " alunos.'); window.location='index.php';</script>";
} catch (PDOException $e) {
    echo "Erro ao processar: " . $e->getMessage();
}
