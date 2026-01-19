<?php
function verificarRequisitos($pdo, $id_usuario)
{
    // 1. Busca os dados de graduação
    $query = $pdo->prepare("SELECT * FROM graduacoes WHERE usuario_id = :id LIMIT 1");
    $query->bindValue(":id", $id_usuario);
    $query->execute();
    $grad = $query->fetch(PDO::FETCH_ASSOC);

    if (!$grad) {
        return ['faixa' => 'branca', 'grau' => 0, 'aulas' => 0, 'meta' => 30, 'pode' => false];
    }

    // Normaliza a faixa: tira espaços, converte para minúsculo e traduz cores básicas
    $faixa_banco = mb_strtolower(trim($grad['cor_faixa']));

    $traducoes = [
        'white' => 'branca',
        'blue'  => 'azul',
        'purple' => 'roxa',
        'brown' => 'marrom',
        'black' => 'preta'
    ];

    $faixa = $traducoes[$faixa_banco] ?? $faixa_banco;
    $grau_atual = (int)$grad['graus'];
    $aulas_concluidas = (int)$grad['total_aulas'];

    // 2. DEFINIÇÃO DAS METAS
    if ($faixa == 'branca') {
        $meta = 30;
    } elseif ($faixa == 'azul') {
        $meta = 65;
    } elseif ($faixa == 'roxa' || $faixa == 'marrom') {
        $meta = 75;
    } elseif ($faixa == 'preta') {
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
        $meta = 100;
    }

    return [
        'faixa' => $faixa, // Retorna sempre o nome padronizado para o CSS do index.php
        'grau'  => $grau_atual,
        'aulas' => $aulas_concluidas,
        'meta'  => $meta,
        'pode'  => ($aulas_concluidas >= $meta)
    ];
}
