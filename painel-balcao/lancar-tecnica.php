<?php
@session_start();
require_once("../conexao.php");

if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Balconista') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

if (!isset($_GET['id'])) {
    echo "<script>window.location='usuarios.php';</script>";
    exit();
}

$id_aluno = $_GET['id'];
$query = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
$query->bindValue(":id", $id_aluno);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($res) == 0) {
    exit();
}
$nome_aluno = $res[0]['nome'];

// Mapeamento de Técnicas x Posições (ATUALIZADO COM LIÇÕES 1 A 50)
$mapa_tecnicas = [
    'Fundamentos' => ['Princípio da Alavanca', 'Gangorra + Levantada técnica', 'Rolamento de costas + Fuga de quadril', 'Rolamento de frente', 'Educativo para pé na virilha', 'Esgrima'],
    'Defesa Pessoal' => ['Gravata por trás', 'Defesa de tapa com queda', 'Abraço por cima dos braços por trás', 'Abraço por cima dos braços', 'Gravata por trás (sentido contrário)', 'Defesa gravata com soco', 'Defesa de gravata lateral', 'Empurrão no peito (1 e 2 mãos)', 'Abraço pela frente por baixo dos braços', 'Saída de gravata lateral/chão (com gancho)', 'Defesa de jab'],
    'Quedas' => ['Sprawl', 'Koshi Guruma com defesa pessoal', 'Baiana', 'Guilhotina puxando pra guarda', 'Baiana de joelhos', 'Single leg', 'Cinturada por trás', 'Pegada na perna com varredura por dentro', 'Ippon Seoi', 'Esgrima com single leg'],
    'montada ataque (por cima)' => ['Montada + Arm-lock', 'Estrangulamento montado', 'Montada com chave de braço (ataque duplo)', 'Montada com estrangulamento (ataque duplo)', 'Montada técnica com estrangulamento pela gola', 'Montada técnica com chave de braço', 'Ezequiel da montada', 'Montada técnica (com perna em pé)', 'Arm-lock da Montada S', 'Triângulo da Montada', 'Guilhotina da Montada', 'Ezequiel da Montada', 'Triângulo da Montada Técnica (S-Mount)', 'Arm-lock técnico da montada'],
    'costas ataque' => ['Pegada pelas costas + Mata leão', 'Seat belt + pegada pelas costas', 'Retenção das costas', 'Estrangulamento de golas', 'Triângulo das costas', 'Arco e flecha (Bow and Arrow)', 'Crucifixo das costas', 'Estrangulamento gola técnica', 'Mata-leão controle punho (4 apoios)', 'Ataque de braço das costas (Arm-lock)', 'Transição das costas para Triângulo'],
    'montada defesa (por baixo)' => ['Upa e roda', 'Saída de cotovelo', 'Saída de montada com duas mãos na faixa', 'Saída de montada técnica (Camarão lateral)', 'Saída de montada (Upa travamento técnico)'],
    'passagem de guarda' => ['Postura de guarda e passagem', 'Toreando', 'Passagem para meia guarda', 'Passagem com joelho por dentro / cabeça', 'Passagem de meia guarda joelho cruzado', 'Passagem cruzando o joelho', 'Passagem DLR (Estouro de gancho e esgrima)', 'Passagem DLR (Pressão Knee slide)', 'Passagem após tentativa de Omoplata (Circundar)', 'Passagem após tentativa de Omoplata (Salto oposto)', 'Passagem Guarda X (Grampo e Step over)', 'Passagem Guarda X (Passagem por cima do quadril)', 'Passagem Meia Profunda (Esgrima de quadril)', 'Passagem Meia Profunda (Peso morto)', 'Passagem One Leg X (Esgrima e quadril baixo)', 'Passagem One Leg X (Estouro e step back)', 'Leg Drag técnica', 'Smash Pass (Pressão)', 'Over-Under profunda', 'X-Pass (Em pé)', 'Passagem Guarda Aranha (Estouro de pegada)', 'Passagem circundando as pernas', 'Passagem Emborrachando profunda', 'Passagem cruzando o braço (Gift Wrap)'],
    'guarda fechada por cima' => ['Estrangulamento na guarda', 'Postura de guarda com a mão na manga'],
    'guarda fechada por baixo' => ['Raspagem tesourada', 'Raspagem com mão na faixa', 'Kimura', 'Raspagem com pêndulo', 'Chave de braço na guarda', 'Chave de braço na guarda em pé', 'Raspagem Omoplata (Capotagem clássica)', 'Raspagem Omoplata (Variação gola cruzada)', 'Raspagem Omoplata (Saindo para o Single Leg)', 'Finalização de Omoplata (Ajuste braço na cintura)', 'Triângulo invertido (Guarda)'],
    'guarda aberta' => ['Raspagem com mãos nos calcanhares', 'Raspagem pé na virilha com gancho', 'Balão', 'Guarda aberta / Triângulo', 'Raspagem de tripé', 'Raspagem de gancho', 'Raspagem DLR (Chutar o joelho)', 'Raspagem DLR (Gancho Invertido)', 'Raspagem DLR (Indo para as costas)', 'Raspagem Guarda X (Desequilíbrio para trás)', 'Raspagem Guarda X (Levantada técnica)', 'Raspagem Guarda X (Chutando o quadril)', 'Raspagem One Leg X (Ataque no calcanhar)', 'Raspagem One Leg X (Tesoura técnica)', 'Omoplata (Guarda aberta)', 'Transição DLR para X-Guard', 'Raspagem de X-Guard (Variação tesoura)', 'Raspagem Balão (Tomoe-Nage gancho)', 'Raspagem Helicóptero técnica', 'Ataque de Omoplata da guarda aberta'],
    '100 kilos ataque (por cima)' => ['Kimura partindo do 100kg', 'Americana', 'Chave de braço girando', 'Arm-lock invertido', 'Joelho na barriga (+ estrangulamento/arm-lock)', 'Estrangulamento com braço enrolado', 'Arm-lock com braço enrolado', 'Kata-gatame (Triângulo de mão)', 'Estrangulamento Norte-Sul', 'Arm-lock Joelho na Barriga', 'Ezequiel técnica (100kg)'],
    '100 kilos defesa (por baixo)' => ['Reposição de guarda', 'Virada de 4', 'Saída de 100kg (Indo para os 4 apoios)', 'Saída de 100kg (Reposição perna de fora)', 'Saída de 100kg para 4 apoios (Antecipação)', 'Reposição de meia guarda profunda', 'Saída de 100kg (Esgrima e quadril)'],
    'meia guarda por cima' => ['Meia guarda para montada', 'Raspagem Meia Profunda (Fura-bolo)', 'Raspagem Meia Profunda (Waiter Sweep)', 'Raspagem Meia Profunda (Saída para as costas)', 'Raspagem Meia Guarda (Gancho lateral)', 'Raspagem Meia Guarda (Pêndulo profundo)', 'Kimura da meia guarda'],
    'costas defesa' => ['Defesa de pegada de costas (girando)', 'Saída de costas (Giro para a guarda)', 'Saída de costas (Deslizar quadril e ponte)', 'Defesa de Mata-leão (Mão na gola técnica)', 'Defesa de ganchos das costas'],
    '4 apoios ataque (por cima)' => ['Estrangulamento relógio', 'Ataque de 4 apoios (Ida para as costas)', 'Ataque de 4 apoios (Triângulo de mão)', 'Ataque de 4 apoios (Transição para montada)', 'Estrangulamento de gola (4 apoios)'],
    '4 apoios por defesa (por baixo)' => ['Reposição de guarda partindo de 4 apoios', 'Rolo de 4 apoios', 'Rolo de 4 apoios para reposição de guarda', 'Saída de 4 apoios (Levantada técnica)', 'Reposição de guarda de 4 apoios (Rolamento)', 'Fuga de quadril dos 4 apoios (Sit-out)'],
    'defesa de finalizações' => ['Guilhotina', 'Defesa de guilhotina', 'Defesa de Omoplata (Rolamento antecipado)', 'Defesa de Triângulo (Postura e amasso)', 'Defesa de Arm-lock (Saída Hitchhiker)', 'Defesa de Kimura (Contra-ataque)', 'Contra-ataque de triângulo na passagem']
];

$posicoes_lista = [
    'Fundamentos',
    'Defesa Pessoal',
    'Quedas',
    'passagem de guarda',
    'guarda fechada por baixo',
    'guarda fechada por cima',
    'guarda aberta',
    'montada ataque (por cima)',
    'montada defesa (por baixo)',
    '100 kilos ataque (por cima)',
    '100 kilos defesa (por baixo)',
    'meia guarda por cima',
    'costas ataque',
    'costas defesa',
    '4 apoios ataque (por cima)',
    '4 apoios por defesa (por baixo)',
    'defesa de finalizações'
];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Lançar Técnica - Nova Arte</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        body {
            background: #f4f6f9;
            font-size: 0.9rem;
        }

        .card {
            border-radius: 15px;
        }

        .grid-select {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
            margin-bottom: 15px;
        }

        .opt-card input {
            display: none;
        }

        .opt-card label {
            border: 1px solid #ccc;
            padding: 6px;
            border-radius: 5px;
            display: block;
            text-align: center;
            cursor: pointer;
            font-size: 0.75rem;
            background: #fff;
            transition: 0.2s;
        }

        .opt-card input:checked+label {
            background: #007bff;
            color: #fff;
            border-color: #007bff;
            font-weight: bold;
        }

        .section-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .status-badge {
            border-radius: 20px;
            padding: 8px 5px;
            cursor: pointer;
            border: 2px solid #ddd;
            display: block;
            text-align: center;
            transition: 0.3s;
            font-size: 0.8rem;
        }

        #s1:checked+label {
            background: #ffc107;
            color: #000;
            border-color: #ffc107;
            font-weight: bold;
        }

        #s2:checked+label {
            background: #17a2b8;
            color: #fff;
            border-color: #17a2b8;
            font-weight: bold;
        }

        #s3:checked+label {
            background: #28a745;
            color: #fff;
            border-color: #28a745;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-dark text-white text-center p-3">
                        <h5 class="m-0 font-weight-bold">LANÇAR SELO INDIVIDUAL</h5>
                        <small>Aluno: <?php echo $nome_aluno ?></small>
                    </div>
                    <div class="card-body p-4">
                        <form action="salvar-tecnica.php" method="POST">
                            <input type="hidden" name="id_aluno" value="<?php echo $id_aluno ?>">

                            <div class="section-title">1. Escolha a Técnica</div>

                            <div class="input-group mb-3 shadow-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-right-0"><i
                                            class="fas fa-search text-muted"></i></span>
                                </div>
                                <input type="text" id="busca_tecnica" class="form-control border-left-0"
                                    placeholder="Digite para filtrar 50 lições..." onkeyup="filtrarTecnicas()">
                            </div>

                            <div class="grid-select" id="lista_tecnicas"
                                style="max-height: 300px; overflow-y: auto; padding: 5px; border: 1px solid #eee; border-radius: 10px;">
                                <?php foreach ($mapa_tecnicas as $pos_ref => $tecs): ?>
                                    <?php foreach ($tecs as $t): ?>
                                        <div class="opt-card item-tecnica">
                                            <input type="radio" name="tecnica" id="t_<?php echo md5($t) ?>"
                                                value="<?php echo $t ?>" data-posicao="<?php echo $pos_ref ?>"
                                                onclick="selecionarPosicao(this)">
                                            <label for="t_<?php echo md5($t) ?>"><?php echo $t ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>

                            <div class="form-group mt-3 mb-3">
                                <label class="font-weight-bold text-muted">Ou escreva manualmente:</label>
                                <input type="text" name="tecnica_manual" id="tecnica_manual" class="form-control"
                                    placeholder="Nome da técnica..." oninput="limparRadiosTecnica()">
                            </div>

                            <div class="section-title">2. Posição / Categoria</div>
                            <div class="grid-select" id="container_posicoes">
                                <?php foreach ($posicoes_lista as $idx => $p): ?>
                                    <div class="opt-card">
                                        <input type="radio" name="posicao" id="p_<?php echo $idx ?>"
                                            value="<?php echo $p ?>" required>
                                        <label for="p_<?php echo $idx ?>"><?php echo $p ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <hr>

                            <div class="section-title">3. Status do Conhecimento</div>
                            <div class="row no-gutters mb-4">
                                <div class="col-4 px-1">
                                    <input type="radio" id="s1" name="status" value="Em Estudo" class="d-none" checked>
                                    <label class="status-badge" for="s1">🟡 Estudo</label>
                                </div>
                                <div class="col-4 px-1">
                                    <input type="radio" id="s2" name="status" value="Revisão" class="d-none">
                                    <label class="status-badge" for="s2">🔵 Revisão</label>
                                </div>
                                <div class="col-4 px-1">
                                    <input type="radio" id="s3" name="status" value="Concluído" class="d-none">
                                    <label class="status-badge" for="s3">🟢 Concluído</label>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="font-weight-bold d-flex justify-content-between">
                                    Nível de Domínio <span id="val_range" class="badge badge-primary">50%</span>
                                </label>
                                <input type="range" name="nivel" class="custom-range" min="0" max="100" value="50"
                                    oninput="document.getElementById('val_range').innerHTML = this.value + '%'">
                            </div>

                            <button type="submit" class="btn btn-primary btn-block py-3 font-weight-bold shadow">GRAVAR
                                SELO</button>
                            <a href="usuarios.php" class="btn btn-light btn-block border mt-2 text-muted">Voltar</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function filtrarTecnicas() {
            let input = document.getElementById('busca_tecnica').value.toLowerCase();
            let itens = document.getElementsByClassName('item-tecnica');
            for (let i = 0; i < itens.length; i++) {
                let label = itens[i].getElementsByTagName('label')[0].innerText.toLowerCase();
                itens[i].style.display = label.includes(input) ? "" : "none";
            }
        }

        function selecionarPosicao(el) {
            document.getElementById('tecnica_manual').value = "";
            let posicaoAlvo = el.getAttribute('data-posicao');
            let radiosPosicao = document.getElementsByName('posicao');
            for (let r of radiosPosicao) {
                if (r.value === posicaoAlvo) {
                    r.checked = true;
                    break;
                }
            }
        }

        function limparRadiosTecnica() {
            let radiosTec = document.getElementsByName('tecnica');
            for (let r of radiosTec) {
                r.checked = false;
            }
        }
    </script>
</body>

</html>