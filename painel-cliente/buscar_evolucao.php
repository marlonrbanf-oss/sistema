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

// --- AJUSTE DE COMPATIBILIDADE DE COLUNA ---
$coluna_nivel = 'nivel';
try {
    $pdo->query("SELECT nivel_usuario FROM usuarios LIMIT 1");
    $coluna_nivel = 'nivel_usuario';
} catch (Exception $e) {
    $coluna_nivel = 'nivel';
}

// 1. Busca meus dados para comparar
$meu_status = verificarRequisitos($pdo, $id_logado);
$minhas_aulas = $meu_status['aulas'] ?? 0;

// 2. Busca os dados dos colegas (Clientes/Atletas) - Exclui o próprio usuário da busca
$query = $pdo->prepare("SELECT id, nome, foto, telefone, email, data_cad FROM usuarios WHERE nome LIKE :nome AND ($coluna_nivel = 'Cliente' OR $coluna_nivel = 'Atleta') AND id != :meu_id ORDER BY nome ASC LIMIT 5");
$query->bindValue(":nome", $nome);
$query->bindValue(":meu_id", $id_logado);
$query->execute();
$usuarios = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($usuarios) > 0) {
    echo '<div id="resultado_pesquisa" class="animate__animated animate__fadeIn">';

    foreach ($usuarios as $user) {
        // Busca a graduação e requisitos técnicos do colega pesquisado
        $status = verificarRequisitos($pdo, $user['id']);
        $foto = (!empty($user['foto'])) ? $user['foto'] : 'usuario-icone-claro.png';

        // Cálculo de progresso do colega
        $aulas_colega = $status['aulas'] ?? 0;
        $meta = $status['meta'] ?? 1;
        $porcentagem = ($aulas_colega / $meta) * 100;
        if ($porcentagem > 100) $porcentagem = 100;

        // Lógica de Comparação de Progresso
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

        // Definição de cores da faixa
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
        } elseif ($cor_f == 'azul') {
            $label_bg = '#007bff';
        }
?>
        <div class="card card-widget widget-user shadow-lg mb-4">
            <div class="widget-user-header text-white"
                style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('../images/fundo-perfil.jpg') center center; background-size: cover; height: 120px;">
                <h3 class="widget-user-username text-right font-weight-bold" style="text-shadow: 2px 2px 4px #000;">
                    <?php echo htmlspecialchars($user['nome']) ?>
                </h3>
                <h5 class="widget-user-desc text-right" style="text-shadow: 1px 1px 3px #000;">
                    Membro desde <?php echo date('M/Y', strtotime($user['data_cad'])) ?>
                </h5>
            </div>
            <div class="widget-user-image" style="top: 70px;">
                <img class="img-circle elevation-2" src="../images/perfil/<?php echo $foto ?>" alt="User Avatar"
                    style="width: 90px; height: 90px; object-fit: cover; border: 3px solid <?php echo $label_bg ?>;">
            </div>
            <div class="card-footer bg-white" style="padding-top: 50px;">

                <div class="alert <?php echo $comp_class; ?> border-0 shadow-sm mb-4 text-center py-2">
                    <small class="text-uppercase d-block font-weight-bold mb-1">Comparação de Progresso</small>
                    <i class="fas <?php echo $comp_icon; ?> mr-1"></i> <?php echo $comp_msg; ?>
                </div>

                <div class="row">
                    <div class="col-sm-4 border-right">
                        <div class="description-block">
                            <h5 class="description-header"><?php echo $aulas_colega ?></h5>
                            <span class="description-text text-xs text-uppercase">Aulas do Colega</span>
                        </div>
                    </div>
                    <div class="col-sm-4 border-right">
                        <div class="description-block">
                            <span class="badge elevation-1"
                                style="background-color:<?php echo $label_bg ?>; color:<?php echo $label_txt ?>; padding: 8px 12px; font-size: 0.8rem; border: 1px solid #ccc;">
                                FAIXA <?php echo strtoupper($status['faixa']) ?>
                            </span>
                            <br><small class="text-muted font-weight-bold"><?php echo ($status['grau']) ?>º GRAU</small>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="description-block">
                            <h5 class="description-header text-success"><?php echo round($porcentagem) ?>%</h5>
                            <span class="description-text text-xs text-uppercase">Evolução do Colega</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 px-3">
                    <div class="progress progress-sm active shadow-sm" style="border-radius: 10px;">
                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar"
                            style="width: <?php echo $porcentagem ?>%">
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row px-2">
                    <div class="col-12">
                        <p class="text-muted mb-2"><strong><i class="fas fa-award text-warning mr-1"></i> Selos de
                                Especialidade:</strong></p>
                        <div class="d-flex flex-wrap" style="gap: 5px;">
                            <?php
                            $query_selos = $pdo->prepare("SELECT tecnica FROM habilidades WHERE usuario_id = :id AND status = 'Dominado'");
                            $query_selos->bindValue(":id", $user['id']);
                            $query_selos->execute();
                            $selos = $query_selos->fetchAll(PDO::FETCH_ASSOC);

                            if (count($selos) > 0) {
                                foreach ($selos as $selo) {
                                    echo '<span class="badge badge-info shadow-sm text-uppercase" style="font-size: 11px; padding: 5px 10px;"><i class="fas fa-check-circle mr-1"></i> ' . htmlspecialchars($selo['tecnica']) . '</span>';
                                }
                            } else {
                                echo '<span class="text-muted small italic ml-1">Nenhum selo técnico registrado.</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($user['telefone'])): ?>
                    <div class="text-center mt-3">
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $user['telefone']) ?>" target="_blank"
                            class="btn btn-xs btn-outline-success rounded-pill px-3">
                            <i class="fab fa-whatsapp mr-1"></i> Enviar Mensagem
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
<?php
    }
    echo '</div>';
} else {
    echo '<div class="alert alert-light border shadow-sm text-center">Nenhum colega encontrado. Tente um nome diferente.</div>';
}
?>