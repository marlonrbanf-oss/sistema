<?php
@session_start();
require_once("../conexao.php"); 

// Validação de Segurança
if (!isset($_SESSION['id_usuario'])) { echo "<script>window.location='../login.php';</script>"; exit(); }
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Grade de Horários - Nova Arte</title>
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <style>
    .table th {
        background-color: #343a40 !important;
        color: white;
        vertical-align: middle !important;
    }

    .table td {
        vertical-align: middle !important;
        font-size: 0.9rem;
        height: 50px;
    }

    .horario-destaque {
        background-color: #f4f6f9;
        font-weight: bold;
        width: 100px;
    }

    .borda-viva {
        border: 1px solid #dee2e6 !important;
    }
    </style>
</head>

<body class="hold-transition">
    <div class="wrapper">
        <div class="content-wrapper ml-0 bg-white">
            <section class="content pt-3">
                <div class="container-fluid">
                    <div class="card shadow-sm borda-viva">
                        <div class="card-header bg-dark">
                            <h3 class="card-title"><i class="fas fa-clock mr-2"></i> Grade de Horários</h3>
                            <div class="card-tools">
                                <a href="index.php" class="btn btn-tool text-white"><i class="fas fa-arrow-left"></i>
                                    Voltar</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped text-center mb-0">
                                    <thead>
                                        <tr>
                                            <th>Horário</th>
                                            <th>Segunda</th>
                                            <th>Terça</th>
                                            <th>Quarta</th>
                                            <th>Quinta</th>
                                            <th>Sexta</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="horario-destaque">06:15</td>
                                            <td>Intermediário</td>
                                            <td>—</td>
                                            <td>Intermediário</td>
                                            <td>—</td>
                                            <td>Intermediário</td>
                                        </tr>
                                        <tr>
                                            <td class="horario-destaque">08:00</td>
                                            <td>Iniciante</td>
                                            <td>Iniciante</td>
                                            <td>Iniciante</td>
                                            <td>Iniciante</td>
                                            <td>Iniciante</td>
                                        </tr>
                                        <tr>
                                            <td class="horario-destaque">09:00</td>
                                            <td>Intermediário</td>
                                            <td>Intermediário</td>
                                            <td>Intermediário</td>
                                            <td>Intermediário</td>
                                            <td>—</td>
                                        </tr>
                                        <tr>
                                            <td class="horario-destaque">10:00</td>
                                            <td>—</td>
                                            <td>—</td>
                                            <td>—</td>
                                            <td>—</td>
                                            <td class="font-weight-bold">NO-GI</td>
                                        </tr>
                                        <tr>
                                            <td class="horario-destaque">17:00</td>
                                            <td>Iniciante</td>
                                            <td>—</td>
                                            <td>Iniciante</td>
                                            <td>—</td>
                                            <td>—</td>
                                        </tr>
                                        <tr>
                                            <td class="horario-destaque">19:00</td>
                                            <td>Iniciante</td>
                                            <td>Iniciante / NO-GI</td>
                                            <td>Iniciante</td>
                                            <td>Iniciante / NO-GI</td>
                                            <td>Iniciante / Intermediário</td>
                                        </tr>
                                        <tr>
                                            <td class="horario-destaque">20:00</td>
                                            <td>Iniciante / Intermediário</td>
                                            <td>Iniciante / Intermediário</td>
                                            <td>Iniciante / Intermediário</td>
                                            <td>Iniciante / Intermediário</td>
                                            <td>—</td>
                                        </tr>
                                        <tr>
                                            <td class="horario-destaque">21:00</td>
                                            <td>Intermediário</td>
                                            <td>—</td>
                                            <td>Intermediário</td>
                                            <td>—</td>
                                            <td>—</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-3 shadow-sm">
                        <i class="fas fa-info-circle text-primary mr-2"></i>
                        <small class="text-muted">A permanência no tatame após o treino deve respeitar o início da
                            próxima turma.</small>
                    </div>
                </div>
            </section>
        </div>
    </div>
</body>

</html>