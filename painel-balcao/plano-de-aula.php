<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE ACESSO
if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

try {
    // 1. AQUECIMENTOS (Buscando da tabela licoes ou tecnicas conforme seu padrão)
    // Se a sua tabela for 'licoes', ajuste o nome da tabela abaixo. Mantive 'tecnicas' conforme seu original.
    $aquecimentos = $pdo->query("SELECT * FROM tecnicas WHERE categoria = 'Aquecimento' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 2. EDUCATIVOS
    $educativos = $pdo->query("SELECT * FROM tecnicas WHERE categoria = 'Educativo' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 3. LIÇÕES (Matriz Técnica)
    $licoes = $pdo->query("SELECT * FROM licoes ORDER BY nivel ASC, numero ASC")->fetchAll(PDO::FETCH_ASSOC);
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
            color: #333;
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
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #dee2e6;
            position: sticky;
            top: 20px;
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

        .nav-pills .nav-link {
            font-size: 0.8rem;
            padding: 5px 10px;
            cursor: pointer;
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
                                <h6 class="font-weight-bold text-warning">
                                    <span class="badge badge-warning badge-step mr-2 text-white">1</span> Aquecimento
                                </h6>
                                <div class="list-selectable mt-2">
                                    <?php foreach ($aquecimentos as $aq): ?>
                                        <input type="radio" name="aquecimento_id" id="aq_<?php echo $aq['id']; ?>"
                                            value="<?php echo $aq['id']; ?>"
                                            data-nome="<?php echo htmlspecialchars($aq['nome']); ?>"
                                            class="item-check radio-dinamico">
                                        <label for="aq_<?php echo $aq['id']; ?>"
                                            class="item-label"><?php echo $aq['nome']; ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="select-step shadow-sm">
                                <h6 class="font-weight-bold text-info">
                                    <span class="badge badge-info badge-step mr-2 text-white">2</span> Educativos
                                </h6>
                                <div class="list-selectable mt-2">
                                    <?php foreach ($educativos as $ed): ?>
                                        <input type="radio" name="educativo_id" id="ed_<?php echo $ed['id']; ?>"
                                            value="<?php echo $ed['id']; ?>"
                                            data-nome="<?php echo htmlspecialchars($ed['nome']); ?>"
                                            class="item-check radio-dinamico">
                                        <label for="ed_<?php echo $ed['id']; ?>"
                                            class="item-label"><?php echo $ed['nome']; ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="select-step shadow-sm">
                                <h6 class="font-weight-bold text-primary">
                                    <span class="badge badge-primary badge-step mr-2 text-white">3</span> Lição Técnica
                                </h6>
                                <ul class="nav nav-pills nav-fill mb-2 bg-light p-1 rounded border">
                                    <li class="nav-item"><a class="nav-link active btn-filtro-nivel"
                                            data-nivel="Iniciante">Iniciante</a></li>
                                    <li class="nav-item"><a class="nav-link btn-filtro-nivel"
                                            data-nivel="Intermediário">Interm.</a></li>
                                    <li class="nav-item"><a class="nav-link btn-filtro-nivel"
                                            data-nivel="Avançado">Avançado</a></li>
                                </ul>

                                <div class="list-selectable">
                                    <?php foreach ($licoes as $li):
                                        $nivel_li = $li['nivel'] ?? 'Iniciante';
                                        // Ajuste aqui: usando 'titulo' caso sua tabela use esse nome, ou mantendo 'nome'
                                        $nome_exibicao = $li['titulo'] ?? $li['nome'];
                                    ?>
                                        <div class="licao-item" data-nivel="<?php echo $nivel_li; ?>"
                                            style="<?php echo ($nivel_li != 'Iniciante') ? 'display:none' : ''; ?>">
                                            <input type="radio" name="licao_id" id="li_<?php echo $li['id']; ?>"
                                                value="<?php echo $li['id']; ?>"
                                                data-nome="<?php echo htmlspecialchars($nome_exibicao); ?>"
                                                data-numero="<?php echo $li['numero']; ?>" class="item-check radio-dinamico"
                                                required>
                                            <label for="li_<?php echo $li['id']; ?>" class="item-label label-primary">
                                                <b>L<?php echo $li['numero']; ?></b> - <?php echo $nome_exibicao; ?>
                                                <small class="d-block text-muted"><?php echo $nivel_li; ?></small>
                                            </label>
                                        </div>
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
                                <div class="list-selectable p-2 bg-light" style="max-height: 400px;">
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
                            <div class="preview-plano shadow-sm h-100">
                                <h6 class="font-weight-bold text-uppercase border-bottom pb-2 mb-3 text-dark">
                                    <i class="fas fa-clipboard-list mr-2"></i>Visão Geral do Plano
                                </h6>
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
                                    <p class="text-muted font-italic">Aguardando seleção das técnicas...</p>
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
        // Filtro de Nível
        $('.btn-filtro-nivel').click(function() {
            const nivelSelecionado = $(this).data('nivel');
            $('.btn-filtro-nivel').removeClass('active');
            $(this).addClass('active');
            $('.licao-item').hide();
            $(`.licao-item[data-nivel="${nivelSelecionado}"]`).show();
            atualizarResumo();
        });

        // Atualizar Resumo
        function atualizarResumo() {
            const aq_nome = $('input[name="aquecimento_id"]:checked').data('nome') ||
                '<span class="text-danger">Não selecionado</span>';
            const ed_nome = $('input[name="educativo_id"]:checked').data('nome') ||
                '<span class="text-danger">Não selecionado</span>';

            const licaoInput = $('input[name="licao_id"]:checked');
            const li_nome = licaoInput.data('nome') || '<span class="text-danger">Não selecionada</span>';
            const li_num = licaoInput.data('numero') || '';
            const status = $('input[name="status"]:checked').val();

            let html = `
                <div class="mb-3">
                    <h6 class="font-weight-bold text-warning mb-1">🔥 Aquecimento:</h6>
                    <p class="border-left border-warning pl-2 ml-1">${aq_nome}</p>
                </div>
                <div class="mb-3">
                    <h6 class="font-weight-bold text-info mb-1">⚙️ Educativo:</h6>
                    <p class="border-left border-info pl-2 ml-1">${ed_nome}</p>
                </div>
                <div class="mb-3">
                    <h6 class="font-weight-bold text-primary mb-1">📖 Lição Técnica: ${li_num ? 'L'+li_num : ''}</h6>
                    <div class="p-2 bg-light border rounded">
                        <p class="font-weight-bold mb-0">${li_nome}</p>
                    </div>
                </div>
                <div class="mt-4 pt-2 border-top">
                    <p class="mb-0"><strong>Status Final:</strong> <span class="badge badge-dark">${status}</span></p>
                </div>`;
            $('#resumo-conteudo').html(html);
        }

        $(document).on('change', '.radio-dinamico, .status-check', atualizarResumo);

        // Busca de Alunos
        $("#buscaAluno").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $(".aluno-row").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // Checkbox Visual
        $('input[name="alunos[]"]').change(function() {
            const label = $(this).next('label');
            if ($(this).is(':checked')) {
                label.find('.unchecked').addClass('d-none');
                label.find('.checked').removeClass('d-none');
                label.css('background-color', '#eaf5ea');
            } else {
                label.find('.unchecked').removeClass('d-none');
                label.find('.checked').addClass('d-none');
                label.css('background-color', '#fff');
            }
        });

        // Validação
        $('#formPlano').submit(function() {
            if ($('input[name="licao_id"]:checked').length === 0) {
                alert("Selecione uma Lição Técnica antes de finalizar.");
                return false;
            }
            if ($('input[name="alunos[]"]:checked').length === 0) {
                alert("Selecione ao menos um aluno presente.");
                return false;
            }
            $('#btnSalvar').html('<i class="fas fa-spinner fa-spin mr-2"></i> PROCESSANDO...').attr('disabled',
                true);
            return true;
        });
    </script>
</body>

</html>