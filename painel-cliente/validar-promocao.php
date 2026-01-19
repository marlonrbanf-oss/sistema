<?php
@session_start();
require_once("../conexao.php");

// 1. CONFIGURAÇÃO DE SEGURANÇA (Sincronizado com seu QR Code do Professor)
$chave_secreta = "MINHA_NOVA_ARTE_2026";
$token_esperado = md5(date('Y-m-d') . $chave_secreta);

$token_lido = $_POST['token_professor'] ?? '';
$id_usuario = $_SESSION['id_usuario'];

// 2. VALIDAÇÃO DO TOKEN
if ($token_lido !== $token_esperado) {
    echo "QR Code Inválido ou Expirado! Peça ao mestre para atualizar o código.";
    exit();
}

try {
    // 3. BUSCAR DADOS ATUAIS DO ALUNO
    $query = $pdo->prepare("SELECT * FROM graduacoes WHERE usuario_id = :id LIMIT 1");
    $query->bindValue(":id", $id_usuario);
    $query->execute();
    $aluno = $query->fetch(PDO::FETCH_ASSOC);

    if (!$aluno) {
        echo "Erro: Registro de graduação não encontrado.";
        exit();
    }

    $faixa_atual = mb_strtolower(trim($aluno['cor_faixa']));
    $graus_atuais = (int)$aluno['graus'];

    $nova_faixa = $aluno['cor_faixa'];
    $novo_grau = 0;

    // 4. LÓGICA DE PROGRESSÃO COMPLEXA

    // --- Lógica para Faixa PRETA ---
    if ($faixa_atual == 'preta') {
        if ($graus_atuais < 6) {
            $novo_grau = $graus_atuais + 1;
            $nova_faixa = 'Preta';
        } else {
            // Após o 6º grau da preta, vai para o 7º Grau (Coral)
            $novo_grau = 7;
            $nova_faixa = 'Coral';
        }
    }
    // --- Lógica para Faixa CORAL (Vermelha e Preta) ---
    elseif ($faixa_atual == 'coral') {
        if ($graus_atuais < 7) {
            // Caso ele ainda não tenha o 7º grau (segurança de dados)
            $novo_grau = 7;
            $nova_faixa = 'Coral';
        } else {
            // Após o 7º grau da Coral, vai para o 8º Grau (Vermelha e Branca)
            $novo_grau = 8;
            $nova_faixa = 'Vermelha e Branca';
        }
    }
    // --- Lógica para Faixa Vermelha e Branca ---
    elseif ($faixa_atual == 'vermelha e branca') {
        if ($graus_atuais < 8) {
            $novo_grau = 8;
            $nova_faixa = 'Vermelha e Branca';
        } else {
            // Após o 8º grau, vai para o 9º Grau (Vermelha)
            $novo_grau = 9;
            $nova_faixa = 'Vermelha';
        }
    }
    // --- Lógica para Faixa VERMELHA ---
    elseif ($faixa_atual == 'vermelha') {
        // Apenas aumenta os graus se necessário (limite técnico do BJJ é 9º ou 10º)
        $novo_grau = $graus_atuais + 1;
        $nova_faixa = 'Vermelha';
    }
    // --- Lógica para Faixas COLORIDAS (Branca, Azul, Roxa, Marrom) ---
    else {
        if ($graus_atuais < 4) {
            $novo_grau = $graus_atuais + 1;
            $nova_faixa = $aluno['cor_faixa'];
        } else {
            // Troca de Faixa (0 graus na nova)
            $novo_grau = 0;
            if ($faixa_atual == 'branca') $nova_faixa = 'Azul';
            elseif ($faixa_atual == 'azul') $nova_faixa = 'Roxa';
            elseif ($faixa_atual == 'roxa') $nova_faixa = 'Marrom';
            elseif ($faixa_atual == 'marrom') $nova_faixa = 'Preta';
        }
    }

    // 5. ATUALIZAÇÃO NO BANCO DE DADOS
    // IMPORTANTE: Zera o total_aulas para o aluno começar a nova contagem (350, 600, 1000...)
    $upd = $pdo->prepare("UPDATE graduacoes SET 
        cor_faixa = :faixa, 
        graus = :graus, 
        total_aulas = 0 
        WHERE usuario_id = :id");

    $upd->bindValue(":faixa", $nova_faixa);
    $upd->bindValue(":graus", $novo_grau);
    $upd->bindValue(":id", $id_usuario);
    $upd->execute();

    echo "Sucesso";
} catch (Exception $e) {
    echo "Erro ao processar: " . $e->getMessage();
}
