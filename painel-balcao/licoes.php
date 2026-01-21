<?php
@session_start();
require_once("../conexao.php");

if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// Mapeamento Completo com Técnicas e suas Categorias/Posições (1 a 50)
$licoes_array = [
    1 => [
        ["t" => "Princípio da Alavanca", "p" => "Fundamentos"],
        ["t" => "Gravata por trás", "p" => "Defesa Pessoal"],
        ["t" => "Gangorra + Levantada técnica", "p" => "Fundamentos"],
        ["t" => "Defesa de tapa com queda", "p" => "Defesa Pessoal"],
        ["t" => "Montada + Arm-lock", "p" => "montada ataque (por cima)"],
        ["t" => "Pegada pelas costas + Mata leão", "p" => "costas ataque"]
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
        ["t" => "Seat belt + pegada pelas costas", "p" => "costas ataque"],
        ["t" => "Retenção das costas", "p" => "costas ataque"],
        ["t" => "Estrangulamento de golas", "p" => "costas ataque"]
    ],
    15 => [
        ["t" => "Saída de gravata lateral/chão (com gancho)", "p" => "Defesa Pessoal"],
        ["t" => "Defesa de pegada de costas (girando)", "p" => "costas defesa"]
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
    ],
    21 => [
        ["t" => "Raspagem DLR (Chutar o joelho)", "p" => "guarda aberta"],
        ["t" => "Raspagem DLR (Gancho Invertido)", "p" => "guarda aberta"],
        ["t" => "Raspagem DLR (Indo para as costas)", "p" => "guarda aberta"]
    ],
    22 => [
        ["t" => "Passagem DLR (Estouro de gancho e esgrima)", "p" => "passagem de guarda"],
        ["t" => "Passagem DLR (Pressão Knee slide)", "p" => "passagem de guarda"],
        ["t" => "Defesa de Omoplata (Rolamento antecipado)", "p" => "defesa de finalizações"]
    ],
    23 => [
        ["t" => "Raspagem Omoplata (Capotagem clássica)", "p" => "guarda fechada por baixo"],
        ["t" => "Raspagem Omoplata (Variação gola cruzada)", "p" => "guarda fechada por baixo"],
        ["t" => "Raspagem Omoplata (Saindo para o Single Leg)", "p" => "guarda fechada por baixo"]
    ],
    24 => [
        ["t" => "Passagem após tentativa de Omoplata (Circundar)", "p" => "passagem de guarda"],
        ["t" => "Passagem após tentativa de Omoplata (Salto oposto)", "p" => "passagem de guarda"],
        ["t" => "Finalização de Omoplata (Ajuste braço na cintura)", "p" => "guarda fechada por baixo"]
    ],
    25 => [
        ["t" => "Raspagem Guarda X (Desequilíbrio para trás)", "p" => "guarda aberta"],
        ["t" => "Raspagem Guarda X (Levantada técnica)", "p" => "guarda aberta"],
        ["t" => "Raspagem Guarda X (Chutando o quadril)", "p" => "guarda aberta"]
    ],
    26 => [
        ["t" => "Passagem Guarda X (Grampo e Step over)", "p" => "passagem de guarda"],
        ["t" => "Passagem Guarda X (Passagem por cima do quadril)", "p" => "passagem de guarda"],
        ["t" => "Finalização: Triângulo das costas", "p" => "costas ataque"]
    ],
    27 => [
        ["t" => "Raspagem Meia Profunda (Fura-bolo)", "p" => "meia guarda por cima"],
        ["t" => "Raspagem Meia Profunda (Waiter Sweep)", "p" => "meia guarda por cima"],
        ["t" => "Raspagem Meia Profunda (Saída para as costas)", "p" => "meia guarda por cima"]
    ],
    28 => [
        ["t" => "Passagem Meia Profunda (Esgrima de quadril)", "p" => "passagem de guarda"],
        ["t" => "Passagem Meia Profunda (Peso morto)", "p" => "passagem de guarda"],
        ["t" => "Saída de costas (Giro para a guarda)", "p" => "costas defesa"]
    ],
    29 => [
        ["t" => "Raspagem One Leg X (Ataque no calcanhar)", "p" => "guarda aberta"],
        ["t" => "Raspagem One Leg X (Tesoura técnica)", "p" => "guarda aberta"],
        ["t" => "Finalização: Arco e flecha (Bow and Arrow)", "p" => "costas ataque"]
    ],
    30 => [
        ["t" => "Passagem One Leg X (Esgrima e quadril baixo)", "p" => "passagem de guarda"],
        ["t" => "Passagem One Leg X (Estouro e step back)", "p" => "passagem de guarda"],
        ["t" => "Saída de costas (Deslizar quadril e ponte)", "p" => "costas defesa"]
    ],
    31 => [
        ["t" => "Finalização: Arm-lock da Montada S", "p" => "montada ataque (por cima)"],
        ["t" => "Finalização: Triângulo da Montada", "p" => "montada ataque (por cima)"],
        ["t" => "Saída de montada técnica (Camarão lateral)", "p" => "montada defesa (por baixo)"]
    ],
    32 => [
        ["t" => "Finalização: Kata-gatame (Triângulo de mão)", "p" => "100 kilos ataque (por cima)"],
        ["t" => "Finalização: Estrangulamento Norte-Sul", "p" => "100 kilos ataque (por cima)"],
        ["t" => "Saída de 100kg (Indo para os 4 apoios)", "p" => "100 kilos defesa (por baixo)"]
    ],
    33 => [
        ["t" => "Finalização: Crucifixo das costas", "p" => "costas ataque"],
        ["t" => "Finalização: Estrangulamento gola técnica", "p" => "costas ataque"],
        ["t" => "Defesa de Mata-leão (Mão na gola técnica)", "p" => "costas defesa"]
    ],
    34 => [
        ["t" => "Estrangulamento Relógio (Clock Choke)", "p" => "4 apoios ataque (por cima)"],
        ["t" => "Ataque de 4 apoios (Ida para as costas)", "p" => "4 apoios ataque (por cima)"],
        ["t" => "Rolo de 4 apoios para reposição de guarda", "p" => "4 apoios por defesa (por baixo)"]
    ],
    35 => [
        ["t" => "Finalização: Kimura partindo do 100kg", "p" => "100 kilos ataque (por cima)"],
        ["t" => "Finalização: Arm-lock invertido (100kg)", "p" => "100 kilos ataque (por cima)"],
        ["t" => "Saída de 100kg (Reposição perna de fora)", "p" => "100 kilos defesa (por baixo)"]
    ],
    36 => [
        ["t" => "Passagem de Guarda: Leg Drag técnica", "p" => "passagem de guarda"],
        ["t" => "Passagem de Guarda: Smash Pass (Pressão)", "p" => "passagem de guarda"],
        ["t" => "Defesa de Triângulo (Postura e amasso)", "p" => "defesa de finalizações"]
    ],
    37 => [
        ["t" => "Passagem de Guarda: Over-Under profunda", "p" => "passagem de guarda"],
        ["t" => "Passagem de Guarda: X-Pass (Em pé)", "p" => "passagem de guarda"],
        ["t" => "Defesa de Arm-lock (Saída Hitchhiker)", "p" => "defesa de finalizações"]
    ],
    38 => [
        ["t" => "Finalização: Arm-lock Joelho na Barriga", "p" => "100 kilos ataque (por cima)"],
        ["t" => "Finalização: Ezequiel técnica (100kg)", "p" => "100 kilos ataque (por cima)"],
        ["t" => "Saída de montada (Upa travamento técnico)", "p" => "montada defesa (por baixo)"]
    ],
    39 => [
        ["t" => "Finalização: Triângulo invertido (Guarda)", "p" => "guarda fechada por baixo"],
        ["t" => "Finalização: Omoplata (Guarda aberta)", "p" => "guarda aberta"],
        ["t" => "Defesa de Kimura (Contra-ataque)", "p" => "defesa de finalizações"]
    ],
    40 => [
        ["t" => "Finalização: Guilhotina da Montada", "p" => "montada ataque (por cima)"],
        ["t" => "Finalização: Ezequiel da Montada", "p" => "montada ataque (por cima)"],
        ["t" => "Saída de 4 apoios (Levantada técnica)", "p" => "4 apoios por defesa (por baixo)"]
    ],
    41 => [
        ["t" => "Transição DLR para X-Guard", "p" => "guarda aberta"],
        ["t" => "Raspagem de X-Guard (Variação tesoura)", "p" => "guarda aberta"],
        ["t" => "Passagem de pressão em pé", "p" => "passagem de guarda"]
    ],
    42 => [
        ["t" => "Passagem Guarda Aranha (Estouro de pegada)", "p" => "passagem de guarda"],
        ["t" => "Passagem circundando as pernas", "p" => "passagem de guarda"],
        ["t" => "Contra-ataque de triângulo na passagem", "p" => "defesa de finalizações"]
    ],
    43 => [
        ["t" => "Raspagem Meia Guarda (Gancho lateral)", "p" => "meia guarda por cima"],
        ["t" => "Raspagem Meia Guarda (Pêndulo profundo)", "p" => "meia guarda por cima"],
        ["t" => "Finalização: Kimura da meia guarda", "p" => "meia guarda por cima"]
    ],
    44 => [
        ["t" => "Saída de 100kg para 4 apoios (Antecipação)", "p" => "100 kilos defesa (por baixo)"],
        ["t" => "Reposição de meia guarda profunda", "p" => "100 kilos defesa (por baixo)"],
        ["t" => "Saída de 100kg (Esgrima e quadril)", "p" => "100 kilos defesa (por baixo)"]
    ],
    45 => [
        ["t" => "Ataque de 4 apoios (Triângulo de mão)", "p" => "4 apoios ataque (por cima)"],
        ["t" => "Ataque de 4 apoios (Transição para montada)", "p" => "4 apoios ataque (por cima)"],
        ["t" => "Estrangulamento de gola (4 apoios)", "p" => "4 apoios ataque (por cima)"]
    ],
    46 => [
        ["t" => "Passagem Emborrachando profunda", "p" => "passagem de guarda"],
        ["t" => "Passagem cruzando o braço (Gift Wrap)", "p" => "passagem de guarda"],
        ["t" => "Finalização: Arm-lock da passagem", "p" => "passagem de guarda"]
    ],
    47 => [
        ["t" => "Raspagem Balão (Tomoe-Nage gancho)", "p" => "guarda aberta"],
        ["t" => "Raspagem Helicóptero técnica", "p" => "guarda aberta"],
        ["t" => "Ataque de Omoplata da guarda aberta", "p" => "guarda aberta"]
    ],
    48 => [
        ["t" => "Mata-leão controle punho (4 apoios)", "p" => "costas ataque"],
        ["t" => "Ataque de braço das costas (Arm-lock)", "p" => "costas ataque"],
        ["t" => "Transição das costas para Triângulo", "p" => "costas ataque"]
    ],
    49 => [
        ["t" => "Reposição de guarda de 4 apoios (Rolamento)", "p" => "4 apoios por defesa (por baixo)"],
        ["t" => "Fuga de quadril dos 4 apoios (Sit-out)", "p" => "4 apoios por defesa (por baixo)"],
        ["t" => "Defesa de ganchos das costas", "p" => "costas defesa"]
    ],
    50 => [
        ["t" => "Triângulo da Montada Técnica (S-Mount)", "p" => "montada ataque (por cima)"],
        ["t" => "Finalização: Arm-lock técnico da montada", "p" => "montada ataque (por cima)"],
        ["t" => "Transição Joelho na barriga para montada", "p" => "montada ataque (por cima)"]
    ]
];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lançar Lição - Nova Arte BJJ</title>
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        .radio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(65px, 1fr));
            gap: 10px;
        }

        .btn-radio {
            cursor: pointer;
            text-align: center;
            border: 1px solid #dee2e6;
            padding: 10px 5px;
            border-radius: 5px;
            background: #fff;
            transition: all 0.2s;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .btn-radio:hover {
            background: #f8f9fa;
        }

        input[type="radio"]:checked+.btn-radio {
            background-color: #343a40;
            color: #fff;
            border-color: #343a40;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .radio-grid {
                grid-template-columns: repeat(auto-fill, minmax(55px, 1fr));
                gap: 8px;
            }

            .border-right {
                border-right: none !important;
                border-bottom: 1px solid #dee2e6;
                margin-bottom: 20px;
                padding-bottom: 20px;
            }
        }

        /* Estilo para os rádios de Status */
        .status-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-status {
            cursor: pointer;
            padding: 10px 20px;
            border: 2px solid #dee2e6;
            border-radius: 30px;
            font-weight: bold;
            transition: 0.3s;
            background: #fff;
        }

        input[name="status"]:checked+.btn-estudo {
            background: #ffc107;
            border-color: #ffc107;
            color: #000;
        }

        input[name="status"]:checked+.btn-revisao {
            background: #17a2b8;
            border-color: #17a2b8;
            color: #fff;
        }

        input[name="status"]:checked+.btn-concluido {
            background: #28a745;
            border-color: #28a745;
            color: #fff;
        }

        input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .aluno-row {
            cursor: pointer;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container-fluid mt-3">
        <div class="card shadow">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">REGISTRAR LIÇÃO</h5>
                <a href="index.php" class="btn btn-outline-light btn-sm">Voltar</a>
            </div>
            <form action="lancar-selos.php" method="POST">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 border-right">
                            <h6 class="font-weight-bold mb-3 text-primary"><i class="fas fa-list-ol mr-2"></i>1. Escolha
                                a Lição</h6>
                            <div class="radio-grid mb-4">
                                <?php foreach ($licoes_array as $n => $tecnicas) : ?>
                                    <label class="m-0">
                                        <input type="radio" name="licao_id" value="<?php echo $n; ?>" class="d-none"
                                            required>
                                        <div class="btn-radio">L-<?php echo $n; ?></div>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <h6 class="font-weight-bold">Técnicas desta aula:</h6>
                            <div id="preview-tecnicas" class="p-3 bg-white border rounded shadow-sm"
                                style="min-height: 150px;">
                                <small class="text-muted">Selecione uma lição para visualizar as técnicas.</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="font-weight-bold mb-3 text-primary"><i class="fas fa-users mr-2"></i>2. Alunos
                                Presentes</h6>
                            <div class="input-group input-group-sm mb-2">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                </div>
                                <input type="text" id="busca" class="form-control"
                                    placeholder="Buscar aluno pelo nome...">
                            </div>

                            <div style="height: 350px; overflow-y: auto;" class="border rounded bg-white">
                                <table class="table table-sm table-hover mb-0">
                                    <tbody id="tabela_alunos">
                                        <?php
                                        $query = $pdo->query("SELECT id, nome FROM usuarios WHERE nivel = 'Cliente' ORDER BY nome ASC");
                                        $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($res as $aluno) {
                                        ?>
                                            <tr class="aluno-row">
                                                <td width="45" class="text-center align-middle">
                                                    <input type="checkbox" name="alunos[]"
                                                        value="<?php echo $aluno['id']; ?>">
                                                </td>
                                                <td class="align-middle">
                                                    <span
                                                        class="text-dark font-weight-bold d-block py-2"><?php echo $aluno['nome']; ?></span>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row mt-3">
                        <div class="col-12 text-center">
                            <h6 class="font-weight-bold mb-3 text-primary"><i class="fas fa-star mr-2"></i>3. Status do
                                Conhecimento</h6>
                            <div class="status-container">
                                <label class="m-0">
                                    <input type="radio" name="status" value="Em Estudo" class="d-none" checked>
                                    <div class="btn-status btn-estudo">Em Estudo</div>
                                </label>
                                <label class="m-0">
                                    <input type="radio" name="status" value="Revisão" class="d-none">
                                    <div class="btn-status btn-revisao">Revisão</div>
                                </label>
                                <label class="m-0">
                                    <input type="radio" name="status" value="Concluído" class="d-none">
                                    <div class="btn-status btn-concluido">Concluído</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white p-3">
                    <button type="submit" class="btn btn-success btn-lg btn-block shadow font-weight-bold py-3">
                        <i class="fas fa-check-circle mr-2"></i> FINALIZAR LANÇAMENTO
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const licoes = <?php echo json_encode($licoes_array); ?>;

        $('input[name="licao_id"]').change(function() {
            let id = $(this).val();
            if (id && licoes[id]) {
                let html = '<ul class="list-group list-group-flush">';
                licoes[id].forEach(item => {
                    html += `<li class="list-group-item px-0 py-2 border-0">
                                <i class="fas fa-chevron-right text-success mr-2 small"></i>
                                <strong>${item.t}</strong> <br>
                                <span class="badge badge-light border text-muted ml-3">${item.p}</span>
                             </li>`;
                });
                $('#preview-tecnicas').html(html + '</ul>');
            }
        });

        $('.aluno-row').click(function(e) {
            if (e.target.type !== 'checkbox') {
                let cb = $(this).find('input[type="checkbox"]');
                cb.prop('checked', !cb.prop('checked'));
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