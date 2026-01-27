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
$licao_numero   = $_POST['licao_id']       ?? null; // Vem o número da lição (ex: 1, 2, 3...)
$alunos         = $_POST['alunos']         ?? [];
$status_selo    = $_POST['status']         ?? 'Em Estudo';

$nome_prof = $_SESSION['nome_usuario'];

if (empty($alunos)) {
    echo "<script>alert('Erro: Nenhum aluno selecionado.'); window.history.back();</script>";
    exit();
}

if (empty($licao_numero)) {
    echo "<script>alert('Erro: Selecione uma Lição Técnica.'); window.history.back();</script>";
    exit();
}

// 3. PREPARAÇÃO DA LISTA DE TÉCNICAS DA AULA
$tecnicas_para_lancar = [];

// A. Captura o Aquecimento
if (!empty($id_aquecimento)) {
    $q = $pdo->prepare("SELECT nome, COALESCE(categoria, posicao) as cat FROM tecnicas WHERE id = :id");
    $q->execute([':id' => $id_aquecimento]);
    $res = $q->fetch(PDO::FETCH_ASSOC);
    if ($res) $tecnicas_para_lancar[] = ['nome' => $res['nome'], 'cat' => $res['cat']];
}

// B. Captura o Educativo
if (!empty($id_educativo)) {
    $q = $pdo->prepare("SELECT nome, COALESCE(categoria, posicao) as cat FROM tecnicas WHERE id = :id");
    $q->execute([':id' => $id_educativo]);
    $res = $q->fetch(PDO::FETCH_ASSOC);
    if ($res) $tecnicas_para_lancar[] = ['nome' => $res['nome'], 'cat' => $res['cat']];
}

// C. Captura as Técnicas da Lição (Obrigatório)
$query_l = $pdo->prepare("SELECT id, categoria, nivel FROM licoes WHERE numero = :n");
$query_l->execute([':n' => $licao_numero]);
$dados_licao = $query_l->fetch(PDO::FETCH_ASSOC);

if ($dados_licao) {
    // Buscamos todas as técnicas que pertencem a essa lição específica
    $query_t = $pdo->prepare("SELECT nome FROM tecnicas WHERE licao_id = :id_l");
    $query_t->execute([':id_l' => $dados_licao['id']]);
    $tecnicas_licao = $query_t->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tecnicas_licao as $tl) {
        $tecnicas_para_lancar[] = [
            'nome' => $tl['nome'],
            'cat'  => $dados_licao['categoria'] ?: 'Técnica'
        ];
    }
}

// 4. PROCESSAMENTO POR ALUNO (Transação para garantir integridade)
try {
    $pdo->beginTransaction();

    foreach ($alunos as $aluno_id) {
        // Busca dados básicos do aluno
        $q_u = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
        $q_u->execute([':id' => $aluno_id]);
        $aluno_nome = $q_u->fetchColumn();

        foreach ($tecnicas_para_lancar as $item) {
            $tec = $item['nome'];
            $pos = $item['cat'];

            // Verifica se o aluno já possui essa técnica registrada
            $check = $pdo->prepare("SELECT id FROM habilidades WHERE usuario_id = :u AND tecnica = :t");
            $check->execute([':u' => $aluno_id, ':t' => $tec]);

            if ($check->rowCount() == 0) {
                // Inserção de nova habilidade conquistada
                $ins = $pdo->prepare("INSERT INTO habilidades (usuario_id, tecnica, posicao, nivel, status, data_conquista) 
                                      VALUES (:u, :t, :p, '25', :s, NOW())");
                $ins->execute([
                    ':u' => $aluno_id,
                    ':t' => $tec,
                    ':p' => $pos,
                    ':s' => $status_selo
                ]);
            } else {
                // Atualização do status da técnica (ex: de 'Em Estudo' para 'Concluído')
                $upd = $pdo->prepare("UPDATE habilidades SET status = :s, posicao = :p WHERE usuario_id = :u AND tecnica = :t");
                $upd->execute([
                    ':s' => $status_selo,
                    ':p' => $pos,
                    ':u' => $aluno_id,
                    ':t' => $tec
                ]);
            }
        }

        // 5. NOTIFICAÇÕES (Opcional, mas ajuda no engajamento)
        if (function_exists('enviarPush')) {
            $titulo_push = "Nova Aula Registrada! 🥋";
            $msg_push = "OSS! A aula de hoje (Lição $licao_numero) foi lançada como $status_selo.";
            @enviarPush($aluno_id, $titulo_push, $msg_push, "painel-cliente/index.php");
        }

        // 6. LOG DE AUDITORIA
        $acao = "Lançou Plano de Aula Coletivo (Lição $licao_numero) para o aluno $aluno_nome";
        $ins_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:user, :acao, NOW())");
        $ins_log->execute([':user' => $nome_prof, ':acao' => $acao]);
    }

    $pdo->commit();
    echo "<script>alert('OSS! Plano de aula registrado com sucesso para " . count($alunos) . " alunos.'); window.location='index.php?pag=usuarios';</script>";
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Erro técnico ao processar: " . $e->getMessage();
}
