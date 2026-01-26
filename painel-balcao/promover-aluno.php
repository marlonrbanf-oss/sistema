<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE SEGURANÇA (Professor ou Admin)
if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "Acesso negado.";
    exit();
}

// RECEBIMENTO DOS DADOS
$id_aluno   = $_POST['id_aluno']   ?? null;
$nova_faixa = $_POST['nova_faixa'] ?? null;
$novo_grau  = $_POST['novo_grau']  ?? 0;
$data_hoje  = date('Y-m-d');
$instrutor  = $_SESSION['nome_usuario'];

if (!$id_aluno) {
    echo "Erro: ID do aluno não informado.";
    exit();
}

try {
    // 1. BUSCA DADOS ATUAIS ANTES DE MUDAR (Para compor o histórico)
    $q_ant = $pdo->prepare("SELECT cor_faixa, graus FROM graduacoes WHERE usuario_id = :id");
    $q_ant->execute([':id' => $id_aluno]);
    $antigo = $q_ant->fetch(PDO::FETCH_ASSOC);

    // Valores padrão caso não exista registro anterior
    $faixa_velha = $antigo['cor_faixa'] ?? 'Branca';
    $grau_velho  = $antigo['graus']     ?? 0;

    // 2. ATUALIZA OU INSERE NA TABELA DE GRADUAÇÕES
    // Verificamos se o registro existe para decidir entre UPDATE ou INSERT
    if ($antigo) {
        $query = $pdo->prepare("UPDATE graduacoes SET 
            cor_faixa = :faixa, 
            graus = :graus, 
            total_aulas = 0, 
            data_ultima_graduacao = :data 
            WHERE usuario_id = :id");
    } else {
        $query = $pdo->prepare("INSERT INTO graduacoes (usuario_id, cor_faixa, graus, total_aulas, data_ultima_graduacao) 
                                VALUES (:id, :faixa, :graus, 0, :data)");
    }

    $query->execute([
        ':faixa' => $nova_faixa,
        ':graus' => $novo_grau,
        ':data'  => $data_hoje,
        ':id'    => $id_aluno
    ]);

    // 3. REGISTRA NO HISTÓRICO
    // Nota: Certifique-se de que a tabela 'historico_graduacoes' existe no seu banco
    try {
        $log = $pdo->prepare("INSERT INTO historico_graduacoes (usuario_id, faixa_antiga, faixa_nova, data, instrutor) 
                              VALUES (:u, :ant, :nova, :dt, :inst)");
        $log->execute([
            ':u'    => $id_aluno,
            ':ant'  => $faixa_velha . " (" . $grau_velho . "G)",
            ':nova' => $nova_faixa . " (" . $novo_grau . "G)",
            ':dt'   => $data_hoje,
            ':inst' => $instrutor
        ]);
    } catch (Exception $e_log) {
        // Se a tabela de histórico não existir, o sistema ainda assim completa a promoção
        // mas você pode logar o erro internamente se desejar.
    }

    echo "Sucesso";
} catch (PDOException $e) {
    echo "Erro no banco de dados: " . $e->getMessage();
}
