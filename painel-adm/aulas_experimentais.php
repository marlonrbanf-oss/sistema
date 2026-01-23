<?php
@session_start();
require_once("../conexao.php");

// Verificação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$hoje = date('Y-m-d');

// Lógica para Salvar o Agendamento (Quando o Admin define Professor e Data)
if (isset($_POST['id_aula'])) {
    $id_aula = $_POST['id_aula'];
    $id_professor = $_POST['professor'];
    $data_aula = $_POST['data_aula'];
    $horario = $_POST['horario'];

    // CORREÇÃO: Mantendo a lógica de salvar a hora na observação para não dar erro de coluna inexistente
    $obs_final = "EXPERIMENTAL - Agendado para as: " . $horario;

    $res = $pdo->prepare("UPDATE aulas_particulares SET 
        professor_id = :prof, 
        data = :data, 
        observacoes = :obs 
        WHERE id = :id");

    $res->bindValue(":prof", $id_professor);
    $res->bindValue(":data", $data_aula);
    $res->bindValue(":obs", $obs_final);
    $res->bindValue(":id", $id_aula);
    $res->execute();

    echo "<script>alert('Aula experimental agendada com sucesso!'); window.location='aulas_experimentais.php';</script>";
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

        .btn-whatsapp {
            background-color: #25d366;
            color: white !important;
            border-radius: 5px;
            padding: 4px 8px;
            font-size: 0.8rem;
        }

        .thead-dark th {
            background-color: #343a40;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="font-weight-bold"><i class="fas fa-flask text-info mr-2"></i> Solicitações de
                        Experimentais</h4>
                    <a href="index.php" class="btn btn-sm btn-dark shadow-sm">Voltar ao Dashboard</a>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Aluno</th>
                                    <th>WhatsApp</th>
                                    <th>Data do Pedido</th>
                                    <th>Observação Atual</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // BUSCA CORRIGIDA: 
                                // 1. Garante que o professor_id seja 0 (ainda não agendado)
                                // 2. Filtra por 'EXPERIMENTAL' na observação
                                // 3. Se não aparecer nada aqui, o erro está no arquivo 'inserir-aluno.php' 
                                // que não está criando o registro nesta tabela.
                                $query = $pdo->query("SELECT a.*, u.nome as nome_aluno, u.telefone 
                                                      FROM aulas_particulares a 
                                                      INNER JOIN usuarios u ON a.aluno_id = u.id 
                                                      WHERE a.professor_id = '0' 
                                                      AND a.observacoes LIKE '%EXPERIMENTAL%' 
                                                      ORDER BY a.id DESC");
                                $res = $query->fetchAll(PDO::FETCH_ASSOC);

                                if (count($res) == 0) {
                                    echo "<tr><td colspan='5' class='text-center p-4 text-muted'>Nenhuma solicitação experimental pendente.</td></tr>";
                                }

                                foreach ($res as $row) {
                                    $whatsapp_num = preg_replace('/[^0-9]/', '', $row['telefone']);
                                ?>
                                    <tr>
                                        <td><b><?php echo $row['nome_aluno']; ?></b></td>
                                        <td>
                                            <a href="https://api.whatsapp.com/send?phone=55<?php echo $whatsapp_num; ?>"
                                                target="_blank" class="btn-whatsapp">
                                                <i class="fab fa-whatsapp"></i> Chamar
                                            </a>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($row['data'])); ?></td>
                                        <td><small><?php echo $row['observacoes']; ?></small></td>
                                        <td class="text-center">
                                            <button class="btn btn-primary btn-sm"
                                                onclick="agendar('<?php echo $row['id']; ?>', '<?php echo $row['nome_aluno']; ?>')">
                                                <i class="fas fa-calendar-alt"></i> Agendar Aula
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
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
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Confirmar Agendamento</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" id="id_aula" name="id_aula">
                        <p>Aluno: <b id="nome_aluno_modal"></b></p>

                        <div class="form-group">
                            <label>Professor Responsável</label>
                            <select class="form-control" name="professor" required>
                                <option value="">Escolha um Professor...</option>
                                <?php
                                $profs = $pdo->query("SELECT id, nome FROM usuarios WHERE nivel = 'Professor' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($profs as $p) {
                                    echo "<option value='{$p['id']}'>{$p['nome']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <label>Data da Aula</label>
                                <input type="date" class="form-control" name="data_aula" value="<?php echo $hoje; ?>"
                                    required>
                            </div>
                            <div class="col-6">
                                <label>Horário</label>
                                <input type="time" class="form-control" name="horario" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Salvar Agendamento</button>
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