<?php
@session_start();
require_once("../conexao.php");

// Verificação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$hoje = date('Y-m-d');

// Lógica para Salvar o Agendamento - ATUALIZADO PARA professor_id
if (isset($_POST['id_aula'])) {
    $id_aula = $_POST['id_aula'];
    $id_professor = $_POST['professor'];
    $data_aula = $_POST['data_aula'];
    $horario = $_POST['horario'];

    $res = $pdo->prepare("UPDATE aulas_particulares SET professor_id = :prof, data = :data, horario = :hora WHERE id = :id");
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

        .btn-whatsapp {
            background-color: #25d366;
            color: white !important;
            border-radius: 5px;
            padding: 4px 8px;
            font-size: 0.8rem;
        }

        .btn-whatsapp:hover {
            background-color: #1ebe57;
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
                    <a href="index.php" class="btn btn-sm btn-dark shadow-sm">Voltar ao Dashboard</a>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Aluno</th>
                                    <th>Contacto (WhatsApp)</th>
                                    <th>Data do Pedido</th>
                                    <th>Situação</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // QUERY ATUALIZADA: aluno_id, professor_id e observacoes
                                $query = $pdo->query("SELECT a.*, u.nome as nome_aluno, u.telefone 
                                                      FROM aulas_particulares a 
                                                      INNER JOIN usuarios u ON a.aluno_id = u.id 
                                                      WHERE a.professor_id = '0' AND a.observacoes LIKE '%EXPERIMENTAL%' 
                                                      ORDER BY a.id DESC");
                                $res = $query->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($res as $row) {
                                    $dataF = date('d/m/Y', strtotime($row['data']));
                                    $tel = $row['telefone'];
                                    $whatsapp_num = preg_replace('/[^0-9]/', '', $tel);

                                    $texto_wp = "Olá " . $row['nome_aluno'] . ", aqui é da *Academia Nova Arte BJJ*! Recebemos seu pedido para uma aula experimental. Gostaria de verificar qual o melhor horário para você?";
                                    $texto_url = urlencode($texto_wp);
                                ?>
                                    <tr>
                                        <td><b><?php echo $row['nome_aluno']; ?></b></td>
                                        <td>
                                            <a href="https://api.whatsapp.com/send?phone=55<?php echo $whatsapp_num; ?>&text=<?php echo $texto_url; ?>"
                                                target="_blank" class="btn-whatsapp shadow-sm">
                                                <i class="fab fa-whatsapp mr-1"></i> Chamar Aluno
                                            </a>
                                            <small class="text-muted ml-2"><?php echo $tel; ?></small>
                                        </td>
                                        <td><?php echo $dataF; ?></td>
                                        <td><span class="status-badge bg-warning text-dark border">Pendente</span></td>
                                        <td class="text-center">
                                            <button class="btn btn-primary btn-sm shadow-sm"
                                                onclick="agendar('<?php echo $row['id']; ?>', '<?php echo $row['nome_aluno']; ?>')">
                                                <i class="fas fa-calendar-alt mr-1"></i> Agendar Aula
                                            </button>
                                        </td>
                                    </tr>
                                <?php }
                                if (count($res) == 0) echo "<tr><td colspan='5' class='text-center p-5 text-muted'>Nenhuma nova solicitação de aula experimental no momento.</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAgendar" tabindex="-1" role="dialog">
        <div class="modal-dialog shadow-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title font-weight-bold">Confirmar Agendamento</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" id="id_aula" name="id_aula">
                        <div class="alert alert-secondary text-dark border-0">Aluno: <b id="nome_aluno_modal"></b></div>

                        <div class="form-group">
                            <label class="font-weight-bold">Selecionar Professor</label>
                            <select class="form-control form-control-lg" name="professor" required
                                style="font-size: 1rem;">
                                <option value="">Escolha um instrutor...</option>
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
                                    <label class="font-weight-bold">Data</label>
                                    <input type="date" class="form-control" name="data_aula"
                                        value="<?php echo $hoje; ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Horário</label>
                                    <input type="time" class="form-control" name="horario" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success px-4">Salvar Agendamento</button>
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