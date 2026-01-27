<?php
require_once("../../conexao.php");

// 1. RECEBIMENTO DOS DADOS
$licao_id = $_POST['id'] ?? null;

if (!$licao_id) {
    echo '<p class="text-danger">Erro: ID da lição não fornecido.</p>';
    exit();
}

// 2. BUSCA DAS TÉCNICAS
$query = $pdo->query("SELECT * FROM tecnicas WHERE licao_id = '$licao_id' ORDER BY id ASC");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

// 3. RENDERIZAÇÃO
if (count($res) > 0) {
    foreach ($res as $item) {
?>
        <div class="tecnica-item d-flex justify-content-between align-items-center mb-2 shadow-sm">
            <span class="text-dark">
                <i class="fas fa-chevron-right text-primary mr-2" style="font-size: 0.7rem;"></i>
                <?php echo htmlspecialchars($item['nome']); ?>
            </span>
            <button class="btn btn-outline-danger btn-sm border-0" title="Excluir Técnica"
                onclick="excluirTecnica('<?php echo $item['id']; ?>', '<?php echo $licao_id; ?>')">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
<?php
    }
} else {
    echo '
    <div class="text-center py-4">
        <i class="fas fa-info-circle text-muted mb-2 fa-2x"></i>
        <p class="text-muted small">Nenhuma técnica cadastrada para esta lição.<br>Comece adicionando uma acima!</p>
    </div>';
}
?>

<script>
    function excluirTecnica(id, licao_id) {
        if (confirm("Deseja realmente remover esta técnica?")) {
            $.ajax({
                url: "metodologia/excluir-tecnica.php",
                method: "POST",
                data: {
                    id: id
                },
                success: function() {
                    listarTecnicas(licao_id); // Recarrega a lista no modal
                },
                error: function() {
                    alert("Erro ao excluir técnica.");
                }
            });
        }
    }
</script>