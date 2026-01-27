<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE ACESSO
if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// 2. RECEBIMENTO DOS DADOS
$id_aquecimento = $_POST['aquecimento_id'] ?? null;
$id_educativo   = $_POST['educativo_id']   ?? null;
$id_licao       = $_POST['licao_id']       ?? null;
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

// 3. PREPARAÇÃO DA LISTA DE TÉCNICAS (Conforme o esquema da tabela 'licoes')
$tecnicas_para_lancar = [];

function buscarInfoLicao($pdo, $id)
{
    if (!$id) return null;
    $q = $pdo->prepare("SELECT titulo, categoria, numero FROM licoes WHERE id = :id");
    $q->execute([':id' => $id]);
    return $q->fetch(PDO::FETCH_ASSOC);
}

// A. Aquecimento / Fundamento
if (!empty($id_aquecimento)) {
    $res = buscarInfoLicao($pdo, $id_aquecimento);
    if ($res) $tecnicas_para_lancar[] = ['nome' => $res['titulo'], 'cat' => $res['categoria']];
}

// B. Educativo / Alongamento
if (!empty($id_educativo)) {
    $res = buscarInfoLicao($pdo, $id_educativo);
    if ($res) $tecnicas_para_lancar[] = ['nome' => $res['titulo'], 'cat' => $res['categoria']];
}

// C. Lição Principal e Detalhes
$dados_licao = buscarInfoLicao($pdo, $id_licao);
if ($dados_licao) {
    // Adiciona a Lição da Matriz
    $tecnicas_para_lancar[] = ['nome' => "L" . $dados_licao['numero'] . " - " . $dados_licao['titulo'], 'cat' => 'Matriz Técnica'];

    // Busca técnicas vinculadas na tabela 'tecnicas'
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

        // B. CONTROLE DE PRESENÇA (Incrementa total_aulas na tabela graduacoes)
        $check_grad = $pdo->prepare("SELECT id, total_aulas FROM graduacoes WHERE usuario_id = :u");
        $check_grad->execute([':u' => $aluno_id]);
        $res_grad = $check_grad->fetch(PDO::FETCH_ASSOC);

        if ($res_grad) {
            // Só incrementa se não tiver registrado presença hoje (evita duplicidade no mesmo dia)
            $check_hoje = $pdo->prepare("SELECT id FROM graduacoes WHERE usuario_id = :u AND data_ult_aula = :hoje");
            $check_hoje->execute([':u' => $aluno_id, ':hoje' => $hoje]);

            if ($check_hoje->rowCount() == 0) {
                $upd_presenca = $pdo->prepare("UPDATE graduacoes SET total_aulas = total_aulas + 1, data_ult_aula = :hoje WHERE usuario_id = :u");
                $upd_presenca->execute([':u' => $aluno_id, ':hoje' => $hoje]);
            }
        } else {
            // Cria registro de graduação caso não exista
            $ins_grad = $pdo->prepare("INSERT INTO graduacoes (usuario_id, total_aulas, data_ult_aula, cor_faixa) VALUES (:u, 1, :hoje, 'Branca')");
            $ins_grad->execute([':u' => $aluno_id, ':hoje' => $hoje]);
        }

        // C. LANÇAMENTO DOS SELOS (Tabela habilidades)
        foreach ($tecnicas_para_lancar as $item) {
            $tec_nome = $item['nome'];
            $tec_cat  = $item['cat'];

            $check_habil = $pdo->prepare("SELECT id FROM habilidades WHERE usuario_id = :u AND tecnica = :t");
            $check_habil->execute([':u' => $aluno_id, ':t' => $tec_nome]);

            if ($check_habil->rowCount() == 0) {
                $ins = $pdo->prepare("INSERT INTO habilidades (usuario_id, tecnica, posicao, status, data_conquista) 
                                      VALUES (:u, :t, :p, :s, :data)");
                $ins->execute([
                    ':u'    => $aluno_id,
                    ':t'    => $tec_nome,
                    ':p'    => $tec_cat,
                    ':s'    => $status_selo,
                    ':data' => $hoje
                ]);
            } else {
                $upd = $pdo->prepare("UPDATE habilidades SET status = :s, posicao = :p, data_conquista = :data WHERE usuario_id = :u AND tecnica = :t");
                $upd->execute([
                    ':s'    => $status_selo,
                    ':p'    => $tec_cat,
                    ':data' => $hoje,
                    ':u'    => $aluno_id,
                    ':t'    => $tec_nome
                ]);
            }
        }

        // D. LOG DE AUDITORIA
        $resumo_log = "Aula: " . ($dados_licao['titulo'] ?? 'Matriz');
        $ins_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:user, :acao, NOW())");
        $ins_log->execute([
            ':user' => $nome_prof,
            ':acao' => "Lançou Plano Coletivo ($resumo_log) para $aluno_nome"
        ]);
    }

    $pdo->commit();
    echo "<script>alert('OSS! Plano de aula e presenças processados com sucesso.'); window.location='index.php';</script>";
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Erro técnico ao processar: " . $e->getMessage());
}
