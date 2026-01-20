<?php
@session_start();
require_once("../conexao.php");

if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// Mapeamento Completo com Técnicas e suas Categorias/Posições
$licoes_array = [
    1 => [
        ["t" => "Princípio da Alavanca", "p" => "Fundamentos"],
        ["t" => "Gravata por trás", "p" => "Defesa Pessoal"],
        ["t" => "Gangorra + Levantada técnica", "p" => "Fundamentos"],
        ["t" => "Defesa de tapa com queda", "p" => "Defesa Pessoal"],
        ["t" => "Montada + Arm-lock", "p" => "montada ataque (por cima)"],
        ["t" => "Pegada pelas costas + Mata leão", "p" => "costas ataque (por cima)"]
    ],
    2 => [
        ["t" => "Rolamento de costas + Fuga de quadril", "p" => "Fundamentos"],
        ["t" => "Upa e roda", "p" => "montada defesa (por baixo)"],
        ["t" => "Postura de guarda e passagem", "p" => "passagem de guarda"],
        ["t" => "Estrangulamento montado", "p" => "montada ataque (por cima)"]
    ],
    3 => [
        ["t" => "Rolamento de frente", "p" => "Fundamentos"],
        ["t" => "Sprawl", "p" => "Quedas"],
        ["t" => "Abraço por cima dos braços por trás", "p" => "Defesa Pessoal"],
        ["t" => "Saída de cotovelo", "p" => "montada defesa (por baixo)"],
        ["t" => "Estrangulamento na guarda", "p" => "guarda fechada por cima"],
        ["t" => "Raspagem tesourada", "p" => "guarda fechada por baixo"],
        ["t" => "Raspagem com mãos nos calcanhares", "p" => "guarda aberta"]
    ],
    4 => [
        ["t" => "Abraço por cima dos braços", "p" => "Defesa Pessoal"],
        ["t" => "Reposição de guarda", "p" => "100 kilos defesa (por baixo)"],
        ["t" => "Raspagem com mão na faixa", "p" => "guarda fechada por baixo"],
        ["t" => "Kimura", "p" => "guarda fechada por baixo"]
    ],
    5 => [
        ["t" => "Koshi Guruma com defesa pessoal", "p" => "Quedas"],
        ["t" => "Baiana", "p" => "Quedas"],
        ["t" => "Toreando", "p" => "passagem de guarda"],
        ["t" => "Kimura partindo do 100kg", "p" => "100 kilos ataque (por cima)"]
    ],
    6 => [
        ["t" => "Gravata por trás (sentido contrário)", "p" => "Defesa Pessoal"],
        ["t" => "Virada de 4", "p" => "100 kilos defesa (por baixo)"],
        ["t" => "Americana", "p" => "100 kilos ataque (por cima)"],
        ["t" => "Chave de braço girando", "p" => "100 kilos ataque (por cima)"]
    ],
    7 => [
        ["t" => "Defesa gravata com soco", "p" => "Defesa Pessoal"],
        ["t" => "Defesa de gravata lateral", "p" => "Defesa Pessoal"],
        ["t" => "Montada com chave de braço (ataque duplo)", "p" => "montada ataque (por cima)"],
        ["t" => "Montada com estrangulamento (ataque duplo)", "p" => "montada ataque (por cima)"]
    ],
    8 => [
        ["t" => "Educativo para pé na virilha", "p" => "Fundamentos"],
        ["t" => "Raspagem pé na virilha com gancho", "p" => "guarda aberta"],
        ["t" => "Balão", "p" => "guarda aberta"],
        ["t" => "Arm-lock invertido", "p" => "100 kilos ataque (por cima)"]
    ],
    9 => [
        ["t" => "Guilhotina", "p" => "defesa de finalizações"],
        ["t" => "Guilhotina puxando pra guarda", "p" => "Quedas"],
        ["t" => "Raspagem com pêndulo", "p" => "guarda fechada por baixo"],
        ["t" => "Chave de braço na guarda", "p" => "guarda fechada por baixo"]
    ],
    10 => [
        ["t" => "Empurrão no peito (1 e 2 mãos)", "p" => "Defesa Pessoal"],
        ["t" => "Montada técnica com estrangulamento pela gola", "p" => "montada ataque (por cima)"],
        ["t" => "Montada técnica com chave de braço", "p" => "montada ataque (por cima)"]
    ],
    11 => [
        ["t" => "Defesa de guilhotina", "p" => "defesa de finalizações"],
        ["t" => "Joelho na barriga (+ estrangulamento/arm-lock)", "p" => "100 kilos ataque (por cima)"]
    ],
    12 => [
        ["t" => "Baiana de joelhos", "p" => "Quedas"],
        ["t" => "Postura de guarda com a mão na manga", "p" => "guarda fechada por cima"],
        ["t" => "Passagem para meia guarda", "p" => "passagem de guarda"],
        ["t" => "Meia guarda para montada", "p" => "meia guarda por cima"]
    ],
    13 => [
        ["t" => "Abraço pela frente por baixo dos braços", "p" => "Defesa Pessoal"],
        ["t" => "Saída de montada com duas mãos na faixa", "p" => "montada defesa (por baixo)"],
        ["t" => "Estrangulamento com braço enrolado", "p" => "100 kilos ataque (por cima)"],
        ["t" => "Chave de braço na guarda em pé", "p" => "guarda fechada por baixo"]
    ],
    14 => [
        ["t" => "Seat belt + pegada pelas costas", "p" => "costas ataque (por cima)"],
        ["t" => "Retenção das costas", "p" => "costas ataque (por cima)"],
        ["t" => "Estrangulamento de golas", "p" => "costas ataque (por cima)"]
    ],
    15 => [
        ["t" => "Saída de gravata lateral/chão (com gancho)", "p" => "Defesa Pessoal"],
        ["t" => "Defesa de pegada de costas (girando)", "p" => "costas defesa (por baixo)"]
    ],
    16 => [
        ["t" => "Single leg", "p" => "Quedas"],
        ["t" => "Reposição de guarda partindo de 4 apoios", "p" => "4 apoios por defesa (por baixo)"],
        ["t" => "Guarda aberta / Triângulo", "p" => "guarda aberta"],
        ["t" => "Raspagem de tripé", "p" => "guarda aberta"]
    ],
    17 => [
        ["t" => "Defesa de jab", "p" => "Defesa Pessoal"],
        ["t" => "Cinturada por trás", "p" => "Quedas"],
        ["t" => "Estrangulamento relógio", "p" => "4 apoios ataque (por cima)"],
        ["t" => "Rolo de 4 apoios", "p" => "4 apoios por defesa (por baixo)"]
    ],
    18 => [
        ["t" => "Pegada na perna com varredura por dentro", "p" => "Quedas"],
        ["t" => "Passagem com joelho por dentro / cabeça", "p" => "passagem de guarda"],
        ["t" => "Arm-lock com braço enrolado", "p" => "100 kilos ataque (por cima)"]
    ],
    19 => [
        ["t" => "Ippon Seoi", "p" => "Quedas"],
        ["t" => "Raspagem de gancho", "p" => "guarda aberta"],
        ["t" => "Passagem de meia guarda joelho cruzado", "p" => "passagem de guarda"],
        ["t" => "Ezequiel da montada", "p" => "montada ataque (por cima)"]
    ],
    20 => [
        ["t" => "Esgrima", "p" => "Fundamentos"],
        ["t" => "Esgrima com single leg", "p" => "Quedas"],
        ["t" => "Passagem cruzando o joelho", "p" => "passagem de guarda"],
        ["t" => "Montada técnica (com perna em pé)", "p" => "montada ataque (por cima)"]
    ]
];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <title>Lançar Lição - Nova Arte BJJ</title>
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
</head>

<body class="bg-light">
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">REGISTRAR LIÇÃO E TÉCNICAS</h5>
                <a href="index.php" class="btn btn-outline-light btn-sm">Voltar</a>
            </div>
            <form action="lancar-selos.php" method="POST">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5 border-right">
                            <h6 class="font-weight-bold">1. Escolha a Lição do Dia</h6>
                            <select name="licao_id" id="licao_id" class="form-control mb-3" required>
                                <option value="">Selecione a lição...</option>
                                <?php foreach ($licoes_array as $n => $tecnicas) : ?>
                                    <option value="<?php echo $n; ?>">Lição <?php echo $n; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="preview-tecnicas" class="p-3 bg-white border rounded" style="min-height: 150px;">
                                <small class="text-muted">As técnicas da lição aparecerão aqui para conferência.</small>
                            </div>
                        </div>

                        <div class="col-md-7">
                            <h6 class="font-weight-bold">2. Alunos que participaram da aula</h6>
                            <input type="text" id="busca" class="form-control form-control-sm mb-2" placeholder="Pesquisar aluno por nome...">
                            <div style="height: 350px; overflow-y: auto;" class="border rounded p-2 bg-white">
                                <table class="table table-sm table-hover">
                                    <tbody id="tabela_alunos">
                                        <?php
                                        $query = $pdo->query("SELECT id, nome, faixa FROM usuarios WHERE nivel = 'Cliente' ORDER BY nome ASC");
                                        $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($res as $aluno) {
                                            $faixa = !empty($aluno['faixa']) ? $aluno['faixa'] : 'Branca';
                                        ?>
                                            <tr>
                                                <td width="30"><input type="checkbox" name="alunos[]" value="<?php echo $aluno['id']; ?>"></td>
                                                <td>
                                                    <span class="text-dark font-weight-bold"><?php echo $aluno['nome']; ?></span><br>
                                                    <small class="badge badge-secondary"><?php echo strtoupper($faixa); ?></small>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-lg btn-block shadow">
                        <i class="fas fa-save mr-2"></i> LANÇAR SELOS (STATUS: EM ESTUDO)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const licoes = <?php echo json_encode($licoes_array); ?>;

        $('#licao_id').change(function() {
            let id = $(this).val();
            if (id && licoes[id]) {
                let html = '<p class="mb-1 text-primary"><strong>Técnicas desta aula:</strong></p><ul class="pl-3 mb-0">';
                licoes[id].forEach(item => {
                    html += `<li><small><strong>${item.t}</strong> <span class="text-muted">(${item.p})</span></small></li>`;
                });
                $('#preview-tecnicas').html(html + '</ul>');
            } else {
                $('#preview-tecnicas').html('<small class="text-muted">Selecione uma lição para ver as técnicas...</small>');
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