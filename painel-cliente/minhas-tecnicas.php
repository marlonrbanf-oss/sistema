<?php
@session_start();
require_once("../conexao.php");

// Verificação de Acesso
if (!isset($_SESSION['nivel_usuario']) || $_SESSION['nivel_usuario'] != 'Cliente') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// --- LÓGICA PARA ADICIONAR TÉCNICA (TANTO DA LISTA QUANTO PERSONALIZADA) ---
if (isset($_POST['nome_tecnica'])) {
    $nome_tecnica = $_POST['nome_tecnica'];

    // Verifica se já está estudando essa técnica para não duplicar
    $query_check = $pdo->prepare("SELECT id FROM tecnicas_estudo WHERE usuario_id = :id AND nome_tecnica = :nome AND status = 'Em Estudo'");
    $query_check->bindValue(":id", $id_usuario);
    $query_check->bindValue(":nome", $nome_tecnica);
    $query_check->execute();

    if ($query_check->rowCount() == 0) {
        $res = $pdo->prepare("INSERT INTO tecnicas_estudo SET usuario_id = :id, nome_tecnica = :nome, status = 'Em Estudo'");
        $res->bindValue(":id", $id_usuario);
        $res->bindValue(":nome", $nome_tecnica);
        $res->execute();
        echo "<script>alert('Técnica adicionada aos seus estudos!'); window.location='minhas-tecnicas.php'</script>";
    } else {
        echo "<script>alert('Você já está estudando esta técnica!'); window.location='minhas-tecnicas.php'</script>";
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
        }

        .card-tecnica {
            transition: 0.3s;
            border-left: 4px solid #ddd;
        }

        .card-tecnica:hover {
            transform: scale(1.02);
        }

        .btn-estudar {
            background-color: #007bff;
            color: white;
            border: none;
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
                            <h1><i class="fas fa-graduation-cap mr-2"></i>Plano de Estudo Técnico</h1>
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
                                            <i class="fas fa-plus mr-1"></i> ADICIONAR AOS ESTUDOS
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <small class="text-muted">* Após adicionar, peça ao seu professor para validar com o QR Code
                                na tela inicial.</small>
                        </div>
                    </div>

                    <h5 class="mb-3"><b>Técnicas da sua Graduação</b></h5>

                    <?php
                    // Busca todas as habilidades disponíveis para o aluno (baseado no currículo)
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
                                <i class="fas fa-chevron-right mr-2"></i> <?php echo strtoupper($posicao_titulo); ?>
                            </div>
                            <div class="row mt-2">
                                <?php foreach ($tecnicas as $hab) {
                                    $status_nome = mb_strtolower($hab['status']);
                                    $ja_dominado = ($status_nome == 'dominado' || $status_nome == 'concluído');
                                ?>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="card card-tecnica shadow-sm">
                                            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong class="text-dark d-block"><?php echo $hab['tecnica']; ?></strong>
                                                    <?php if ($ja_dominado): ?>
                                                        <span class="badge badge-success"><i class="fas fa-check"></i> Dominado</span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if (!$ja_dominado): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="nome_tecnica" value="<?php echo $hab['tecnica']; ?>">
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
                        echo "<div class='alert alert-info'>Nenhuma técnica listada para sua graduação.</div>";
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