<?php
@session_start();
require_once("../conexao.php");

// Proteção de acesso
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "<script>window.location='../login.php';</script>";
    exit();
}
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
        }

        .user-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .status-badge {
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-user-shield text-primary"></i> Gestão de Biometria Facial</h4>
            <a href="index.php" class="btn btn-dark btn-sm">Voltar ao Painel</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-right-0"><i
                                class="fas fa-search text-muted"></i></span>
                    </div>
                    <input type="text" id="busca_aluno" class="form-control border-left-0"
                        placeholder="Digite o nome do aluno para buscar...">
                </div>
            </div>
        </div>

        <div id="lista_alunos">
            <div class="text-center text-muted py-5">
                <i class="fas fa-users fa-3x mb-3"></i>
                <p>Use a barra acima para pesquisar um aluno.</p>
            </div>
        </div>
    </div>

    <a href="../terminal-presenca.php" target="_blank" class="btn btn-warning shadow-lg"
        style="position: fixed; bottom: 30px; right: 30px; border-radius: 50px; padding: 15px 25px; font-weight: bold;">
        <i class="fas fa-external-link-alt mr-2"></i> TESTAR NO TERMINAL
    </a>

    <?php include_once("modal-biometria.php"); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/bootstrap.js"></script>

    <script>
        $(document).ready(function() {
            $('#busca_aluno').keyup(function() {
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
                        }
                    });
                }
            });
        });

        function abrirModalBio(id, nome) {
            $('#id-aluno-bio').val(id);
            $('#nome-aluno-bio').text(nome);
            $('#modalBiometria').modal('show');
            // Função para iniciar a câmera do modal
            iniciarCameraBio();
        }
    </script>
</body>

</html>