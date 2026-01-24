<?php

/**
 * Verifica os requisitos de graduação de um aluno (Jiu-Jitsu)
 * Retorna faixa, grau, progresso de aulas e status de promoção.
 */
function verificarRequisitos($pdo, $id_usuario)
{
    try {
        // 1. Busca os dados de graduação
        // Adicionada verificação dinâmica para suportar 'cor_faixa' ou apenas 'faixa'
        $query = $pdo->prepare("SELECT * FROM graduacoes WHERE usuario_id = :id LIMIT 1");
        $query->bindValue(":id", $id_usuario);
        $query->execute();
        $grad = $query->fetch(PDO::FETCH_ASSOC);

        // Se o registro não existir no banco, retorna o padrão iniciante
        if (!$grad) {
            return [
                'faixa' => 'branca',
                'grau' => 0,
                'aulas' => 0,
                'meta' => 30,
                'pode' => false,
                'mensagem' => 'Inicie seus treinos para começar a evoluir! OSS.'
            ];
        }

        // 2. NORMALIZAÇÃO E TRADUÇÃO
        // Tenta pegar 'cor_faixa' (seu original), se não existir tenta 'faixa' (comum em backups)
        $faixa_bruta = $grad['cor_faixa'] ?? ($grad['faixa'] ?? 'branca');
        $faixa_banco = mb_strtolower(trim($faixa_bruta));

        $traducoes = [
            'white' => 'branca',
            'blue'  => 'azul',
            'purple' => 'roxa',
            'brown' => 'marrom',
            'black' => 'preta',
            'coral' => 'coral'
        ];

        $faixa = $traducoes[$faixa_banco] ?? $faixa_banco;

        // Normalização de colunas numéricas
        $grau_atual = (int)($grad['graus'] ?? ($grad['grau'] ?? 0));
        $aulas_concluidas = (int)($grad['total_aulas'] ?? ($grad['aulas'] ?? 0));

        // 3. DEFINIÇÃO DAS METAS DE AULAS (Regras Nova Arte BJJ)
        if ($faixa == 'branca') {
            $meta = 30;
        } elseif ($faixa == 'azul') {
            $meta = 65;
        } elseif ($faixa == 'roxa' || $faixa == 'marrom') {
            $meta = 75;
        } elseif ($faixa == 'preta') {
            // Lógica de graus na faixa preta
            if ($grau_atual < 3) $meta = 350;
            elseif ($grau_atual < 6) $meta = 600;
            else $meta = 1000;
        } elseif ($faixa == 'coral') {
            $meta = 1000;
        } elseif ($faixa == 'vermelha e branca') {
            $meta = 1500;
        } elseif ($faixa == 'vermelha') {
            $meta = 2000;
        } else {
            $meta = 100; // Valor padrão para segurança
        }

        // 4. VERIFICAÇÃO DE PROMOÇÃO
        $pode_graduar = ($aulas_concluidas >= $meta);
        $mensagem = "";

        if ($pode_graduar) {
            $mensagem = "Parabéns! Você atingiu a meta de $meta aulas. Procure seu professor para validar sua próxima graduação! OSS.";
        } else {
            $restantes = $meta - $aulas_concluidas;
            $mensagem = "Faltam apenas $restantes aulas para sua próxima conquista. Mantenha o foco!";
        }

        return [
            'faixa' => $faixa,
            'grau'  => $grau_atual,
            'aulas' => $aulas_concluidas,
            'meta'  => $meta,
            'pode'  => $pode_graduar,
            'mensagem' => $mensagem
        ];
    } catch (Exception $e) {
        // Retorno de segurança para não quebrar o painel do aluno
        return [
            'faixa' => 'branca',
            'grau'  => 0,
            'aulas' => 0,
            'meta'  => 30,
            'pode'  => false,
            'mensagem' => 'Erro ao carregar graduação. Verifique sua conexão.'
        ];
    }
}
