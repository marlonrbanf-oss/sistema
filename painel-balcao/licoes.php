<?php
@session_start();
require_once("../conexao.php");

if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// Mapeamento Completo das 20 Lições do Módulo 1
$licoes_array = [
    1 => ["Princípio da Alavanca", "Gravata por trás", "Gangorra + Levantada técnica", "Defesa de tapa com queda", "Montada + Arm-lock", "Pegada pelas costas + Mata leão"],
    2 => ["Rolamento de costas + Fuga de quadril", "Upa e roda", "Postura de guarda e passagem", "Estrangulamento montado"],
    3 => ["Rolamento de frente", "Sprawl", "Abraço por cima dos braços por trás", "Saída de cotovelo", "Estrangulamento na guarda", "Raspagem tesourada", "Raspagem com mãos nos calcanhares"],
    4 => ["Abraço por cima dos braços", "Reposição de guarda", "Raspagem com mão na faixa", "Kimura"],
    5 => ["Koshi Guruma com defesa pessoal", "Baiana", "Toreando", "Kimura partindo do 100kg"],
    6 => ["Gravata por trás (sentido contrário)", "Virada de 4", "Americana", "Chave de braço girando"],
    7 => ["Defesa gravata com soco", "Defesa de gravata lateral", "Montada com chave de braço (ataque duplo)", "Montada com estrangulamento (ataque duplo)"],
    8 => ["Educativo para pé na virilha", "Raspagem pé na virilha com gancho", "Balão", "Arm-lock invertido"],
    9 => ["Guilhotina", "Guilhotina puxando pra guarda", "Raspagem com pêndulo", "Chave de braço na guarda"],
    10 => ["Empurrão no peito (1 e 2 mãos)", "Montada técnica com estrangulamento pela gola", "Montada técnica com chave de braço"],
    11 => ["Defesa de guilhotina", "Joelho na barriga (+ estrangulamento/arm-lock)"],
    12 => ["Baiana de joelhos", "Postura de guarda com a mão na manga", "Passagem para meia guarda", "Meia guarda para montada"],
    13 => ["Abraço pela frente por baixo dos braços", "Saída de montada com duas mãos na faixa", "Estrangulamento com braço enrolado", "Chave de braço na guarda em pé"],
    14 => ["Seat belt + pegada pelas costas", "Retenção das costas", "Estrangulamento de golas"],
    15 => ["Saída de gravata lateral/chão (com gancho)", "Defesa de pegada de costas (girando)"],
    16 => ["Single leg", "Reposição de guarda partindo de 4 apoios", "Guarda aberta / Triângulo", "Raspagem de tripé"],
    17 => ["Defesa de jab", "Cinturada por trás", "Estrangulamento relógio", "Rolo de 4 apoios"],
    18 => ["Pegada na perna com varredura por dentro", "Passagem com joelho por dentro / cabeça", "Arm-lock com braço enrolado"],
    19 => ["Ippon Seoi", "Raspagem de gancho", "Passagem de meia guarda joelho cruzado", "Ezequiel da montada"],
    20 => ["Esgrima", "Esgrima com single leg", "Passagem cruzando o joelho", "Montada técnica (com perna em pé)"]
];
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="utf-8">
    <title>Lançar Lição - Nova Arte BJJ</title>
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
</head>

<body class="bg-light">
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-dark text-white d-flex justify-content-between">
                <h5 class="mb-0">REGISTAR LIÇÃO E TÉCNICAS</h5>
                <a href="index.php" class="btn btn-outline-light btn-sm">Voltar</a>
            </div>
            <form action="lancar-selos.php" method="POST">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5 border-right">
                            <h6>1. Escolha a Lição</h6>
                            <select name="licao_id" id="licao_id" class="form-control mb-3" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($licoes_array as $n => $t) echo "<option value='$n'>Lição $n</option>"; ?>
                            </select>
                            <div id="preview-tecnicas" class="p-2 bg-white border rounded" style="min-height: 100px;">
                                <small class="text-muted">Selecione uma lição para ver as técnicas...</small>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <h6>2. Alunos Presentes</h6>
                            <input type="text" id="busca" class="form-control form-control-sm mb-2" placeholder="Procurar aluno...">
                            <div style="height: 300px; overflow-y: auto;" class="border rounded p-2 bg-white">
                                <table class="table table-sm">
                                    <tbody id="tabela_alunos">
                                        <?php
                                        $res = $pdo->query("SELECT id, nome, faixa FROM usuarios WHERE nivel = 'Cliente' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($res as $aluno) {
                                            echo "<tr>
                                                <td width='30'><input type='checkbox' name='alunos[]' value='{$aluno['id']}'></td>
                                                <td><small>{$aluno['nome']} ({$aluno['faixa']})</small></td>
                                              </tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-block">LANÇAR SELOS "EM ESTUDO"</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const licoes = <?php echo json_encode($licoes_array); ?>;
        $('#licao_id').change(function() {
            let id = $(this).val();
            if (id) {
                let html = '<strong>Técnicas:</strong><ul class="mb-0">';
                licoes[id].forEach(t => html += `<li><small>${t}</small></li>`);
                $('#preview-tecnicas').html(html + '</ul>');
            }
        });
        $("#busca").on("keyup", function() {
            var v = $(this).val().toLowerCase();
            $("#tabela_alunos tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1)
            });
        });
    </script>
</body>

</html>