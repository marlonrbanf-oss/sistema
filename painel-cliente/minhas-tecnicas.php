<?php
@session_start();
require_once("../conexao.php");

// --- REPARADOR DE EMERGÊNCIA ---
$tabela = 'tecnicas_estudo';
$colunas_necessarias = [
    'usuario_id' => "INT NOT NULL",
    'nome_tecnica' => "VARCHAR(255) NOT NULL",
    'status' => "VARCHAR(50) DEFAULT 'Em Estudo'",
    'repeticoes' => "INT DEFAULT 0",
    'ultimo_parceiro' => "VARCHAR(255) NULL",
    'ultimo_parceiro_id' => "INT NULL",
    'parceiro_confirmado' => "ENUM('Sim', 'Não') DEFAULT 'Não'",
    'data_inicio' => "DATE NULL",
    'data_ultima_pratica' => "DATE NULL",
    'tipo_registro' => "ENUM('Treino', 'Combate') DEFAULT 'Treino'",
    'acao_combate' => "VARCHAR(50) NULL"
];

foreach ($colunas_necessarias as $coluna => $definicao) {
    try {
        $pdo->query("SELECT $coluna FROM $tabela LIMIT 1");
    } catch (Exception $e) {
        $pdo->query("ALTER TABLE $tabela ADD COLUMN $coluna $definicao");
    }
}

if (!isset($_SESSION['id_usuario'])) {
    echo "<script>window.location='../login.php'</script>";
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$nome_usuario = $_SESSION['nome_usuario'] ?? 'Eu';

// --- LÓGICA DE EXCLUSÃO (BOTÃO X) ---
if (isset($_GET['delete'])) {
    $res = $pdo->prepare("DELETE FROM $tabela WHERE id = :id AND usuario_id = :u_id");
    $res->execute([':id' => $_GET['delete'], ':u_id' => $id_usuario]);
    echo "<script>window.location='minhas-tecnicas.php'</script>";
    exit();
}

// --- LÓGICA DE APROVAÇÃO (DAR 3 TAPINHAS) ---
if (isset($_GET['acao']) && $_GET['acao'] == 'aprovar' && isset($_GET['id_log'])) {
    $res = $pdo->prepare("UPDATE $tabela SET parceiro_confirmado = 'Sim' WHERE id = :id AND ultimo_parceiro_id = :meu_id");
    $res->execute([':id' => $_GET['id_log'], ':meu_id' => $id_usuario]);
    echo "<script>window.location='minhas-tecnicas.php'</script>";
    exit();
}

// --- ADICIONAR NOVA TÉCNICA ---
if (isset($_POST['nome_tecnica']) && !isset($_POST['id_log'])) {
    $nome = trim($_POST['nome_tecnica']);
    if (!empty($nome)) {
        $res = $pdo->prepare("INSERT INTO $tabela (usuario_id, nome_tecnica, status, data_inicio) VALUES (:id, :nome, 'Em Estudo', CURDATE())");
        $res->execute([':id' => $id_usuario, ':nome' => $nome]);
        echo "<script>window.location='minhas-tecnicas.php'</script>";
        exit();
    }
}

// --- REGISTRAR ATIVIDADE ---
if (isset($_POST['id_log'])) {
    $tipo = $_POST['tipo_registro'];
    $reps = ($tipo == 'Treino') ? (int)$_POST['quantidade_reps'] : 1;
    $acao = ($tipo == 'Combate') ? $_POST['acao_combate'] : null;
    $res = $pdo->prepare("UPDATE $tabela SET repeticoes = repeticoes + :reps, ultimo_parceiro = :p, ultimo_parceiro_id = :pid, data_ultima_pratica = CURDATE(), parceiro_confirmado = 'Não', tipo_registro = :tipo, acao_combate = :acao WHERE id = :id AND usuario_id = :uid");
    $res->execute([':reps' => $reps, ':p' => $_POST['parceiro_nome_texto'], ':pid' => $_POST['parceiro_id'], ':tipo' => $tipo, ':acao' => $acao, ':id' => $_POST['id_log'], ':uid' => $id_usuario]);
    echo "<script>window.location='minhas-tecnicas.php'</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Minhas Técnicas - Arena BJJ</title>
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <style>
        .card-tecnica {
            border-left: 4px solid #007bff;
            position: relative;
            transition: 0.3s;
        }

        .btn-delete-x {
            position: absolute;
            top: 8px;
            right: 8px;
            color: #dc3545;
            opacity: 0.6;
            z-index: 10;
        }

        .btn-delete-x:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        .badge-reps {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
        }

        .lista-busca {
            position: absolute;
            z-index: 1050;
            width: 100%;
            background: white;
            border: 1px solid #ddd;
            max-height: 150px;
            overflow-y: auto;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .item-aluno {
            padding: 8px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            display: block;
            color: #333;
        }

        .item-aluno:hover {
            background: #f8f9fa;
        }

        .vs-badge {
            width: 40px;
            height: 40px;
            background: #000;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 auto;
            border: 2px solid #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .secao-combate {
            display: none;
        }
    </style>
</head>

<body class="hold-transition bg-light">
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="font-weight-bold">Dojo Digital</h3>
            <a href="index.php" class="btn btn-dark btn-sm shadow-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>

        <div class="card shadow-sm mb-4 border-0 card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title font-weight-bold text-uppercase" style="font-size: 0.9rem;">Placar de Confronto
                </h3>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-5 text-center">
                        <small class="text-muted d-block font-weight-bold">VOCÊ</small>
                        <span class="text-truncate d-block"><?= $nome_usuario ?></span>
                    </div>
                    <div class="col-2">
                        <div class="vs-badge">VS</div>
                    </div>
                    <div class="col-5 text-center">
                        <div class="position-relative">
                            <input type="text" id="input-comparativo"
                                class="form-control form-control-sm input-busca-aluno text-center"
                                placeholder="Oponente..." autocomplete="off">
                            <div class="lista-busca d-none text-left"></div>
                        </div>
                    </div>
                </div>

                <div id="resultado-disputa" class="mt-4 d-none">
                    <div class="row text-center bg-white p-2 rounded border shadow-sm">
                        <div class="col-5 border-right">
                            <div class="text-success small">FIN: <b id="meu-fin">0</b></div>
                            <div class="text-primary small">RAS: <b id="meu-ras">0</b></div>
                            <div class="text-info small">PAS: <b id="meu-pas">0</b></div>
                        </div>
                        <div class="col-2 d-flex align-items-center justify-content-center p-0">
                            <i class="fas fa-exchange-alt text-muted"></i>
                        </div>
                        <div class="col-5 border-left">
                            <div class="text-success small">FIN: <b id="dele-fin">0</b></div>
                            <div class="text-primary small">RAS: <b id="dele-ras">0</b></div>
                            <div class="text-info small">PAS: <b id="dele-pas">0</b></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $query_solic = $pdo->prepare("SELECT t.*, u.nome FROM $tabela t INNER JOIN usuarios u ON t.usuario_id = u.id WHERE t.ultimo_parceiro_id = :id AND t.parceiro_confirmado = 'Não'");
        $query_solic->execute([':id' => $id_usuario]);
        while ($s = $query_solic->fetch(PDO::FETCH_ASSOC)) {
            $texto_notificacao = ($s['tipo_registro'] == 'Combate') ?
                "fez uma <b>" . $s['acao_combate'] . "</b> de <b>" . $s['nome_tecnica'] . "</b> em você." :
                "registrou treino de <b>" . $s['nome_tecnica'] . "</b> com você.";
        ?>
            <div class="alert alert-warning shadow-sm border-0 animate__animated animate__headShake">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-bell mr-2"></i> <b><?= $s['nome'] ?></b> <?= $texto_notificacao ?></span>
                    <a href="minhas-tecnicas.php?acao=aprovar&id_log=<?= $s['id'] ?>"
                        class="btn btn-sm btn-dark font-weight-bold">DAR 3 TAPINHAS <i
                            class="fas fa-sign-language ml-1"></i></a>
                </div>
            </div>
        <?php } ?>

        <div class="card card-primary card-outline shadow-sm mb-4">
            <div class="card-body">
                <form method="POST">
                    <label class="small font-weight-bold text-muted text-uppercase">O que vamos treinar hoje?</label>
                    <div class="input-group">
                        <input type="text" name="nome_tecnica" class="form-control"
                            placeholder="Ex: Passagem de guarda emborrachando..." required>
                        <div class="input-group-append"><button class="btn btn-primary" type="submit"><i
                                    class="fas fa-plus"></i></button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <?php
            $query = $pdo->prepare("SELECT * FROM $tabela WHERE usuario_id = :id AND status = 'Em Estudo' ORDER BY id DESC");
            $query->execute([':id' => $id_usuario]);
            while ($t = $query->fetch(PDO::FETCH_ASSOC)) { ?>
                <div class="col-md-6 mb-3">
                    <div class="card card-tecnica shadow-sm h-100">
                        <a href="minhas-tecnicas.php?delete=<?= $t['id'] ?>" class="btn-delete-x"
                            onclick="return confirm('Deseja excluir este golpe do seu diário?')"><i
                                class="fas fa-times-circle fa-lg"></i></a>

                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2 pr-4">
                                <h6 class="font-weight-bold text-uppercase mb-0"><?= htmlspecialchars($t['nome_tecnica']) ?>
                                </h6>
                                <span class="badge-reps"><?= $t['repeticoes'] ?></span>
                            </div>
                            <form method="POST" class="p-3 border rounded bg-white mt-2 shadow-inner">
                                <input type="hidden" name="id_log" value="<?= $t['id'] ?>">
                                <div class="d-flex justify-content-around mb-3">
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input radio-tipo" type="radio" name="tipo_registro"
                                            id="t<?= $t['id'] ?>" value="Treino" checked data-id="<?= $t['id'] ?>">
                                        <label for="t<?= $t['id'] ?>" class="custom-control-label small">Treino</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input radio-tipo" type="radio" name="tipo_registro"
                                            id="c<?= $t['id'] ?>" value="Combate" data-id="<?= $t['id'] ?>">
                                        <label for="c<?= $t['id'] ?>"
                                            class="custom-control-label text-danger font-weight-bold small">Combate</label>
                                    </div>
                                </div>
                                <div id="box-treino-<?= $t['id'] ?>"><input type="number" name="quantidade_reps"
                                        class="form-control form-control-sm mb-2" placeholder="Qtd Repetições"></div>
                                <div id="box-combate-<?= $t['id'] ?>" class="secao-combate mb-2">
                                    <select name="acao_combate" class="form-control form-control-sm">
                                        <option value="Finalização">Finalização ✅</option>
                                        <option value="Raspagem">Raspagem 🔄</option>
                                        <option value="Passagem">Passagem 🤺</option>
                                    </select>
                                </div>
                                <div class="position-relative mb-2">
                                    <input type="text" name="parceiro_nome_texto"
                                        class="form-control form-control-sm input-busca-aluno"
                                        placeholder="Nome do parceiro..." autocomplete="off" required>
                                    <input type="hidden" name="parceiro_id" class="aluno-id-hidden">
                                    <div class="lista-busca d-none"></div>
                                </div>
                                <button type="submit" class="btn btn-dark btn-sm btn-block mt-2 font-weight-bold">SALVAR
                                    ATIVIDADE</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Alternar Treino/Combate
        $(document).on('change', '.radio-tipo', function() {
            let id = $(this).data('id');
            if ($(this).val() == 'Treino') {
                $(`#box-treino-${id}`).show();
                $(`#box-combate-${id}`).hide();
            } else {
                $(`#box-treino-${id}`).hide();
                $(`#box-combate-${id}`).show();
            }
        });

        // Busca Alunos
        $(document).on('keyup', '.input-busca-aluno', function() {
            let input = $(this);
            let lista = input.siblings('.lista-busca');
            if (input.val().length >= 2) {
                $.post('buscar_alunos.php', {
                    query: input.val()
                }, data => lista.removeClass('d-none').html(data));
            } else {
                lista.addClass('d-none');
            }
        });

        // Selecionar Aluno
        $(document).on('click', '.item-aluno', function() {
            let container = $(this).closest('.position-relative');
            container.find('.input-busca-aluno').val($(this).data('nome'));
            container.find('.aluno-id-hidden').val($(this).data('id'));
            $('.lista-busca').addClass('d-none');
            if (container.find('#input-comparativo').length > 0) {
                buscarPlacarDisputa($(this).data('id'));
            }
        });

        function buscarPlacarDisputa(parceiroId) {
            $.post('get_placar_disputa.php', {
                parceiro_id: parceiroId
            }, function(data) {
                let p = JSON.parse(data);
                $('#meu-fin').text(p.meu.fin);
                $('#meu-ras').text(p.meu.ras);
                $('#meu-pas').text(p.meu.pas);
                $('#dele-fin').text(p.dele.fin);
                $('#dele-ras').text(p.dele.ras);
                $('#dele-pas').text(p.dele.pas);
                $('#resultado-disputa').removeClass('d-none').hide().fadeIn();
            });
        }
    </script>
</body>

</html>