<?php
@session_start();
require_once("../conexao.php");

if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// --- BUSCA DINÂMICA DE LIÇÕES E TÉCNICAS NO BANCO ---
$licoes_array = [];
$query_l = $pdo->query("SELECT * FROM licoes ORDER BY numero ASC");
$res_l = $query_l->fetchAll(PDO::FETCH_ASSOC);

foreach ($res_l as $licao) {
    $id_licao = $licao['id'];
    $num_licao = $licao['numero'];
    $cat_licao = $licao['categoria'];

    // Busca as técnicas atreladas a esta lição
    $query_t = $pdo->prepare("SELECT nome FROM tecnicas WHERE licao_id = :id ORDER BY id ASC");
    $query_t->bindValue(":id", $id_licao);
    $query_t->execute();
    $res_t = $query_t->fetchAll(PDO::FETCH_ASSOC);

    $tecnicas_formatadas = [];
    foreach ($res_t as $tec) {
        $tecnicas_formatadas[] = [
            "t" => $tec['nome'],
            "p" => $cat_licao // Usa a categoria da lição como 'posição'
        ];
    }

    // Organiza no array usando o número da lição como chave
    $licoes_array[$num_licao] = $tecnicas_formatadas;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lançar Lição - Nova Arte BJJ</title>
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        :root {
            --primary-color: #007bff;
            --dark-color: #343a40;
            --success-color: #28a745;
        }

        body {
            background-color: #f4f7f6;
        }

        .radio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(65px, 1fr));
            gap: 10px;
        }

        .btn-radio {
            cursor: pointer;
            text-align: center;
            border: 1px solid #dee2e6;
            padding: 12px 5px;
            border-radius: 8px;
            background: #fff;
            transition: all 0.2s;
            font-weight: bold;
            font-size: 0.85rem;
            color: #495057;
        }

        .btn-radio:hover {
            background: #e9ecef;
            border-color: var(--primary-color);
        }

        input[type="radio"]:checked+.btn-radio {
            background-color: var(--dark-color);
            color: #fff;
            border-color: var(--dark-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .preview-box {
            min-height: 180px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
        }

        .status-container {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-status {
            cursor: pointer;
            padding: 12px 25px;
            border: 2px solid #dee2e6;
            border-radius: 50px;
            font-weight: 700;
            transition: 0.3s;
            background: #fff;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        input[name="status"]:checked+.btn-estudo {
            background: #ffc107;
            border-color: #e0a800;
            color: #212529;
        }

        input[name="status"]:checked+.btn-revisao {
            background: #17a2b8;
            border-color: #117a8b;
            color: #fff;
        }

        input[name="status"]:checked+.btn-concluido {
            background: #28a745;
            border-color: #1e7e34;
            color: #fff;
        }

        .aluno-row {
            transition: 0.2s;
            cursor: pointer;
        }

        .aluno-row:hover {
            background-color: #f1f3f5 !important;
        }

        .custom-checkbox {
            width: 22px;
            height: 22px;
            cursor: pointer;
            accent-color: var(--success-color);
        }

        .sticky-footer {
            position: sticky;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            border-top: 1px solid #dee2e6;
            z-index: 1000;
        }

        .border-right-custom {
            border-right: 1px solid #dee2e6;
        }

        @media (max-width: 768px) {
            .border-right-custom {
                border-right: none;
                border-bottom: 1px solid #dee2e6;
                margin-bottom: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-3 mb-5">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 font-weight-bold"><i class="fas fa-edit mr-2 text-warning"></i> LANÇAMENTO COLETIVO</h5>
                <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Voltar</a>
            </div>

            <form action="lancar-selos.php" method="POST" id="formLancar">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 border-right-custom">
                            <h6 class="font-weight-bold mb-3 text-primary"><span class="badge badge-primary mr-2">1</span> SELECIONE A LIÇÃO</h6>
                            <div class="radio-grid mb-4">
                                <?php foreach ($licoes_array as $n => $tecnicas) : ?>
                                    <label class="m-0 p-0">
                                        <input type="radio" name="licao_id" value="<?php echo $n; ?>" class="d-none" required>
                                        <div class="btn-radio">L-<?php echo str_pad($n, 2, "0", STR_PAD_LEFT); ?></div>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <h6 class="font-weight-bold text-secondary small text-uppercase">Técnicas desta lição:</h6>
                            <div id="preview-tecnicas" class="preview-box shadow-sm overflow-auto">
                                <div class="text-center mt-4 text-muted">
                                    <i class="fas fa-book-open fa-2x mb-2 opacity-5"></i><br>
                                    Clique em uma lição acima.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mt-4 mt-md-0">
                            <h6 class="font-weight-bold mb-3 text-primary"><span class="badge badge-primary mr-2">2</span> SELECIONE OS ALUNOS</h6>
                            <div class="input-group mb-3 shadow-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
                                </div>
                                <input type="text" id="busca" class="form-control border-left-0" placeholder="Filtrar aluno por nome...">
                            </div>

                            <div style="max-height: 400px; overflow-y: auto;" class="border rounded bg-white">
                                <table class="table table-hover mb-0">
                                    <tbody id="tabela_alunos">
                                        <?php
                                        $query = $pdo->query("SELECT id, nome FROM usuarios WHERE nivel = 'Cliente' AND ativo = 'Sim' ORDER BY nome ASC");
                                        $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($res as $aluno) {
                                        ?>
                                            <tr class="aluno-row">
                                                <td width="50" class="text-center align-middle">
                                                    <input type="checkbox" name="alunos[]" value="<?php echo $aluno['id']; ?>" class="custom-checkbox">
                                                </td>
                                                <td class="align-middle py-3">
                                                    <span class="text-dark font-weight-bold"><?php echo mb_strtoupper($aluno['nome']); ?></span>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-right mt-2">
                                <small class="text-muted" id="contador-alunos">0 alunos selecionados</small>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row">
                        <div class="col-12 text-center">
                            <h6 class="font-weight-bold mb-3 text-primary"><span class="badge badge-primary mr-2">3</span> NÍVEL DE APRENDIZADO</h6>
                            <div class="status-container">
                                <label class="m-0">
                                    <input type="radio" name="status" value="Em Estudo" class="d-none" checked>
                                    <div class="btn-status btn-estudo">🟡 Em Estudo</div>
                                </label>
                                <label class="m-0">
                                    <input type="radio" name="status" value="Revisão" class="d-none">
                                    <div class="btn-status btn-revisao">🔵 Revisão</div>
                                </label>
                                <label class="m-0">
                                    <input type="radio" name="status" value="Concluído" class="d-none">
                                    <div class="btn-status btn-concluido">🟢 Concluído</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white p-3 sticky-footer">
                    <button type="submit" class="btn btn-success btn-lg btn-block shadow font-weight-bold py-3" id="btnFinalizar">
                        <i class="fas fa-save mr-2"></i> SALVAR PARA TODOS SELECIONADOS
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const licoes = <?php echo json_encode($licoes_array); ?>;

        $('input[name="licao_id"]').change(function() {
            const id = $(this).val();
            const box = $('#preview-tecnicas');
            if (id && licoes[id]) {
                let html = '<div class="list-group list-group-flush">';
                licoes[id].forEach(item => {
                    html += `
                        <div class="list-group-item px-1 py-2 bg-transparent border-bottom">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-caret-right text-primary mt-1 mr-2"></i>
                                <div>
                                    <div class="font-weight-bold text-dark">${item.t}</div>
                                    <span class="badge badge-light border text-primary small">${item.p}</span>
                                </div>
                            </div>
                        </div>`;
                });
                box.html(html + '</div>').hide().fadeIn(200);
            }
        });

        $('.aluno-row').click(function(e) {
            if (e.target.type !== 'checkbox') {
                const cb = $(this).find('input[type="checkbox"]');
                cb.prop('checked', !cb.prop('checked'));
            }
            atualizarContador();
        });

        function atualizarContador() {
            const total = $('input[name="alunos[]"]:checked').length;
            $('#contador-alunos').text(total + (total === 1 ? ' aluno selecionado' : ' alunos selecionados'));
        }

        $("#busca").on("keyup", function() {
            const value = $(this).val().toLowerCase();
            $("#tabela_alunos tr").each(function() {
                $(this).toggle($(this).find('span').text().toLowerCase().indexOf(value) > -1);
            });
        });

        $('#formLancar').submit(function() {
            if ($('input[name="alunos[]"]:checked').length === 0) {
                alert("Selecione pelo menos um aluno!");
                return false;
            }
            $('#btnFinalizar').html('<i class="fas fa-spinner fa-spin mr-2"></i> GRAVANDO...').addClass('disabled');
            return true;
        });
    </script>
</body>

</html>