<?php
@session_start();
require_once("../conexao.php");

// Proteção de acesso
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Balconista') {
    echo "<script>window.location='../login.php';</script>";
    exit();
}

$nome_usuario_logado = isset($_SESSION['nome_usuario']) ? $_SESSION['nome_usuario'] : 'Usuário';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Gestão Facial - Nova Arte</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">

    <style>
        body {
            background: #f4f6f9;
            font-family: sans-serif;
        }

        .user-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
            border: 1px solid #eee;
        }

        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: bold;
        }

        .btn-testar {
            position: fixed;
            bottom: 30px;
            right: 30px;
            border-radius: 50px;
            padding: 15px 25px;
            font-weight: bold;
            z-index: 1000;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .header-top {
            background: #fff;
            padding: 15px 25px;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 30px;
        }
    </style>
</head>

<body>

    <div class="header-top shadow-sm d-flex justify-content-between align-items-center">
        <h5 class="mb-0 font-weight-bold text-dark">
            <i class="fas fa-user-shield text-primary mr-2"></i> BIOMETRIA FACIAL
        </h5>
        <div>
            <span class="mr-3 small text-muted">Olá, <b><?php echo $nome_usuario_logado; ?></b></span>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Sair da Gestão</a>
        </div>
    </div>

    <div class="container">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <label class="small font-weight-bold text-muted">BUSCAR ALUNO</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-right-0"><i
                                class="fas fa-search text-muted"></i></span>
                    </div>
                    <input type="text" id="busca_aluno" class="form-control border-left-0 shadow-none"
                        placeholder="Digite o nome do atleta para cadastrar o rosto...">
                </div>
            </div>
        </div>

        <div id="lista_alunos">
            <div class="text-center text-muted py-5 mt-4">
                <i class="fas fa-id-card-alt fa-4x mb-3" style="opacity: 0.2;"></i>
                <p>Pesquise um aluno para gerenciar sua biometria.</p>
            </div>
        </div>
    </div>

    <a href="../terminal-presenca.php" target="_blank" class="btn btn-warning btn-testar">
        <i class="fas fa-external-link-alt mr-2"></i> IR PARA O TERMINAL
    </a>

    <?php include_once("modal-biometria.php"); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Busca dinâmica ao digitar
            $('#busca_aluno').on('keyup', function() {
                let busca = $(this).val();
                if (busca.length > 2) {
                    $.ajax({
                        url: 'acoes/buscar_alunos_facial.php',
                        method: 'POST',
                        data: {
                            nome: busca
                        },
                        success: function(data) {
                            $('#lista_alunos').html(data);
                        },
                        error: function() {
                            $('#lista_alunos').html(
                                '<div class="alert alert-danger">Erro na conexão com o servidor.</div>'
                            );
                        }
                    });
                } else if (busca.length === 0) {
                    $('#lista_alunos').html(
                        '<div class="text-center text-muted py-5 mt-4"><i class="fas fa-id-card-alt fa-4x mb-3" style="opacity: 0.2;"></i><p>Pesquise um aluno para gerenciar sua biometria.</p></div>'
                    );
                }
            });
        });

        // Função que é chamada pelo botão gerado no buscar_alunos_facial.php
        function abrirModalBio(id, nome) {
            // Preenche os dados no modal
            $('#id-aluno-bio').val(id);
            $('#nome-aluno-bio').text(nome);

            // Abre o modal
            $('#modalBiometria').modal('show');

            // Timeout de 500ms para garantir que o modal terminou de abrir antes de iniciar a câmera
            setTimeout(function() {
                if (typeof iniciarCameraBio === "function") {
                    iniciarCameraBio();
                } else {
                    console.error("Função iniciarCameraBio não encontrada no modal-biometria.php");
                }
            }, 500);
        }
    </script>
</body>

</html>