<?php
@session_start();
require_once("../conexao.php");

// Verificação de Acesso
if (!isset($_SESSION['nivel_usuario']) || $_SESSION['nivel_usuario'] != 'Cliente') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// --- LÓGICA 1: ADICIONAR TÉCNICA (ORIGINAL COM SUPORTE AO DIÁRIO) ---
if (isset($_POST['nome_tecnica']) && !isset($_POST['id_log'])) {
    $nome_tecnica = trim($_POST['nome_tecnica']);

    if (!empty($nome_tecnica)) {
        // Verifica se já está estudando essa técnica para não duplicar
        $query_check = $pdo->prepare("SELECT id FROM tecnicas_estudo WHERE usuario_id = :id AND nome_tecnica = :nome AND status = 'Em Estudo'");
        $query_check->bindValue(":id", $id_usuario);
        $query_check->bindValue(":nome", $nome_tecnica);
        $query_check->execute();

        if ($query_check->rowCount() == 0) {
            // Insere com repeticoes zeradas para iniciar o diário
            $res = $pdo->prepare("INSERT INTO tecnicas_estudo SET usuario_id = :id, nome_tecnica = :nome, status = 'Em Estudo', repeticoes = 0, data_inicio = CURDATE()");
            $res->bindValue(":id", $id_usuario);
            $res->bindValue(":nome", $nome_tecnica);
            $res->execute();
            echo "<script>alert('Técnica adicionada ao seu Diário!'); window.location='minhas-tecnicas.php'</script>";
        } else {
            echo "<script>alert('Você já está estudando esta técnica!'); window.location='minhas-tecnicas.php'</script>";
        }
    }
}

// --- LÓGICA 2: REGISTRAR PRÁTICA (SOMA REPETIÇÕES E SALVA PARCEIRO) ---
if (isset($_POST['id_log'])) {
    $id_registro = $_POST['id_log'];
    $novas_reps = (int)$_POST['quantidade_reps'];
    $parceiro = trim($_POST['parceiro']);

    if ($novas_reps > 0) {
        $res = $pdo->prepare("UPDATE tecnicas_estudo SET repeticoes = repeticoes + :reps, ultimo_parceiro = :parceiro, data_ultima_pratica = CURDATE() WHERE id = :id AND usuario_id = :user_id");
        $res->bindValue(":reps", $novas_reps);
        $res->bindValue(":parceiro", $parceiro);
        $res->bindValue(":id", $id_registro);
        $res->bindValue(":user_id", $id_usuario);
        $res->execute();
        echo "<script>window.location='minhas-tecnicas.php'</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Minhas Técnicas - Nova Arte BJJ</title>
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <style>
        .posicao-header {
            background: #343a40;
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: bold;
        }

        .card-tecnica {
            transition: 0.3s;
            border-left: 4px solid #007bff;
            height: 100%;
        }

        .card-tecnica:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important;
        }

        .stats-diario {
            background: #f4f6f9;
            padding: 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-bottom: 10px;
            border: 1px solid #ddd;
        }

        .badge-repeticoes {
            font-size: 0.9rem;
            background-color: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed text-sm">
    <div class="wrapper">

        <?php include_once("sidebar.php"); ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1><i class="fas fa-book-open mr-2"></i>Meu Diário Técnico</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">

                    <div class="card card-primary card-outline shadow-sm mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><b>O que deseja aprender hoje?</b></h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="input-group">
                                    <input type="text" name="nome_tecnica" class="form-control"
                                        placeholder="Digite uma técnica personalizada (Ex: De la Riva invertida...)"
                                        required>
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-plus mr-1"></i> ADICIONAR AO DIÁRIO
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary"><b><i class="fas fa-history mr-2"></i>Progresso de Repetições</b></h5>
                    <div class="row">
                        <?php
                        $query_progresso = $pdo->prepare("SELECT * FROM tecnicas_estudo WHERE usuario_id = :id AND status = 'Em Estudo' ORDER BY id DESC");
                        $query_progresso->bindValue(":id", $id_usuario);
                        $query_progresso->execute();
                        $res_prog = $query_progresso->fetchAll(PDO::FETCH_ASSOC);

                        if (count($res_prog) > 0) {
                            foreach ($res_prog as $t) {
                                // CORREÇÃO: Tratamento para evitar erro de Array Key Undefined
                                $nome_exibir = $t['nome_tecnica'] ?? 'Técnica sem nome';
                                $reps_exibir = $t['repeticoes'] ?? 0;
                                $parceiro_exibir = $t['ultimo_parceiro'] ?? 'Não registrado';
                                $id_tec = $t['id'] ?? 0;
                        ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card card-tecnica shadow-sm">
                                        <div class="card-body">
                                            <h6 class="mb-2">
                                                <strong><?php echo htmlspecialchars($nome_exibir); ?></strong>
                                            </h6>
                                            <div class="stats-diario">
                                                <i class="fas fa-redo-alt mr-1"></i> Total: <span
                                                    class="badge-repeticoes"><?php echo $reps_exibir; ?></span><br>
                                                <small class="text-muted">
                                                    <i class="fas fa-user mr-1"></i> Parceiro:
                                                    <?php echo htmlspecialchars($parceiro_exibir); ?>
                                                </small>
                                            </div>

                                            <form method="POST" class="mt-2 p-2 border rounded bg-light">
                                                <input type="hidden" name="id_log" value="<?php echo $id_tec; ?>">
                                                <div class="row no-gutters">
                                                    <div class="col-4">
                                                        <input type="number" name="quantidade_reps"
                                                            class="form-control form-control-sm" placeholder="Reps" required
                                                            min="1">
                                                    </div>
                                                    <div class="col-8 pl-1">
                                                        <input type="text" name="parceiro" class="form-control form-control-sm"
                                                            placeholder="Com quem?">
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-xs btn-block btn-success mt-2">
                                                    <i class="fas fa-check mr-1"></i> Salvar Prática
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                        <?php }
                        } else {
                            echo "<div class='col-12'><p class='text-muted ml-2'>Você ainda não adicionou técnicas para praticar hoje.</p></div>";
                        } ?>
                    </div>

                    <hr class="mt-4 mb-4">

                    <h5 class="mb-3"><b>Técnicas Sugeridas para sua Graduação</b></h5>
                    <?php
                    $query_hab = $pdo->prepare("SELECT * FROM habilidades WHERE usuario_id = :id ORDER BY posicao ASC, tecnica ASC");
                    $query_hab->bindValue(":id", $id_usuario);
                    $query_hab->execute();
                    $res_hab = $query_hab->fetchAll(PDO::FETCH_ASSOC);

                    if (count($res_hab) > 0) {
                        $grupos = [];
                        foreach ($res_hab as $hab) {
                            $posicao_label = (!empty($hab['posicao'])) ? $hab['posicao'] : 'OUTRAS POSIÇÕES';
                            $grupos[$posicao_label][] = $hab;
                        }

                        foreach ($grupos as $posicao_titulo => $tecnicas) { ?>
                            <div class="posicao-header">
                                <i class="fas fa-chevron-right mr-2"></i>
                                <?php echo strtoupper(htmlspecialchars($posicao_titulo)); ?>
                            </div>
                            <div class="row mt-2">
                                <?php foreach ($tecnicas as $hab) {
                                    $status_nome = mb_strtolower($hab['status'] ?? '');
                                    $ja_dominado = ($status_nome == 'dominado' || $status_nome == 'concluído');
                                    $nome_hab = $hab['tecnica'] ?? 'Técnica';
                                ?>
                                    <div class="col-md-4 col-sm-6 mb-2">
                                        <div
                                            class="card shadow-sm border-left <?php echo $ja_dominado ? 'border-success' : 'border-gray'; ?>">
                                            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong
                                                        class="text-dark d-block"><?php echo htmlspecialchars($nome_hab); ?></strong>
                                                    <?php if ($ja_dominado): ?>
                                                        <span class="badge badge-success"><i class="fas fa-check"></i> Dominado</span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if (!$ja_dominado): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="nome_tecnica"
                                                            value="<?php echo htmlspecialchars($nome_hab); ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-book-open"></i> Estudar
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                    <?php }
                    } else {
                        echo "<div class='alert alert-info'>Nenhuma técnica sugerida para sua graduação atual.</div>";
                    }
                    ?>

                </div>
            </section>
        </div>
    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.js"></script>
</body>

</html>