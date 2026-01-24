<?php
@session_start();
require_once("../conexao.php");

// --- REPARADOR DE BANCO (Garante colunas essenciais) ---
$colunas = [
    'data_inicio' => "DATE NULL",
    'repeticoes' => "INT DEFAULT 0",
    'parceiro_id' => "INT NULL",
    'status' => "VARCHAR(50) DEFAULT 'Em Estudo'"
];
foreach ($colunas as $col => $def) {
    try {
        $pdo->query("SELECT $col FROM tecnicas_estudo LIMIT 1");
    } catch (Exception $e) {
        $pdo->query("ALTER TABLE tecnicas_estudo ADD COLUMN $col $def");
    }
}

$id_usuario = $_SESSION['id_usuario'];

// Lógica de Registro de Repetições (Linha 70 corrigida)
if (isset($_POST['reps'])) {
    $id_tec = $_POST['id_tecnica'];
    $reps_novas = (int)$_POST['reps'];
    // Uso de null coalescing (??) para evitar o erro "Undefined array key"
    $parceiro_nome = trim($_POST['parceiro'] ?? '');

    $query = $pdo->prepare("UPDATE tecnicas_estudo SET repeticoes = repeticoes + :r WHERE id = :id");
    $query->bindValue(":r", $reps_novas);
    $query->bindValue(":id", $id_tec);
    $query->execute();
    echo "<script>window.location='minhas-tecnicas.php'</script>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <title>Minhas Técnicas</title>
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <?php include_once("sidebar.php"); ?>
        <div class="content-wrapper p-3">
            <section class="content">
                <div class="card card-dark">
                    <div class="card-header">
                        <h3 class="card-title">O QUE ESTÁ ESTUDANDO HOJE?</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="index.php">
                            <input type="text" name="nova_tecnica_estudo" class="form-control" placeholder="Ex: Raspagem de Meia Guarda...">
                        </form>
                    </div>
                </div>

                <div class="row">
                    <?php
                    $res = $pdo->query("SELECT * FROM tecnicas_estudo WHERE usuario_id = '$id_usuario' AND status = 'Em Estudo'");
                    $dados = $res->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($dados as $item) { ?>
                        <div class="col-md-4">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h5 class="font-weight-bold"><?php echo $item['nome_tecnica']; ?></h5>
                                    <p>Reps: <span class="badge badge-success"><?php echo $item['repeticoes']; ?></span></p>
                                    <form method="POST">
                                        <input type="hidden" name="id_tecnica" value="<?php echo $item['id']; ?>">
                                        <div class="row">
                                            <div class="col-4"><input type="number" name="reps" class="form-control" placeholder="Qtd"></div>
                                            <div class="col-8"><input type="text" name="parceiro" class="form-control" placeholder="Parceiro..."></div>
                                        </div>
                                        <button class="btn btn-success btn-block mt-2">REGISTRAR</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </section>
        </div>
    </div>
</body>

</html>