<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE SEGURANÇA
// Verificamos se o usuário tem permissão para realizar esta ação
if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "Acesso negado.";
    exit();
}

// 2. RECEBIMENTO E LIMPEZA DOS DADOS
$id_aluno   = $_POST['id_aluno']   ?? null;
$nova_faixa = $_POST['nova_faixa'] ?? null;
$novo_grau  = $_POST['novo_grau']  ?? 0;
$data_hoje  = date('Y-m-d');
$instrutor  = $_SESSION['nome_usuario'] ?? 'Sistema';

// Validação básica de obrigatoriedade
if (!$id_aluno || !$nova_faixa) {
    echo "Erro: Dados incompletos para a promoção.";
    exit();
}

try {
    // 3. BUSCA DADOS ATUAIS (Para registrar o histórico corretamente)
    $q_ant = $pdo->prepare("SELECT cor_faixa, graus FROM graduacoes WHERE usuario_id = :id");
    $q_ant->execute([':id' => $id_aluno]);
    $antigo = $q_ant->fetch(PDO::FETCH_ASSOC);

    // Definimos os valores da graduação antiga para o log
    $faixa_velha = $antigo['cor_faixa'] ?? 'Branca';
    $grau_velho  = $antigo['graus']     ?? 0;

    // 4. ATUALIZAÇÃO DO STATUS ATUAL (Resetando aulas)
    if ($antigo) {
        // Se já existe registro, atualizamos e zeramos o contador de aulas
        $query = $pdo->prepare("UPDATE graduacoes SET 
            cor_faixa = :faixa, 
            graus = :graus, 
            total_aulas = 0, 
            data_ultima_graduacao = :data 
            WHERE usuario_id = :id");
    } else {
        // Se for a primeira graduação registrada no sistema
        $query = $pdo->prepare("INSERT INTO graduacoes (usuario_id, cor_faixa, graus, total_aulas, data_ultima_graduacao) 
                                VALUES (:id, :faixa, :graus, 0, :data)");
    }

    $query->execute([
        ':faixa' => $nova_faixa,
        ':graus' => $novo_grau,
        ':data'  => $data_hoje,
        ':id'    => $id_aluno
    ]);

    // 5. REGISTRO NO HISTÓRICO (Log de Auditoria)
    try {
        $log = $pdo->prepare("INSERT INTO historico_graduacoes (usuario_id, faixa_antiga, faixa_nova, data, instrutor) 
                              VALUES (:u, :ant, :nova, :dt, :inst)");

        $log_antigo = $faixa_velha . " (" . $grau_velho . "G)";
        $log_novo   = $nova_faixa . " (" . $novo_grau . "G)";

        $log->execute([
            ':u'    => $id_aluno,
            ':ant'  => $log_antigo,
            ':nova' => $log_novo,
            ':dt'   => $data_hoje,
            ':inst' => $instrutor
        ]);
    } catch (Exception $e_log) {
        // Caso a tabela de histórico não exista, não travamos o processo principal.
        // O erro é ignorado silenciosamente para garantir a experiência do usuário.
    }

    // RESPOSTA ÚNICA PARA O AJAX
    echo "Sucesso";
} catch (PDOException $e) {
    echo "Erro no banco de dados: " . $e->getMessage();
}
