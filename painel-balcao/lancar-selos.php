<?php
@session_start();
require_once("../conexao.php");

$licao_id = isset($_POST['licao_id']) ? $_POST['licao_id'] : null;
$alunos = isset($_POST['alunos']) ? $_POST['alunos'] : [];
$id_prof = @$_SESSION['id_usuario'];

// Mapeamento Completo com as Posições/Categorias para o Banco de Dados
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

if (!empty($alunos) && isset($licoes_array[$licao_id])) {
    $tecnicas_da_aula = $licoes_array[$licao_id];

    foreach ($alunos as $aluno_id) {
        foreach ($tecnicas_da_aula as $item) {
            $tec = $item['t'];
            $pos = $item['p'];

            // Verifica se o aluno já possui essa técnica registrada
            $check = $pdo->prepare("SELECT id FROM habilidades WHERE usuario_id = :u AND tecnica = :t");
            $check->execute([':u' => $aluno_id, ':t' => $tec]);

            if ($check->rowCount() == 0) {
                // Insere com Nível 25% e a Posição correspondente
                $ins = $pdo->prepare("INSERT INTO habilidades (usuario_id, tecnica, posicao, nivel, status) VALUES (:u, :t, :p, '25', 'Em Estudo')");
                $ins->execute([
                    ':u' => $aluno_id,
                    ':t' => $tec,
                    ':p' => $pos
                ]);
            }
        }
    }
    echo "<script>alert('Selos de estudo atribuídos com sucesso para a Lição $licao_id!'); window.location='licoes.php';</script>";
} else {
    echo "<script>alert('Erro: Certifique-se de selecionar a lição e os alunos.'); window.history.back();</script>";
}
