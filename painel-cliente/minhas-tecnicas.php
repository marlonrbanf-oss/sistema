<?php
@session_start();
require_once("../conexao.php");

// Verificação de Acesso - Padronizada com autenticar.php
if (!isset($_SESSION['nivel_usuario']) || ($_SESSION['nivel_usuario'] != 'Cliente' && $_SESSION['nivel_usuario'] != 'Atleta')) {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// --- LÓGICA DE APROVAÇÃO DE PARCEIRO ---
if (isset($_GET['acao']) && $_GET['acao'] == 'aprovar' && isset($_GET['id_log'])) {
    $id_log = $_GET['id_log'];
    $res = $pdo->prepare("UPDATE tecnicas_estudo SET parceiro_confirmado = 'Sim' WHERE id = :id AND ultimo_parceiro_id = :meu_id");
    $res->bindValue(":id", $id_log);
    $res->bindValue(":meu_id", $id_usuario);
    $res->execute();
    echo "<script>window.location='minhas-tecnicas.php'</script>";
}

// --- LÓGICA 1: ADICIONAR TÉCNICA ---
if (isset($_POST['nome_tecnica']) && !isset($_POST['id_log'])) {
    $nome_tecnica = trim($_POST['nome_tecnica']);
    if (!empty($nome_tecnica)) {
        $query_check = $pdo->prepare("SELECT id FROM tecnicas_estudo WHERE usuario_id = :id AND nome_tecnica = :nome AND status = 'Em Estudo'");
        $query_check->bindValue(":id", $id_usuario);
        $query_check->bindValue(":nome", $nome_tecnica);
        $query_check->execute();

        if ($query_check->rowCount() == 0) {
            $res = $pdo->prepare("INSERT INTO tecnicas_estudo SET usuario_id = :id, nome_tecnica = :nome, status = 'Em Estudo', repeticoes = 0, data_inicio = CURDATE()");
            $res->bindValue(":id", $id_usuario);
            $res->bindValue(":nome", $nome_tecnica);
            $res->execute();
            echo "<script>alert('Técnica adicionada!'); window.location='minhas-tecnicas.php'</script>";
        } else {
            echo "<script>alert('Você já está estudando esta técnica!'); window.location='minhas-tecnicas.php'</script>";
        }
    }
}

// --- LÓGICA 2: REGISTRAR PRÁTICA ---
if (isset($_POST['id_log'])) {
    $id_registro = $_POST['id_log'];
    $novas_reps = (int)$_POST['quantidade_reps'];
    $parceiro_nome = trim($_POST['parceiro']);
    $parceiro_id = !empty($_POST['parceiro_id']) ? $_POST['parceiro_id'] : null;

    if ($novas_reps > 0) {
        $res = $pdo->prepare("UPDATE tecnicas_estudo SET repeticoes = repeticoes + :reps, ultimo_parceiro = :parceiro, ultimo_parceiro_id = :p_id, data_ultima_pratica = CURDATE(), parceiro_confirmado = 'Não' WHERE id = :id AND usuario_id = :user_id");
        $res->bindValue(":reps", $novas_reps);
        $res->bindValue(":parceiro", $parceiro_nome);
        $res->bindValue(":p_id", $parceiro_id);
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

        .lista-busca {
            position: absolute;
            z-index: 1000;
            width: 100%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed text-sm">
    <div class="wrapper">
        <?php include_once("sidebar.php"); ?>
        <div class="content-wrapper">
            <section class="content pt-3">
                <div class="container-fluid">

                    <?php
                    $query_solic = $pdo->prepare("SELECT t.*, u.nome as aluno FROM tecnicas_estudo t INNER JOIN usuarios u ON t.usuario_id = u.id WHERE t.ultimo_parceiro_id = :id AND t.parceiro_confirmado = 'Não'");
                    $query_solic->bindValue(":id", $id_usuario);
                    $query_solic->execute();
                    $solics = $query_solic->fetchAll(PDO::FETCH_ASSOC);

                    if (count($solics) > 0): ?>
                        <div class="alert alert-warning shadow-sm">
                            <h5><i class="fas fa-users mr-2"></i> Solicitações de Parceiro</h5>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($solics as $s): ?>
                                    <li class="d-flex justify-content-between align-items-center border-bottom py-2">
                                        <span><strong><?php echo htmlspecialchars($s['aluno']); ?></strong> treinou
                                            <strong><?php echo htmlspecialchars($s['nome_tecnica']); ?></strong> com
                                            você.</span>
                                        <a href="minhas-tecnicas.php?acao=aprovar&id_log=<?php echo $s['id']; ?>"
                                            class="btn btn-xs btn-success shadow-sm">Confirmar</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="card card-primary card-outline shadow-sm mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><b>O que deseja aprender hoje?</b></h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="input-group">
                                    <input type="text" name="nome_tecnica" class="form-control"
                                        placeholder="Ex: De la Riva invertida..." required>
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit"><i class="fas fa-plus mr-1"></i>
                                            ADICIONAR</button>
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
                                $reps = isset($t['repeticoes']) ? $t['repeticoes'] : 0;
                                $parceiro = !empty($t['ultimo_parceiro']) ? $t['ultimo_parceiro'] : 'Não registrado';
                        ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card card-tecnica shadow-sm">
                                        <div class="card-body">
                                            <h6 class="mb-2">
                                                <strong><?php echo htmlspecialchars($t['nome_tecnica'] ?? 'Técnica'); ?></strong>
                                            </h6>
                                            <div class="stats-diario">
                                                <i class="fas fa-redo-alt mr-1"></i> Total: <span
                                                    class="badge-repeticoes"><?php echo $reps; ?></span><br>
                                                <small class="text-muted"><i class="fas fa-user mr-1"></i> Parceiro:
                                                    <?php echo htmlspecialchars($parceiro); ?></small>
                                            </div>

                                            <form method="POST" class="mt-2 p-2 border rounded bg-light">
                                                <input type="hidden" name="id_log" value="<?php echo $t['id']; ?>">
                                                <div class="row no-gutters">
                                                    <div class="col-3">
                                                        <input type="number" name="quantidade_reps"
                                                            class="form-control form-control-sm" placeholder="Reps" required
                                                            min="1">
                                                    </div>
                                                    <div class="col-9 pl-1 position-relative">
                                                        <input type="text" name="parceiro"
                                                            class="form-control form-control-sm input-busca-aluno"
                                                            placeholder="Quem treinou?" autocomplete="off">
                                                        <input type="hidden" name="parceiro_id" class="aluno-id-hidden">
                                                        <div class="list-group lista-busca d-none"></div>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-xs btn-block btn-success mt-2"><i
                                                        class="fas fa-check mr-1"></i> Salvar Prática</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                        <?php }
                        } else {
                            echo "<div class='col-12'><p class='text-muted'>Nenhuma técnica em estudo no momento.</p></div>";
                        } ?>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.js"></script>

    <script>
        $(document).ready(function() {
            $('.input-busca-aluno').on('keyup', function() {
                let query = $(this).val();
                let lista = $(this).siblings('.lista-busca');
                if (query.length > 2) {
                    $.ajax({
                        url: 'buscar_alunos.php',
                        method: 'POST',
                        data: {
                            query: query
                        },
                        success: function(data) {
                            lista.removeClass('d-none').html(data);
                        }
                    });
                } else {
                    lista.addClass('d-none');
                }
            });

            $(document).on('click', '.item-aluno', function(e) {
                e.preventDefault();
                let nome = $(this).data('nome');
                let id = $(this).data('id');
                let form_div = $(this).closest('.position-relative');
                form_div.find('.input-busca-aluno').val(nome);
                form_div.find('.aluno-id-hidden').val(id);
                form_div.find('.lista-busca').addClass('d-none');
            });

            $(document).click(function(e) {
                if (!$(e.target).closest('.position-relative').length) {
                    $('.lista-busca').addClass('d-none');
                }
            });
        });
    </script>
</body>

</html>