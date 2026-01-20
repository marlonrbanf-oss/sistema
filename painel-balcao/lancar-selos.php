<?php
@session_start();
require_once("../conexao.php");

$licao_id = $_POST['licao_id'];
$alunos = $_POST['alunos'];
$id_prof = $_SESSION['id_usuario'];

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

if (!empty($alunos) && isset($licoes_array[$licao_id])) {
    $tecnicas = $licoes_array[$licao_id];
    foreach ($alunos as $aluno_id) {
        foreach ($tecnicas as $tec) {
            // Verifica se já existe para não duplicar
            $check = $pdo->prepare("SELECT id FROM habilidades WHERE usuario_id = :u AND tecnica = :t");
            $check->execute([':u' => $aluno_id, ':t' => $tec]);

            if ($check->rowCount() == 0) {
                $ins = $pdo->prepare("INSERT INTO habilidades SET usuario_id = :u, tecnica = :t, nivel = '25', status = 'Em Estudo'");
                $ins->execute([':u' => $aluno_id, ':t' => $tec]);
            }
        }
    }
    echo "<script>alert('Selos atribuídos com sucesso!'); window.location='licoes.php';</script>";
} else {
    echo "<script>alert('Erro: Selecione a lição e os alunos.'); window.history.back();</script>";
}
