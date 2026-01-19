<?php
require_once("../conexao.php");
@session_start();

$hoje = date('Y-m-d');
$busca = "%" . @$_POST['txt_busca'] . "%";

// Função para garantir que o CSS entenda a cor vinda do banco (Igual ao usuarios.php)
function tratarCorFaixa($cor)
{
    $c = mb_strtolower(trim($cor));
    $mapa = [
        'branca' => 'white',
        'azul'   => 'blue',
        'roxa'   => 'purple',
        'marrom' => '#8B4513',
        'preta'  => 'black',
        'verde'  => 'green',
        'amarela' => 'yellow'
    ];
    return $mapa[$c] ?? $c;
}

// Procura alunos pelo nome ou e-mail (usuario)
$query = $pdo->prepare("SELECT u.id, u.nome, u.usuario, u.data_venc, g.cor_faixa, g.graus, g.total_aulas 
                      FROM usuarios u 
                      LEFT JOIN graduacoes g ON u.id = g.usuario_id 
                      WHERE u.nivel = 'Cliente' AND u.ativo = 'Sim' AND (u.nome LIKE :busca OR u.usuario LIKE :busca) 
                      ORDER BY u.nome ASC");
$query->bindValue(":busca", $busca);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($res) > 0) {
    foreach ($res as $aluno) {
        $id = $aluno['id'];

        // TRATAMENTO DA COR PARA O CSS
        $cor_banco = $aluno['cor_faixa'] ?? 'white';
        $cor_css = tratarCorFaixa($cor_banco);
        $texto_cor = ($cor_css == 'white' || $cor_css == 'yellow') ? '#000' : '#fff';

        // Lógica de Atrasado
        $vencido = (strtotime($aluno['data_venc'] ?? '') < strtotime($hoje)) ? true : false;
        $classe_atrasado = $vencido ? "style='border-left: 5px solid #dc3545;'" : "";
        $badge_pendente = $vencido ? "<span class='badge badge-danger'>PENDENTE</span>" : "";

        echo "<tr {$classe_atrasado}>
                <td>
                    <strong>" . mb_strtoupper($aluno['nome']) . "</strong> {$badge_pendente}<br>
                    <small class='text-muted'>{$aluno['usuario']}</small>
                </td>
                <td>
                    <span class='badge' style='background-color:{$cor_css}; color:{$texto_cor}; border:1px solid #333; padding: 5px 10px; min-width: 80px;'>
                        " . strtoupper($cor_banco) . " ({$aluno['graus']}G)
                    </span>
                </td>
                <td class='text-center font-weight-bold'>{$aluno['total_aulas']}</td>
                <td class='text-center'>
                    <a href='registrar_presenca.php?id={$id}' class='btn btn-success btn-sm px-3 shadow-sm'>
                        <i class='fas fa-check'></i> PRESENÇA
                    </a>
                    <a href='lancar-tecnica.php?id={$id}' class='btn btn-sm px-3 shadow-sm' style='background-color: #6f42c1; color: white;'>
                        <i class='fas fa-stamp'></i> SELO
                    </a>
                </td>
            </tr>";
    }
} else {
    echo "<tr><td colspan='4' class='text-center text-muted p-4'>Nenhum aluno encontrado com este nome.</td></tr>";
}
