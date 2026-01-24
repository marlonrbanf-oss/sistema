<?php
@session_start();
require_once("../conexao.php");
require_once("funcoes-graduacao.php");

$nome_input = $_POST['nome'] ?? '';
$nome = '%' . $nome_input . '%';

if (empty($nome_input)) {
    exit();
}

// ID do aluno logado para a comparação
$id_logado = $_SESSION['id_usuario'] ?? 0;

// --- AJUSTE DE COMPATIBILIDADE DINÂMICA (Resolução de Erros 1054) ---
// 1. Verificamos o nome da coluna de nível (nivel ou nivel_usuario)
$coluna_nivel = 'nivel';
try {
    $check_nivel = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'nivel_usuario'");
    if ($check_nivel->rowCount() > 0) {
        $coluna_nivel = 'nivel_usuario';
    }
} catch (Exception $e) {
    $coluna_nivel = 'nivel';
}

// 2. Verificamos se a coluna 'email' existe na tabela para evitar erro na query
$coluna_email = "";
try {
    $check_email = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'email'");
    if ($check_email->rowCount() > 0) {
        $coluna_email = ", email";
    }
} catch (Exception $e) {
    $coluna_email = "";
}

// 3. Busca meus dados para comparar
$meu_status = verificarRequisitos($pdo, $id_logado);
$minhas_aulas = $meu_status['aulas'] ?? 0;

// 4. Busca os dados dos colegas (Exclui o próprio usuário e resolve conflitos de colunas)
$sql = "SELECT id, nome, foto, telefone, data_cad $coluna_email 
        FROM usuarios 
        WHERE nome LIKE :nome 
        AND ($coluna_nivel = 'Cliente' OR $coluna_nivel = 'Atleta') 
        AND id != :meu_id 
        ORDER BY nome ASC LIMIT 5";

$query = $pdo->prepare($sql);
$query->bindValue(":nome", $nome);
$query->bindValue(":meu_id", $id_logado);
$query->execute();
$usuarios = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($usuarios) > 0) {
    echo '<div id="resultado_pesquisa" class="animate__animated animate__fadeIn">';

    foreach ($usuarios as $user) {
        $status = verificarRequisitos($pdo, $user['id']);
        $foto = (!empty($user['foto'])) ? $user['foto'] : 'usuario-icone-claro.png';

        // Cálculo de progresso do colega
        $aulas_colega = $status['aulas'] ?? 0;
        $meta = $status['meta'] ?? 1;
        $porcentagem = ($aulas_colega / $meta) * 100;
        if ($porcentagem > 100) $porcentagem = 100;

        // Lógica de Comparação
        $diferenca = $aulas_colega - $minhas_aulas;
        if ($diferenca > 0) {
            $comp_class = "alert-warning";
            $comp_icon = "fa-arrow-up";
            $comp_msg = "Faltam <b>$diferenca aulas</b> para você alcançar este colega!";
        } elseif ($diferenca < 0) {
            $diff_abs = abs($diferenca);
            $comp_class = "alert-success";
            $comp_icon = "fa-medal";
            $comp_msg = "Você está na frente! Tem <b>$diff_abs aulas</b> a mais que este colega.";
        } else {
            $comp_class = "alert-primary";
            $comp_icon = "fa-equals";
            $comp_msg = "Vocês estão empatados em número de aulas!";
        }

        // Cores da faixa
        $cor_f = mb_strtolower($status['faixa'] ?? 'branca');
        $label_bg = '#007bff';
        $label_txt = '#fff';
        if ($cor_f == 'preta') {
            $label_bg = '#000';
        } elseif ($cor_f == 'branca') {
            $label_bg = '#fff';
            $label_txt = '#000';
        } elseif ($cor_f == 'marrom') {
            $label_bg = '#5d3914';
        } elseif ($cor_f == 'roxa') {
            $label_bg = '#6f42c1';
        }
?>
        <div class="card card-widget widget-user shadow-lg mb-4">
            <div class="widget-user-header text-white"
                style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('../images/fundo-perfil.jpg') center center; background-size: cover; height: 120px;">
                <h3 class="widget-user-username text-right font-weight-bold" style="text-shadow: 2px 2px 4px #000;">
                    <?php echo htmlspecialchars($user['nome']) ?>
                </h3>
                <h5 class="widget-user-desc text-right" style="text-shadow: 1px 1px 3px #000;">
                    Membro desde <?php echo (isset($user['data_cad'])) ? date('M/Y', strtotime($user['data_cad'])) : '---'; ?>
                </h5>
            </div>
            <div class="widget-user-image" style="top: 70px;">
                <img class="img-circle elevation-2" src="../images/perfil/<?php echo $foto ?>"
                    style="width: 90px; height: 90px; object-fit: cover; border: 3px solid <?php echo $label_bg ?>;">
            </div>
            <div class="card-footer bg-white" style="padding-top: 50px;">

                <div class="alert <?php echo $comp_class; ?> border-0 shadow-sm mb-4 text-center py-2">
                    <small class="text-uppercase d-block font-weight-bold mb-1">Comparação de Progresso</small>
                    <i class="fas <?php echo $comp_icon; ?> mr-1"></i> <?php echo $comp_msg; ?>
                </div>

                <div class="row">
                    <div class="col-sm-4 border-right text-center">
                        <h5 class="description-header"><?php echo $aulas_colega ?></h5>
                        <span class="text-xs text-uppercase">Aulas</span>
                    </div>
                    <div class="col-sm-4 border-right text-center">
                        <span class="badge elevation-1"
                            style="background-color:<?php echo $label_bg ?>; color:<?php echo $label_txt ?>; padding: 5px 10px;">
                            <?php echo strtoupper($status['faixa'] ?? 'BRANCA') ?>
                        </span>
                        <br><small><?php echo ($status['grau'] ?? 0) ?>º GRAU</small>
                    </div>
                    <div class="col-sm-4 text-center">
                        <h5 class="description-header text-success"><?php echo round($porcentagem) ?>%</h5>
                        <span class="text-xs text-uppercase">Evolução</span>
                    </div>
                </div>

                <div class="mt-3 px-3">
                    <div class="progress progress-sm active shadow-sm" style="height: 10px; border-radius: 10px;">
                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                            style="width: <?php echo $porcentagem ?>%"></div>
                    </div>
                </div>

                <hr>
                <div class="px-2">
                    <p class="text-muted mb-2 small font-weight-bold"><i class="fas fa-award text-warning"></i> SELOS:</p>
                    <div class="d-flex flex-wrap" style="gap: 5px;">
                        <?php
                        $q_selos = $pdo->prepare("SELECT tecnica FROM habilidades WHERE usuario_id = :id AND status = 'Dominado'");
                        $q_selos->execute([':id' => $user['id']]);
                        $selos = $q_selos->fetchAll(PDO::FETCH_ASSOC);
                        if ($selos) {
                            foreach ($selos as $s) echo '<span class="badge badge-info text-uppercase" style="font-size:9px;">' . htmlspecialchars($s['tecnica']) . '</span>';
                        } else {
                            echo '<small class="text-muted italic">Nenhum selo.</small>';
                        }
                        ?>
                    </div>
                </div>

                <?php if (!empty($user['telefone'])): ?>
                    <div class="text-center mt-3">
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $user['telefone']) ?>" target="_blank"
                            class="btn btn-xs btn-outline-success rounded-pill px-3">
                            <i class="fab fa-whatsapp"></i> Conversar
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
<?php
    }
    echo '</div>';
} else {
    echo '<div class="alert alert-light border shadow-sm text-center">Nenhum colega encontrado.</div>';
}
?>