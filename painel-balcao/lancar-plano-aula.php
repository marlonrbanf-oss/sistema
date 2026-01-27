<?php
@session_start();
require_once("../conexao.php");
// Removi o require da notificação caso o arquivo não exista para não travar o script, 
// mas mantive a funcionalidade comentada se você usar.
@include_once("../enviar-notificacao.php");

// 1. VALIDAÇÃO DE ACESSO
if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// 2. RECEBIMENTO DOS DADOS
$id_aquecimento = $_POST['aquecimento_id'] ?? null;
$id_educativo   = $_POST['educativo_id']   ?? null;
$id_licao       = $_POST['licao_id']       ?? null; // ID da lição selecionada
$alunos         = $_POST['alunos']         ?? [];
$status_selo    = $_POST['status']         ?? 'Em Estudo';
$hoje           = date('Y-m-d');
$nome_prof      = $_SESSION['nome_usuario'];

if (empty($alunos)) {
    echo "<script>alert('Erro: Nenhum aluno selecionado.'); window.history.back();</script>";
    exit();
}

if (empty($id_licao)) {
    echo "<script>alert('Erro: Selecione uma Lição Técnica.'); window.history.back();</script>";
    exit();
}

// 3. PREPARAÇÃO DA LISTA DE TÉCNICAS (Buscando da tabela LICOES conforme sua metodologia)
$tecnicas_para_lancar = [];

// Função auxiliar para evitar repetição de código ao buscar itens da metodologia
function buscarInfoLicao($pdo, $id)
{
    $q = $pdo->prepare("SELECT titulo as nome, categoria FROM licoes WHERE id = :id");
    $q->execute([':id' => $id]);
    return $q->fetch(PDO::FETCH_ASSOC);
}

// A. Captura Aquecimento/Fundamento
if (!empty($id_aquecimento)) {
    $res = buscarInfoLicao($pdo, $id_aquecimento);
    if ($res) $tecnicas_para_lancar[] = ['nome' => $res['nome'], 'cat' => $res['categoria']];
}

// B. Captura Educativo/Alongamento
if (!empty($id_educativo)) {
    $res = buscarInfoLicao($pdo, $id_educativo);
    if ($res) $tecnicas_para_lancar[] = ['nome' => $res['nome'], 'cat' => $res['categoria']];
}

// C. Captura Técnicas da Lição Principal (Buscando técnicas vinculadas)
$dados_licao = buscarInfoLicao($pdo, $id_licao);
if ($dados_licao) {
    $query_t = $pdo->prepare("SELECT nome FROM tecnicas WHERE licao_id = :id_l");
    $query_t->execute([':id_l' => $id_licao]);
    $tecnicas_vinculadas = $query_t->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tecnicas_vinculadas as $tl) {
        $tecnicas_para_lancar[] = ['nome' => $tl['nome'], 'cat' => 'Técnica'];
    }
}

// 4. PROCESSAMENTO POR ALUNO
try {
    $pdo->beginTransaction();

    foreach ($alunos as $aluno_id) {
        // A. BUSCA DADOS DO ALUNO
        $q_u = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
        $q_u->execute([':id' => $aluno_id]);
        $aluno_nome = $q_u->fetchColumn();

        // B. CONTROLE DE PRESENÇA (Somente 1 por dia)
        // Verificamos se a data_ult_aula na tabela graduacoes é diferente de hoje
        $check_presenca = $pdo->prepare("SELECT id FROM graduacoes WHERE usuario_id = :u AND data_ult_aula = :hoje");
        $check_presenca->execute([':u' => $aluno_id, ':hoje' => $hoje]);

        if ($check_presenca->rowCount() == 0) {
            // Se não deu presença hoje, incrementa total_aulas e atualiza a data
            $upd_presenca = $pdo->prepare("UPDATE graduacoes SET total_aulas = total_aulas + 1, data_ult_aula = :hoje WHERE usuario_id = :u");
            $upd_presenca->execute([':u' => $aluno_id, ':hoje' => $hoje]);

            // Log específico de presença
            $log_p = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:user, :acao, NOW())");
            $log_p->execute([':user' => $nome_prof, ':acao' => "Registrou presença diária para $aluno_nome"]);
        }

        // C. LANÇAMENTO DOS SELOS/HABILIDADES
        foreach ($tecnicas_para_lancar as $item) {
            $tec = $item['nome'];
            $pos = $item['cat'];

            $check_habil = $pdo->prepare("SELECT id FROM habilidades WHERE usuario_id = :u AND tecnica = :t");
            $check_habil->execute([':u' => $aluno_id, ':t' => $tec]);

            if ($check_habil->rowCount() == 0) {
                $ins = $pdo->prepare("INSERT INTO habilidades (usuario_id, tecnica, posicao, status, data_conquista) 
                                      VALUES (:u, :t, :p, :s, NOW())");
                $ins->execute([
                    ':u' => $aluno_id,
                    ':t' => $tec,
                    ':p' => $pos,
                    ':s' => $status_selo
                ]);
            } else {
                $upd = $pdo->prepare("UPDATE habilidades SET status = :s, posicao = :p WHERE usuario_id = :u AND tecnica = :t");
                $upd->execute([
                    ':s' => $status_selo,
                    ':p' => $pos,
                    ':u' => $aluno_id,
                    ':t' => $tec
                ]);
            }
        }

        // D. NOTIFICAÇÕES (Mantendo funcionalidade)
        if (function_exists('enviarPush')) {
            $msg_push = "OSS! Sua aula de hoje foi lançada: " . ($dados_licao['nome'] ?? 'Técnica do Dia');
            @enviarPush($aluno_id, "Presença Confirmada! 🥋", $msg_push, "painel-cliente/index.php");
        }

        // E. LOG DE AUDITORIA DA AULA
        $acao_aula = "Lançou Aula Coletiva (" . ($dados_licao['nome'] ?? 'Licao') . ") para $aluno_nome";
        $ins_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:user, :acao, NOW())");
        $ins_log->execute([':user' => $nome_prof, ':acao' => $acao_aula]);
    }

    $pdo->commit();
    echo "<script>alert('OSS! Plano de aula e presenças processados com sucesso.'); window.location='index.php?pag=usuarios';</script>";
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Erro técnico ao processar: " . $e->getMessage();
}
