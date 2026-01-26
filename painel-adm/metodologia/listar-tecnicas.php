<?php
require_once("../../conexao.php");
$licao_id = $_POST['id'];

$query = $pdo->query("SELECT * FROM tecnicas WHERE licao_id = '$licao_id' ORDER BY id ASC");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($res) > 0) {
    foreach ($res as $item) {
        echo '<div class="tecnica-item">
                <span><i class="fas fa-chevron-right text-muted mr-2"></i> ' . $item['nome'] . '</span>
                <button class="btn btn-outline-danger btn-xs" onclick="excluirTecnica(' . $item['id'] . ')">
                    <i class="fas fa-times"></i>
                </button>
              </div>';
    }
} else {
    echo '<p class="text-muted">Nenhuma técnica cadastrada para esta lição.</p>';
}
