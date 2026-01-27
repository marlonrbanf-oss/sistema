<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE SEGURANÇA
// Verificamos se o usuário está logado e se possui o nível de permissão necessário
if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "Acesso negado. Apenas Professores ou Administradores podem promover alunos.";
    exit();
}

// 2. RECEBIMENTO E TRATAMENTO DOS DADOS
$id_aluno   = $_POST['id_aluno']   ?? null;
$nova_faixa = $_POST['nova_faixa'] ?? null;
$novo_grau  = $_POST['novo_grau']  ?? 0;
$data_hoje  = date('Y-m-d');
$instrutor  = $_SESSION['nome_usuario'] ?? 'Sistema';

// Validação básica de obrigatoriedade
if (!$id_aluno || !$nova_faixa) {
    echo "Erro: Selecione o aluno e a nova faixa.";
    exit();
}

try {
    // INÍCIO DA TRANSAÇÃO - Garante que todas as queries funcionem ou nenhuma funcione
    $pdo->beginTransaction();

    // 3. BUSCA DADOS ATUAIS (Para registrar o histórico corretamente antes da mudança)
    $q_ant = $pdo->prepare("SELECT cor_faixa, graus FROM graduacoes WHERE usuario_id = :id");
    $q_ant->execute([':id' => $id_aluno]);
    $antigo = $q_ant->fetch(PDO::FETCH_ASSOC);

    // Definimos os valores da graduação antiga para o log (padrão 'Branca 0G' se não existir)
    $faixa_velha = $antigo['cor_faixa'] ?? 'Branca';
    $grau_velho  = $antigo['graus']     ?? 0;

    // 4. ATUALIZAÇÃO OU INSERÇÃO DO STATUS ATUAL
    // Resetamos o contador de aulas sempre que há uma mudança de faixa/grau
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

    // 5. REGISTRO NO HISTÓRICO (Log de Auditoria)
    // Criamos uma string legível para o histórico: Ex: "Branca (0G) -> Azul (1G)"
    $log_antigo = $faixa_velha . " (" . $grau_velho . "G)";
    $log_novo   = $nova_faixa . " (" . $novo_grau . "G)";

    $log = $pdo->prepare("INSERT INTO historico_graduacoes (usuario_id, faixa_antiga, faixa_nova, data, instrutor) 
                          VALUES (:u, :ant, :nova, :dt, :inst)");

    $log->execute([
        ':u'    => $id_aluno,
        ':ant'  => $log_antigo,
        ':nova' => $log_novo,
        ':dt'   => $data_hoje,
        ':inst' => $instrutor
    ]);

    // SE CHEGOU ATÉ AQUI SEM ERROS, CONFIRMA AS MUDANÇAS NO BANCO
    $pdo->commit();

    // RESPOSTA PARA O AJAX (Verificada no Success do JavaScript)
    echo "Sucesso";
} catch (Exception $e) {
    // EM CASO DE QUALQUER ERRO, DESFAZ TUDO O QUE FOI FEITO NO BANCO (Rollback)
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Erro ao processar promoção: " . $e->getMessage();
}
