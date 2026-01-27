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
$id_licao       = $_POST['licao_id']       ?? null; // ID da lição da matriz técnica
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

// 3. PREPARAÇÃO DA LISTA DE TÉCNICAS (Buscando detalhes da tabela LICOES)
$tecnicas_para_lancar = [];

// Função auxiliar para buscar informações na tabela de Lições/Matriz
function buscarInfoLicao($pdo, $id)
{
    if (!$id) return null;
    $q = $pdo->prepare("SELECT titulo, categoria, numero FROM licoes WHERE id = :id");
    $q->execute([':id' => $id]);
    return $q->fetch(PDO::FETCH_ASSOC);
}

// A. Captura Aquecimento/Fundamento
if (!empty($id_aquecimento)) {
    $res = buscarInfoLicao($pdo, $id_aquecimento);
    if ($res) $tecnicas_para_lancar[] = ['nome' => $res['titulo'], 'cat' => $res['categoria']];
}

// B. Captura Educativo/Alongamento
if (!empty($id_educativo)) {
    $res = buscarInfoLicao($pdo, $id_educativo);
    if ($res) $tecnicas_para_lancar[] = ['nome' => $res['titulo'], 'cat' => $res['categoria']];
}

// C. Captura Lição Principal
$dados_licao = buscarInfoLicao($pdo, $id_licao);
if ($dados_licao) {
    // Adiciona a Lição Principal como uma habilidade/selo
    $tecnicas_para_lancar[] = ['nome' => "L" . $dados_licao['numero'] . " - " . $dados_licao['titulo'], 'cat' => 'Matriz Técnica'];

    // Se houver uma tabela separada de 'tecnicas' vinculadas a esta lição, buscamos aqui
    $query_t = $pdo->prepare("SELECT nome FROM tecnicas WHERE licao_id = :id_l");
    $query_t->execute([':id_l' => $id_licao]);
    $tecnicas_vinculadas = $query_t->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tecnicas_vinculadas as $tl) {
        $tecnicas_para_lancar[] = ['nome' => $tl['nome'], 'cat' => 'Detalhe Técnico'];
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

        // B. CONTROLE DE PRESENÇA (Garante apenas 1 incremento de aula por dia)
        $check_presenca = $pdo->prepare("SELECT id FROM graduacoes WHERE usuario_id = :u AND data_ult_aula = :hoje");
        $check_presenca->execute([':u' => $aluno_id, ':hoje' => $hoje]);

        if ($check_presenca->rowCount() == 0) {
            // Se o registro de graduação não existe para o aluno, criamos um (opcional dependendo da sua DB)
            $check_exist = $pdo->prepare("SELECT id FROM graduacoes WHERE usuario_id = :u");
            $check_exist->execute([':u' => $aluno_id]);

            if ($check_exist->rowCount() > 0) {
                $upd_presenca = $pdo->prepare("UPDATE graduacoes SET total_aulas = total_aulas + 1, data_ult_aula = :hoje WHERE usuario_id = :u");
                $upd_presenca->execute([':u' => $aluno_id, ':hoje' => $hoje]);
            } else {
                $ins_presenca = $pdo->prepare("INSERT INTO graduacoes (usuario_id, total_aulas, data_ult_aula) VALUES (:u, 1, :hoje)");
                $ins_presenca->execute([':u' => $aluno_id, ':hoje' => $hoje]);
            }
        }

        // C. LANÇAMENTO DOS SELOS/HABILIDADES (O coração da sua metodologia)
        foreach ($tecnicas_para_lancar as $item) {
            $tec_nome = $item['nome'];
            $tec_cat  = $item['cat'];

            // Verifica se o aluno já tem essa técnica registrada
            $check_habil = $pdo->prepare("SELECT id FROM habilidades WHERE usuario_id = :u AND tecnica = :t");
            $check_habil->execute([':u' => $aluno_id, ':t' => $tec_nome]);

            if ($check_habil->rowCount() == 0) {
                // Insere novo selo
                $ins = $pdo->prepare("INSERT INTO habilidades (usuario_id, tecnica, posicao, status, data_conquista) 
                                      VALUES (:u, :t, :p, :s, NOW())");
                $ins->execute([
                    ':u' => $aluno_id,
                    ':t' => $tec_nome,
                    ':p' => $tec_cat,
                    ':s' => $status_selo
                ]);
            } else {
                // Atualiza status do selo (Ex: de 'Em Estudo' para 'Concluído')
                $upd = $pdo->prepare("UPDATE habilidades SET status = :s, posicao = :p, data_conquista = NOW() WHERE usuario_id = :u AND tecnica = :t");
                $upd->execute([
                    ':s' => $status_selo,
                    ':p' => $tec_cat,
                    ':u' => $aluno_id,
                    ':t' => $tec_nome
                ]);
            }
        }

        // D. LOG DE AUDITORIA
        $resumo_log = "Aula: " . ($dados_licao['titulo'] ?? 'Técnica');
        $ins_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES (:user, :acao, NOW())");
        $ins_log->execute([
            ':user' => $nome_prof,
            ':acao' => "Lançou Plano Coletivo ($resumo_log) para o aluno $aluno_nome"
        ]);
    }

    $pdo->commit();

    // Feedback de sucesso
    echo "<script>alert('OSS! Plano de aula e presenças processados com sucesso.'); window.location='index.php';</script>";
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Erro técnico ao processar banco de dados: " . $e->getMessage());
}
