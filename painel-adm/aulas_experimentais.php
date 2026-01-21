<?php
@session_start();
require_once("../conexao.php");

// Verificação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$hoje = date('Y-m-d');

// Lógica para Salvar o Agendamento (Quando o formulário do modal é enviado)
if (isset($_POST['id_aula'])) {
    $id_aula = $_POST['id_aula'];
    $id_professor = $_POST['professor'];
    $data_aula = $_POST['data_aula'];
    $horario = $_POST['horario'];

    $res = $pdo->prepare("UPDATE aulas_particulares SET professor = :prof, data = :data, horario = :hora, status = 'Agendada' WHERE id = :id");
    $res->bindValue(":prof", $id_professor);
    $res->bindValue(":data", $data_aula);
    $res->bindValue(":hora", $horario);
    $res->bindValue(":id", $id_aula);
    $res->execute();
    echo "<script>alert('Aula agendada com sucesso!'); window.location='aulas_experimentais.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Aulas Experimentais - Nova Arte BJJ</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
    <style>
        body {
            background: #f4f6f9;
            font-size: 0.9rem;
        }

        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="font-weight-bold"><i class="fas fa-flask text-info mr-2"></i> Solicitações de Aulas
                        Experimentais</h4>
                    <a href="index.php" class="btn btn-sm btn-dark">Voltar ao Dashboard</a>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Aluno</th>
                                    <th>Contacto</th>
                                    <th>Data Pedido</th>
                                    <th>Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Procura apenas aulas com status 'Experimental'
                                $query = $pdo->query("SELECT a.*, u.nome as nome_aluno, u.telefone 
                                                      FROM aulas_particulares a 
                                                      INNER JOIN usuarios u ON a.cliente = u.id 
                                                      WHERE a.status = 'Experimental' ORDER BY a.id DESC");
                                $res = $query->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($res as $row) {
                                    $dataF = date('d/m/Y', strtotime($row['data']));
                                    $tel = $row['telefone'];
                                    $whatsapp = preg_replace('/[^0-9]/', '', $tel);
                                ?>
                                    <tr>
                                        <td><b><?php echo $row['nome_aluno']; ?></b></td>
                                        <td>
                                            <a href="https://wa.me/<?php echo $whatsapp; ?>" target="_blank"
                                                class="text-success">
                                                <i class="fab fa-whatsapp mr-1"></i> <?php echo $tel; ?>
                                            </a>
                                        </td>
                                        <td><?php echo $dataF; ?></td>
                                        <td><span class="status-badge bg-warning text-dark">Pendente</span></td>
                                        <td class="text-center">
                                            <button class="btn btn-primary btn-sm"
                                                onclick="agendar('<?php echo $row['id']; ?>', '<?php echo $row['nome_aluno']; ?>')">
                                                <i class="fas fa-calendar-check mr-1"></i> Agendar Professor
                                            </button>
                                        </td>
                                    </tr>
                                <?php }
                                if (count($res) == 0) echo "<tr><td colspan='5' class='text-center p-4 text-muted'>Nenhuma nova solicitação encontrada.</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAgendar" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Agendar Aula Experimental</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" id="id_aula" name="id_aula">
                        <p>Aluno: <b id="nome_aluno_modal"></b></p>

                        <div class="form-group">
                            <label>Escolher Professor</label>
                            <select class="form-control" name="professor" required>
                                <option value="">Selecione um Professor</option>
                                <?php
                                $query_p = $pdo->query("SELECT id, nome FROM usuarios WHERE nivel = 'Professor' ORDER BY nome ASC");
                                $professores = $query_p->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($professores as $p) {
                                    echo "<option value='{$p['id']}'>{$p['nome']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Data da Aula</label>
                                    <input type="date" class="form-control" name="data_aula"
                                        value="<?php echo $hoje; ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Horário</label>
                                    <input type="time" class="form-control" name="horario" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Agendamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function agendar(id, nome) {
            $('#id_aula').val(id);
            $('#nome_aluno_modal').text(nome);
            $('#modalAgendar').modal('show');
        }
    </script>
</body>

</html>