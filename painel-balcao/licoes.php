<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE SEGURANÇA
if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

try {
    // 1. LIÇÕES TÉCNICAS (Categorias: Licao)
    $licoes = $pdo->query("SELECT * FROM licoes WHERE categoria = 'Licao' ORDER BY numero ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 2. AQUECIMENTOS E FUNDAMENTOS
    $aquecimentos = $pdo->query("SELECT * FROM licoes WHERE categoria = 'Aquecimento' OR categoria = 'Fundamento' ORDER BY titulo ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 3. EDUCATIVOS E ALONGAMENTOS
    $educativos = $pdo->query("SELECT * FROM licoes WHERE categoria = 'Educativo' OR categoria = 'Alongamento' ORDER BY titulo ASC")->fetchAll(PDO::FETCH_ASSOC);
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
                                <h6 class="font-weight-bold text-warning">
                                    <span class="badge badge-warning badge-step mr-2 text-white">1</span>
                                    Aquecimento / Fundamento
                                </h6>
                                <div class="list-selectable mt-2">
                                    <?php foreach ($aquecimentos as $aq): ?>
                                        <input type="radio" name="aquecimento_id" id="aq_<?php echo $aq['id']; ?>"
                                            value="<?php echo $aq['id']; ?>"
                                            data-titulo="<?php echo htmlspecialchars($aq['titulo']); ?>"
                                            class="item-check radio-dinamico">
                                        <label for="aq_<?php echo $aq['id']; ?>" class="item-label">
                                            <small
                                                class="badge badge-light border text-muted"><?php echo $aq['categoria']; ?></small><br>
                                            <?php echo $aq['titulo']; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="select-step shadow-sm">
                                <h6 class="font-weight-bold text-info">
                                    <span class="badge badge-info badge-step mr-2 text-white">2</span>
                                    Educativo / Alongamento
                                </h6>
                                <div class="list-selectable mt-2">
                                    <?php foreach ($educativos as $ed): ?>
                                        <input type="radio" name="educativo_id" id="ed_<?php echo $ed['id']; ?>"
                                            value="<?php echo $ed['id']; ?>"
                                            data-titulo="<?php echo htmlspecialchars($ed['titulo']); ?>"
                                            class="item-check radio-dinamico">
                                        <label for="ed_<?php echo $ed['id']; ?>" class="item-label">
                                            <small
                                                class="badge badge-light border text-muted"><?php echo $ed['categoria']; ?></small><br>
                                            <?php echo $ed['titulo']; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="select-step shadow-sm">
                                <h6 class="font-weight-bold text-primary">
                                    <span class="badge badge-primary badge-step mr-2 text-white">3</span>
                                    Lição Técnica (Matriz)
                                </h6>
                                <div class="list-selectable mt-2">
                                    <?php foreach ($licoes as $li):
                                        $numero = (int)$li['numero'];
                                        if ($numero <= 20) {
                                            $cor_nivel = "text-success";
                                            $nivel_txt = "Iniciante";
                                        } elseif ($numero <= 50) {
                                            $cor_nivel = "text-primary";
                                            $nivel_txt = "Intermediário";
                                        } else {
                                            $cor_nivel = "text-danger";
                                            $nivel_txt = "Avançado";
                                        }

                                        $desc_preview = htmlspecialchars(strip_tags($li['descricao'] ?? 'Sem descrição técnica.'));
                                    ?>
                                        <input type="radio" name="licao_id" id="li_<?php echo $li['id']; ?>"
                                            value="<?php echo $li['id']; ?>"
                                            data-titulo="<?php echo htmlspecialchars($li['titulo']); ?>"
                                            data-numero="<?php echo $li['numero']; ?>"
                                            data-descricao="<?php echo $desc_preview; ?>" class="item-check radio-dinamico"
                                            required>
                                        <label for="li_<?php echo $li['id']; ?>" class="item-label label-primary">
                                            <span class="font-weight-bold">L<?php echo $li['numero']; ?></span> -
                                            <?php echo $li['titulo']; ?>
                                            <small class="d-block <?php echo $cor_nivel; ?> font-weight-bold"
                                                style="font-size: 0.7rem;">NÍVEL: <?php echo $nivel_txt; ?></small>
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
                                <div class="list-selectable p-2 bg-light" style="max-height: 350px;">
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
                                <h6 class="font-weight-bold text-uppercase border-bottom border-secondary pb-2 mb-3">
                                    Resumo da Aula</h6>
                                <div class="status-group mb-4">
                                    <div class="status-card">
                                        <input type="radio" name="status" value="Em Estudo" id="st_estudo"
                                            class="status-check" checked>
                                        <label for="st_estudo" class="status-label label-estudo">🟡<br>ESTUDO</label>
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
                                    <p class="text-muted font-italic">Selecione as técnicas para visualizar os detalhes
                                        técnicos aqui.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white p-3 text-center">
                    <button type="submit" class="btn btn-success btn-lg px-5 font-weight-bold shadow" id="btnSalvar">
                        <i class="fas fa-save mr-2"></i> FINALIZAR E LANÇAR PLANO
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Função para atualizar o Resumo
        $(document).on('change', '.radio-dinamico, .status-check', function() {
            const aq = $('input[name="aquecimento_id"]:checked').data('titulo') || '---';
            const ed = $('input[name="educativo_id"]:checked').data('titulo') || '---';

            const licaoSel = $('input[name="licao_id"]:checked');
            const li_titulo = licaoSel.data('titulo') || '---';
            const li_num = licaoSel.data('numero') || '';
            const li_desc = licaoSel.data('descricao') || 'Selecione uma lição para ver os detalhes.';

            const st = $('input[name="status"]:checked').val();

            let html = `
                <div class="resumo-final">
                    <div class="mb-3 p-2 bg-light border-left border-warning" style="border-left-width: 4px !important;">
                        <small class="text-muted d-block font-weight-bold">AQUECIMENTO:</small>
                        <span class="text-dark">🔥 ${aq}</span>
                    </div>
                    <div class="mb-3 p-2 bg-light border-left border-info" style="border-left-width: 4px !important;">
                        <small class="text-muted d-block font-weight-bold">EDUCATIVO:</small>
                        <span class="text-dark">⚙️ ${ed}</span>
                    </div>
                    <div class="mb-3 p-3 border rounded shadow-sm bg-white">
                        <small class="text-primary d-block font-weight-bold mb-1 text-uppercase">Técnica Principal (L${li_num}):</small>
                        <h6 class="font-weight-bold mb-2">${li_titulo}</h6>
                        <div class="small text-muted" style="line-height: 1.4; white-space: pre-wrap;">${li_desc}</div>
                    </div>
                    <div class="mt-3">
                        <span class="badge badge-dark p-2 w-100">FOCO: ${st.toUpperCase()}</span>
                    </div>
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

        // Estilo Checkbox Alunos
        $('input[name="alunos[]"]').change(function() {
            const label = $(this).next('label');
            if ($(this).is(':checked')) {
                label.find('.unchecked').addClass('d-none');
                label.find('.checked').removeClass('d-none');
                label.css('background-color', '#e8f4fd');
            } else {
                label.find('.unchecked').removeClass('d-none');
                label.find('.checked').addClass('d-none');
                label.css('background-color', '#fff');
            }
        });

        // Validação de Envio
        $('#formPlano').submit(function() {
            if ($('input[name="licao_id"]:checked').length === 0) {
                alert("Por favor, selecione a Lição Técnica.");
                return false;
            }
            if ($('input[name="alunos[]"]:checked').length === 0) {
                alert("Por favor, selecione ao menos um aluno presente.");
                return false;
            }
            $('#btnSalvar').html('<i class="fas fa-spinner fa-spin mr-2"></i> PROCESSANDO...').attr('disabled',
                true);
            return true;
        });
    </script>
</body>

</html>