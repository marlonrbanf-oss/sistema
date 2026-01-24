<?php
@session_start();
require_once("../conexao.php");

// --- REPARADOR DE EMERGÊNCIA (Executa antes de tudo) ---
$tabela = 'tecnicas_estudo';
$colunas_necessarias = [
    'repeticoes' => "INT DEFAULT 0",
    'ultimo_parceiro' => "VARCHAR(255) NULL",
    'ultimo_parceiro_id' => "INT NULL",
    'parceiro_confirmado' => "ENUM('Sim', 'Não') DEFAULT 'Não'",
    'data_inicio' => "DATE NULL",
    'data_ultima_pratica' => "DATE NULL"
];

foreach ($colunas_necessarias as $coluna => $definicao) {
    try {
        $pdo->query("SELECT $coluna FROM $tabela LIMIT 1");
    } catch (Exception $e) {
        // Se a coluna não existe, nós a criamos agora
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

// --- LÓGICA DE APROVAÇÃO DE PARCEIRO ---
if (isset($_GET['acao']) && $_GET['acao'] == 'aprovar' && isset($_GET['id_log'])) {
    $id_log = $_GET['id_log'];
    $res = $pdo->prepare("UPDATE $tabela SET parceiro_confirmado = 'Sim' WHERE id = :id AND ultimo_parceiro_id = :meu_id");
    $res->bindValue(":id", $id_log);
    $res->bindValue(":meu_id", $id_usuario);
    $res->execute();
    echo "<script>window.location='minhas-tecnicas.php'</script>";
    exit();
}

// --- LÓGICA 1: ADICIONAR TÉCNICA (Onde dava o erro) ---
if (isset($_POST['nome_tecnica']) && !isset($_POST['id_log'])) {
    $nome_tecnica = trim($_POST['nome_tecnica']);
    if (!empty($nome_tecnica)) {
        $query_check = $pdo->prepare("SELECT id FROM $tabela WHERE usuario_id = :id AND nome_tecnica = :nome AND status = 'Em Estudo'");
        $query_check->bindValue(":id", $id_usuario);
        $query_check->bindValue(":nome", $nome_tecnica);
        $query_check->execute();

        if ($query_check->rowCount() == 0) {
            // Agora a coluna data_inicio com certeza existe
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
    $novas_reps = (int)$_POST['quantidade_reps'];
    $parceiro_nome = trim($_POST['parceiro']);
    $parceiro_id = !empty($_POST['parceiro_id']) ? $_POST['parceiro_id'] : null;

    if ($novas_reps > 0) {
        $res = $pdo->prepare("UPDATE $tabela SET 
            repeticoes = repeticoes + :reps, 
            ultimo_parceiro = :parceiro, 
            ultimo_parceiro_id = :p_id, 
            data_ultima_pratica = CURDATE(), 
            parceiro_confirmado = 'Não' 
            WHERE id = :id AND usuario_id = :user_id");
        $res->bindValue(":reps", $novas_reps);
        $res->bindValue(":parceiro", $parceiro_nome);
        $res->bindValue(":p_id", $parceiro_id);
        $res->bindValue(":id", $id_registro);
        $res->bindValue(":user_id", $id_usuario);
        $res->execute();
        echo "<script>window.location='minhas-tecnicas.php'</script>";
        exit();
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
    }

    .item-aluno {
        padding: 8px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
        display: block;
        color: #333;
    }
    </style>
</head>

<body class="hold-transition bg-light">
    <div class="container-fluid py-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="font-weight-bold">Minhas Técnicas</h3>
            <a href="index.php" class="btn btn-dark btn-sm shadow-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>

        <?php
        $query_solic = $pdo->prepare("SELECT t.*, u.nome FROM $tabela t INNER JOIN usuarios u ON t.usuario_id = u.id WHERE t.ultimo_parceiro_id = :id AND t.parceiro_confirmado = 'Não'");
        $query_solic->bindValue(":id", $id_usuario);
        $query_solic->execute();
        while ($s = $query_solic->fetch(PDO::FETCH_ASSOC)) { ?>
        <div class="alert alert-info elevation-1">
            <strong><?php echo htmlspecialchars($s['nome']); ?></strong> registrou um treino com você.
            <a href="minhas-tecnicas.php?acao=aprovar&id_log=<?php echo $s['id']; ?>"
                class="btn btn-sm btn-light ml-2">Confirmar</a>
        </div>
        <?php } ?>

        <div class="card card-primary card-outline shadow-sm mb-4">
            <div class="card-body">
                <form method="POST">
                    <label class="small font-weight-bold text-muted text-uppercase">O que está estudando hoje?</label>
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
            <div class="col-md-4 mb-3">
                <div class="card card-tecnica shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="font-weight-bold"><?php echo htmlspecialchars($t['nome_tecnica']); ?></h6>
                        <p class="small text-muted">Reps: <span
                                class="badge-reps"><?php echo $t['repeticoes']; ?></span></p>

                        <form method="POST" class="p-2 border rounded bg-light">
                            <input type="hidden" name="id_log" value="<?php echo $t['id']; ?>">
                            <div class="row no-gutters">
                                <div class="col-4"><input type="number" name="quantidade_reps"
                                        class="form-control form-control-sm" placeholder="Reps" required></div>
                                <div class="col-8 pl-1 position-relative">
                                    <input type="text" class="form-control form-control-sm input-busca-aluno"
                                        placeholder="Parceiro..." autocomplete="off">
                                    <input type="hidden" name="parceiro_id" class="aluno-id-hidden">
                                    <div class="lista-busca d-none"></div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success btn-xs btn-block mt-2">REGISTRAR</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script>
    $(document).on('keyup', '.input-busca-aluno', function() {
        let input = $(this);
        let query = input.val();
        let lista = input.siblings('.lista-busca');
        if (query.length > 2) {
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
        container.find('.input-busca-aluno').val($(this).data('nome'));
        container.find('.aluno-id-hidden').val($(this).data('id'));
        $('.lista-busca').addClass('d-none');
    });
    </script>
</body>

</html>