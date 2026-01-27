<?php
@session_start();
require_once("../conexao.php");

if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

try {
    // 1. Lições (Técnicas principais) - Buscando categorias para separar no rádio
    $licoes = $pdo->query("SELECT * FROM licoes ORDER BY numero ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 2. Aquecimentos e Educativos da tabela 'tecnicas'
    $res_col = $pdo->query("SHOW COLUMNS FROM tecnicas LIKE 'posicao'");
    $tem_coluna_posicao = $res_col->rowCount() > 0;

    if ($tem_coluna_posicao) {
        $aquecimentos = $pdo->query("SELECT * FROM tecnicas WHERE posicao = 'Aquecimento' OR posicao = 'Fundamento' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
        $educativos = $pdo->query("SELECT * FROM tecnicas WHERE posicao = 'Educativo' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $aquecimentos = $pdo->query("SELECT * FROM tecnicas ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
        $educativos = $aquecimentos;
    }
} catch (Exception $e) {
    echo "Erro ao carregar dados: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <title>Plano de Aula - Nova Arte BJJ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        .card-aula {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .select-step {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            height: 100%;
        }

        .list-selectable {
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 5px;
            background: #f8f9fa;
        }

        /* Estilo Radio Card */
        .item-check {
            display: none;
        }

        .item-label {
            display: block;
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 6px;
            cursor: pointer;
            border: 1px solid #eee;
            transition: 0.2s;
            background: #fff;
            font-size: 0.9rem;
        }

        .item-check:checked+.item-label {
            background: #28a745;
            color: #fff;
            border-color: #28a745;
        }

        .item-check:checked+.label-primary {
            background: #007bff;
            border-color: #007bff;
        }

        /* Status Radio Quadrado */
        .status-group {
            display: flex;
            gap: 10px;
        }

        .status-card {
            flex: 1;
            text-align: center;
        }

        .status-check {
            display: none;
        }

        .status-label {
            display: block;
            padding: 15px 5px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: bold;
            font-size: 0.8rem;
        }

        .status-check:checked+.label-estudo {
            border-color: #ffc107;
            background: #fff9e6;
            color: #856404;
        }

        .status-check:checked+.label-revisao {
            border-color: #17a2b8;
            background: #e7f3f5;
            color: #0c5460;
        }

        .status-check:checked+.label-concluido {
            border-color: #28a745;
            background: #eaf5ea;
            color: #155724;
        }

        .preview-plano {
            background: #f1f3f5;
            border-radius: 10px;
            padding: 20px;
            border: 1px dashed #ced4da;
        }

        .badge-step {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid mt-3 mb-5">
        <form action="lancar-plano-aula.php" method="POST" id="formPlano">
            <div class="card card-aula">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🥋 MONTAR PLANO DE AULA COLETIVO</h5>
                    <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill">Sair</a>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="select-step shadow-sm">
                                <h6 class="font-weight-bold text-warning"><span
                                        class="badge badge-warning badge-step mr-2 text-white">1</span>
                                    Aquecimento/Fundamento</h6>
                                <div class="list-selectable">
                                    <?php foreach ($aquecimentos as $aq): ?>
                                        <input type="radio" name="aquecimento_id" id="aq_<?php echo $aq['id']; ?>"
                                            value="<?php echo $aq['id']; ?>" class="item-check radio-dinamico">
                                        <label for="aq_<?php echo $aq['id']; ?>"
                                            class="item-label"><?php echo $aq['nome']; ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="select-step shadow-sm">
                                <h6 class="font-weight-bold text-info"><span
                                        class="badge badge-info badge-step mr-2 text-white">2</span> Educativos</h6>
                                <div class="list-selectable">
                                    <?php foreach ($educativos as $ed): ?>
                                        <input type="radio" name="educativo_id" id="ed_<?php echo $ed['id']; ?>"
                                            value="<?php echo $ed['id']; ?>" class="item-check radio-dinamico">
                                        <label for="ed_<?php echo $ed['id']; ?>"
                                            class="item-label"><?php echo $ed['nome']; ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="select-step shadow-sm">
                                <h6 class="font-weight-bold text-primary"><span
                                        class="badge badge-primary badge-step mr-2 text-white">3</span> Lição Técnica
                                </h6>
                                <div class="list-selectable">
                                    <?php foreach ($licoes as $li): ?>
                                        <input type="radio" name="licao_id" id="li_<?php echo $li['id']; ?>"
                                            value="<?php echo $li['numero']; ?>" class="item-check radio-dinamico" required>
                                        <label for="li_<?php echo $li['id']; ?>" class="item-label label-primary">
                                            <b>L<?php echo $li['numero']; ?></b> - <?php echo $li['nome']; ?>
                                            <small
                                                class="d-block text-muted"><?php echo @$li['categoria'] ?: @$li['posicao']; ?></small>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6 mb-4">
                            <div class="border rounded p-3 bg-white shadow-sm">
                                <h6 class="font-weight-bold mb-3"><i class="fas fa-users mr-2 text-primary"></i> Alunos
                                    Presentes</h6>
                                <div class="input-group mb-3">
                                    <input type="text" id="buscaAluno" class="form-control"
                                        placeholder="Filtrar por nome...">
                                </div>
                                <div class="list-selectable p-2 bg-light" style="max-height: 300px;">
                                    <?php
                                    $alunos = $pdo->query("SELECT id, nome FROM usuarios WHERE nivel = 'Cliente' AND ativo = 'Sim' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($alunos as $aluno): ?>
                                        <div class="aluno-row">
                                            <input type="checkbox" name="alunos[]" id="aluno_<?php echo $aluno['id']; ?>"
                                                value="<?php echo $aluno['id']; ?>" class="item-check">
                                            <label for="aluno_<?php echo $aluno['id']; ?>" class="item-label mb-1">
                                                <i class="far fa-square mr-2 unchecked"></i>
                                                <i class="fas fa-check-circle mr-2 checked d-none"></i>
                                                <?php echo mb_strtoupper($aluno['nome']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="preview-plano h-100">
                                <h6 class="font-weight-bold text-uppercase border-bottom border-secondary pb-2 mb-3">
                                    Resumo e Status</h6>

                                <div class="status-group mb-4">
                                    <div class="status-card">
                                        <input type="radio" name="status" value="Em Estudo" id="st_estudo"
                                            class="status-check" checked>
                                        <label for="st_estudo" class="status-label label-estudo">🟡<br>EM ESTUDO</label>
                                    </div>
                                    <div class="status-card">
                                        <input type="radio" name="status" value="Revisão" id="st_revisao"
                                            class="status-check">
                                        <label for="st_revisao" class="status-label label-revisao">🔵<br>REVISÃO</label>
                                    </div>
                                    <div class="status-card">
                                        <input type="radio" name="status" value="Concluído" id="st_concluido"
                                            class="status-check">
                                        <label for="st_concluido"
                                            class="status-label label-concluido">🟢<br>CONCLUÍDO</label>
                                    </div>
                                </div>

                                <div id="resumo-conteudo">
                                    <p class="text-muted font-italic">Selecione as técnicas acima.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white p-3 text-center">
                    <button type="submit" class="btn btn-success btn-lg px-5 font-weight-bold shadow" id="btnSalvar">
                        <i class="fas fa-save mr-2"></i> FINALIZAR AULA
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Atualiza Resumo em tempo real para Rádios
        $('.radio-dinamico, .status-check').change(function() {
            const aq = $('input[name="aquecimento_id"]:checked').next('label').text() || '---';
            const ed = $('input[name="educativo_id"]:checked').next('label').text() || '---';
            const li = $('input[name="licao_id"]:checked').next('label').text() || '---';
            const st = $('input[name="status"]:checked').val();

            let html = `
                <div class="small">
                    <p class="mb-1 text-warning"><b>🔥 Aquecimento:</b> ${aq}</p>
                    <p class="mb-1 text-info"><b>⚙️ Educativo:</b> ${ed}</p>
                    <p class="mb-1 text-primary"><b>📖 Lição:</b> ${li}</p>
                    <p class="mb-0 text-dark"><b>📌 Status:</b> ${st}</p>
                </div>`;
            $('#resumo-conteudo').html(html);
        });

        // Filtro de Alunos
        $("#buscaAluno").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $(".aluno-row").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // Checkbox Alunos
        $('input[name="alunos[]"]').change(function() {
            const label = $(this).next('label');
            if ($(this).is(':checked')) {
                label.find('.unchecked').addClass('d-none');
                label.find('.checked').removeClass('d-none');
            } else {
                label.find('.unchecked').removeClass('d-none');
                label.find('.checked').addClass('d-none');
            }
        });

        $('#formPlano').submit(function() {
            if ($('input[name="alunos[]"]:checked').length === 0) {
                alert("Selecione os alunos presentes.");
                return false;
            }
            $('#btnSalvar').html('<i class="fas fa-spinner fa-spin mr-2"></i> SALVANDO...').attr('disabled', true);
            return true;
        });
    </script>
</body>

</html>