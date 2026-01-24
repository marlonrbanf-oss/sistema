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

// --- VERIFICAÇÃO DE ACESSO ---
$nivel_logado = $_SESSION['nivel'] ?? ($_SESSION['nivel_usuario'] ?? '');
if ($nivel_logado != 'Cliente' && $nivel_logado != 'Atleta') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$nome_usuario = $_SESSION['nome_usuario'] ?? 'Eu';

// --- LÓGICA DE APROVAÇÃO ---
if (isset($_GET['acao']) && $_GET['acao'] == 'aprovar' && isset($_GET['id_log'])) {
    $id_log = $_GET['id_log'];
    $res = $pdo->prepare("UPDATE $tabela SET parceiro_confirmado = 'Sim' WHERE id = :id AND ultimo_parceiro_id = :meu_id");
    $res->bindValue(":id", $id_log);
    $res->bindValue(":meu_id", $id_usuario);
    $res->execute();
    echo "<script>window.location='minhas-tecnicas.php'</script>";
    exit();
}

// --- LÓGICA 1: ADICIONAR NOVA TÉCNICA ---
if (isset($_POST['nome_tecnica']) && !isset($_POST['id_log'])) {
    $nome_tecnica = trim($_POST['nome_tecnica']);
    if (!empty($nome_tecnica)) {
        $query_check = $pdo->prepare("SELECT id FROM $tabela WHERE usuario_id = :id AND nome_tecnica = :nome AND status = 'Em Estudo'");
        $query_check->bindValue(":id", $id_usuario);
        $query_check->bindValue(":nome", $nome_tecnica);
        $query_check->execute();

        if ($query_check->rowCount() == 0) {
            $res = $pdo->prepare("INSERT INTO $tabela (usuario_id, nome_tecnica, status, repeticoes, data_inicio) VALUES (:id, :nome, 'Em Estudo', 0, CURDATE())");
            $res->bindValue(":id", $id_usuario);
            $res->bindValue(":nome", $nome_tecnica);
            $res->execute();
            echo "<script>window.location='minhas-tecnicas.php'</script>";
            exit();
        }
    }
}

// --- LÓGICA 2: REGISTRAR PRÁTICA ---
if (isset($_POST['id_log'])) {
    $id_registro = $_POST['id_log'];
    $tipo = $_POST['tipo_registro'];
    $reps = ($tipo == 'Treino') ? (int)$_POST['quantidade_reps'] : 1;
    $acao = ($tipo == 'Combate') ? $_POST['acao_combate'] : null;
    $parceiro_nome = trim($_POST['parceiro_nome_texto'] ?? '');
    $parceiro_id = !empty($_POST['parceiro_id']) ? $_POST['parceiro_id'] : null;

    $res = $pdo->prepare("UPDATE $tabela SET 
        repeticoes = repeticoes + :reps, 
        ultimo_parceiro = :parceiro, 
        ultimo_parceiro_id = :p_id, 
        data_ultima_pratica = CURDATE(), 
        parceiro_confirmado = 'Não',
        tipo_registro = :tipo,
        acao_combate = :acao
        WHERE id = :id AND usuario_id = :user_id");
    $res->bindValue(":reps", $reps);
    $res->bindValue(":parceiro", $parceiro_nome);
    $res->bindValue(":p_id", $parceiro_id);
    $res->bindValue(":tipo", $tipo);
    $res->bindValue(":acao", $acao);
    $res->bindValue(":id", $id_registro);
    $res->bindValue(":user_id", $id_usuario);
    $res->execute();
    echo "<script>window.location='minhas-tecnicas.php'</script>";
    exit();
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
        .card-tecnica {
            border-left: 4px solid #007bff;
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
            z-index: 1000;
            width: 100%;
            background: white;
            border: 1px solid #ddd;
            max-height: 150px;
            overflow-y: auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .item-aluno {
            padding: 8px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            display: block;
            color: #333;
        }

        .item-aluno:hover {
            background-color: #f8f9fa;
        }

        .secao-combate {
            display: none;
        }

        .vs-badge {
            width: 40px;
            height: 40px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 auto;
            border: 2px solid #fff;
            position: relative;
            z-index: 2;
        }
    </style>
</head>

<body class="hold-transition bg-light">
    <div class="container-fluid py-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="font-weight-bold">Minhas Técnicas</h3>
            <a href="index.php" class="btn btn-dark btn-sm shadow-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>

        <div class="card card-widget widget-user-2 shadow-sm mb-4">
            <div class="widget-user-header bg-dark">
                <h5 class="widget-user-username ml-0">Placar de Disputa Direta</h5>
                <h6 class="widget-user-desc ml-0">Compare seu desempenho contra um colega</h6>
            </div>
            <div class="card-footer bg-white">
                <div class="row align-items-center">
                    <div class="col-5 text-center">
                        <small class="text-muted d-block text-uppercase">Você</small>
                        <span class="font-weight-bold"><?php echo $nome_usuario; ?></span>
                    </div>
                    <div class="col-2">
                        <div class="vs-badge shadow-sm">VS</div>
                    </div>
                    <div class="col-5 text-center">
                        <div class="position-relative">
                            <input type="text" id="input-comparativo"
                                class="form-control form-control-sm input-busca-aluno text-center"
                                placeholder="Escolha um aluno..." autocomplete="off">
                            <div class="lista-busca d-none text-left"></div>
                        </div>
                    </div>
                </div>

                <div id="resultado-disputa" class="mt-4 d-none">
                    <div class="row text-center border-top pt-3">
                        <div class="col-4">
                            <h4 class="text-success font-weight-bold mb-0" id="fin-count">0</h4>
                            <small class="text-muted">Finalizações</small>
                        </div>
                        <div class="col-4 border-left border-right">
                            <h4 class="text-primary font-weight-bold mb-0" id="ras-count">0</h4>
                            <small class="text-muted">Raspagens</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-info font-weight-bold mb-0" id="pas-count">0</h4>
                            <small class="text-muted">Passagens</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $query_solic = $pdo->prepare("SELECT t.*, u.nome FROM $tabela t INNER JOIN usuarios u ON t.usuario_id = u.id WHERE t.ultimo_parceiro_id = :id AND t.parceiro_confirmado = 'Não'");
        $query_solic->bindValue(":id", $id_usuario);
        $query_solic->execute();
        while ($s = $query_solic->fetch(PDO::FETCH_ASSOC)) { ?>
            <div class="alert alert-info d-flex justify-content-between align-items-center elevation-1">
                <span><i class="fas fa-handshake mr-2"></i> <strong><?php echo htmlspecialchars($s['nome']); ?></strong>
                    registrou um treino com você.</span>
                <a href="minhas-tecnicas.php?acao=aprovar&id_log=<?php echo $s['id']; ?>"
                    class="btn btn-sm btn-light font-weight-bold text-primary">Confirmar</a>
            </div>
        <?php } ?>

        <div class="card card-primary card-outline shadow-sm mb-4">
            <div class="card-body">
                <form method="POST">
                    <label class="small font-weight-bold text-muted text-uppercase">Nova Técnica para Estudo</label>
                    <div class="input-group">
                        <input type="text" name="nome_tecnica" class="form-control"
                            placeholder="Ex: Raspagem de Meia Guarda..." required>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <?php
            $query = $pdo->prepare("SELECT * FROM $tabela WHERE usuario_id = :id AND status = 'Em Estudo' ORDER BY id DESC");
            $query->bindValue(":id", $id_usuario);
            $query->execute();
            $res = $query->fetchAll(PDO::FETCH_ASSOC);

            foreach ($res as $t) { ?>
                <div class="col-md-6 mb-3">
                    <div class="card card-tecnica shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <h6 class="font-weight-bold text-uppercase mb-0">
                                    <?php echo htmlspecialchars($t['nome_tecnica']); ?></h6>
                                <span class="badge-reps"><?php echo $t['repeticoes']; ?></span>
                            </div>

                            <form method="POST" class="p-3 border rounded bg-white mt-2">
                                <input type="hidden" name="id_log" value="<?php echo $t['id']; ?>">

                                <div class="d-flex justify-content-around mb-3">
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input radio-tipo" type="radio" name="tipo_registro"
                                            id="treino<?php echo $t['id']; ?>" value="Treino" checked
                                            data-id="<?php echo $t['id']; ?>">
                                        <label for="treino<?php echo $t['id']; ?>"
                                            class="custom-control-label">Treino</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input radio-tipo" type="radio" name="tipo_registro"
                                            id="combate<?php echo $t['id']; ?>" value="Combate"
                                            data-id="<?php echo $t['id']; ?>">
                                        <label for="combate<?php echo $t['id']; ?>"
                                            class="custom-control-label text-danger font-weight-bold">Combate</label>
                                    </div>
                                </div>

                                <div id="box-treino-<?php echo $t['id']; ?>" class="row secao-treino mb-2">
                                    <div class="col-12"><input type="number" name="quantidade_reps"
                                            class="form-control form-control-sm" placeholder="Quantidade de Repetições">
                                    </div>
                                </div>

                                <div id="box-combate-<?php echo $t['id']; ?>" class="secao-combate mb-2">
                                    <select name="acao_combate" class="form-control form-control-sm">
                                        <option value="Finalização">Finalização ✅</option>
                                        <option value="Raspagem">Raspagem 🔄</option>
                                        <option value="Passagem">Passagem 🤺</option>
                                    </select>
                                </div>

                                <div class="position-relative mb-2">
                                    <input type="text" name="parceiro_nome_texto"
                                        class="form-control form-control-sm input-busca-aluno"
                                        placeholder="Com qual colega?" autocomplete="off" required>
                                    <input type="hidden" name="parceiro_id" class="aluno-id-hidden">
                                    <div class="lista-busca d-none"></div>
                                </div>

                                <button type="submit" class="btn btn-dark btn-sm btn-block mt-2 font-weight-bold">REGISTRAR
                                    ATIVIDADE</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="font-weight-bold mb-0 text-muted"><i class="fas fa-history mr-2"></i> Histórico
                            Recente</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="bg-light small text-muted">
                                <tr>
                                    <th>Parceiro</th>
                                    <th>Última Técnica</th>
                                    <th>Tipo</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query_p = $pdo->prepare("SELECT * FROM $tabela WHERE usuario_id = :id AND ultimo_parceiro IS NOT NULL 
                                                          ORDER BY data_ultima_pratica DESC LIMIT 5");
                                $query_p->bindValue(":id", $id_usuario);
                                $query_p->execute();
                                $parceiros = $query_p->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($parceiros as $p) { ?>
                                    <tr class="small">
                                        <td>
                                            <?php echo htmlspecialchars($p['ultimo_parceiro']); ?>
                                            <?php echo ($p['parceiro_confirmado'] == 'Sim') ? '<i class="fas fa-check-circle text-success ml-1"></i>' : '<i class="fas fa-clock text-warning ml-1"></i>'; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($p['nome_tecnica']); ?></td>
                                        <td><span
                                                class="badge <?php echo $p['tipo_registro'] == 'Combate' ? 'badge-danger' : 'badge-light'; ?>"><?php echo $p['tipo_registro']; ?></span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($p['data_ultima_pratica'])); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Alternar entre campos de Treino e Combate
        $(document).on('change', '.radio-tipo', function() {
            let id = $(this).data('id');
            if ($(this).val() == 'Treino') {
                $(`#box-treino-${id}`).fadeIn();
                $(`#box-combate-${id}`).hide();
            } else {
                $(`#box-treino-${id}`).hide();
                $(`#box-combate-${id}`).fadeIn();
            }
        });

        // Busca de alunos geral
        $(document).on('keyup', '.input-busca-aluno', function() {
            let input = $(this);
            let query = input.val();
            let lista = input.siblings('.lista-busca');
            if (query.length >= 2) {
                $.post('buscar_alunos.php', {
                    query: query
                }, function(data) {
                    lista.removeClass('d-none').html(data);
                });
            } else {
                lista.addClass('d-none');
            }
        });

        $(document).on('click', '.item-aluno', function() {
            let container = $(this).closest('.position-relative');
            let nome = $(this).data('nome');
            let id = $(this).data('id');

            container.find('.input-busca-aluno').val(nome);
            container.find('.aluno-id-hidden').val(id);
            $('.lista-busca').addClass('d-none');

            // Caso seja o input de comparativo, dispara o placar
            if (container.find('#input-comparativo').length > 0) {
                buscarPlacarDisputa(id);
            }
        });

        function buscarPlacarDisputa(parceiroId) {
            $.post('get_placar_disputa.php', {
                parceiro_id: parceiroId
            }, function(data) {
                let placar = JSON.parse(data);
                $('#fin-count').text(placar.fin);
                $('#ras-count').text(placar.ras);
                $('#pas-count').text(placar.pas);
                $('#resultado-disputa').removeClass('d-none').hide().fadeIn();
            });
        }

        $(document).mouseup(function(e) {
            let container = $(".lista-busca");
            if (!container.is(e.target) && container.has(e.target).length === 0) {
                container.addClass('d-none');
            }
        });
    </script>
</body>

</html>