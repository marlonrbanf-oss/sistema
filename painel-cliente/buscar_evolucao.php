<?php
@session_start();
require_once("../conexao.php");
require_once("funcoes-graduacao.php");

$nome = '%' . ($_POST['nome'] ?? '') . '%';

if ($nome == '%%') {
    exit();
}

// 1. Busca usuários (Clientes)
$query = $pdo->prepare("SELECT id, nome, foto FROM usuarios WHERE nome LIKE :nome AND nivel = 'Cliente' ORDER BY nome ASC LIMIT 5");
$query->bindValue(":nome", $nome);
$query->execute();
$usuarios = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($usuarios) > 0) {
    echo '<div class="card card-dark shadow-sm animate__animated animate__fadeIn">';
    echo '<div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title" style="font-size:1rem">Colegas Encontrados</h3>
            <button type="button" class="btn btn-tool" onclick="$(\'#resultado_pesquisa\').fadeOut()"><i class="fas fa-times"></i></button>
          </div>';
    echo '<div class="card-body p-3 bg-light">';

    foreach ($usuarios as $user) {
        // Busca a graduação do colega com as regras de aulas (350, 600, etc)
        $status = verificarRequisitos($pdo, $user['id']);
        
        $foto = (!empty($user['foto'])) ? $user['foto'] : 'usuario-icone-claro.png';
        $porcentagem = ($status['meta'] > 0) ? ($status['aulas'] / $status['meta']) * 100 : 0;
        if ($porcentagem > 100) $porcentagem = 100;

        // Cores para as etiquetas
        $cor_f = mb_strtolower($status['faixa']);
        $label_bg = ($cor_f == 'preta') ? '#000' : (($cor_f == 'branca') ? '#fff' : '#007bff');
        $label_txt = ($cor_f == 'branca') ? '#000' : '#fff';

        ?>
<div class="p-3 mb-3 bg-white border rounded shadow-sm">
    <div class="row align-items-center">
        <div class="col-auto">
            <img src="../images/perfil/<?php echo $foto ?>" class="img-circle border" width="55" height="55"
                style="object-fit:cover;">
        </div>
        <div class="col">
            <div class="font-weight-bold text-dark text-uppercase"><?php echo $user['nome'] ?></div>
            <span class="badge"
                style="background-color:<?php echo $label_bg ?>; color:<?php echo $label_txt ?>; border: 1px solid #ccc;">
                FAIXA <?php echo strtoupper($status['faixa']) ?> - <?php echo $status['grau'] ?>º GRAU
            </span>
        </div>
        <div class="col-12 col-md-4 mt-2 mt-md-0">
            <div class="d-flex justify-content-between mb-1">
                <small class="text-muted">Evolução para o próximo grau</small>
                <small class="font-weight-bold"><?php echo round($porcentagem) ?>%</small>
            </div>
            <div class="progress progress-xxs shadow-sm" style="height:10px;">
                <div class="progress-bar bg-success" style="width: <?php echo $porcentagem ?>%"></div>
            </div>
        </div>
    </div>

    <hr class="my-2">
    <div class="row">
        <div class="col-12">
            <small class="text-muted d-block mb-1 font-weight-bold"><i class="fas fa-medal text-warning"></i> SELOS
                CONQUISTADOS:</small>
            <div class="d-flex flex-wrap" style="gap: 5px;">
                <?php
                        $query_selos = $pdo->prepare("SELECT tecnica FROM habilidades WHERE usuario_id = :id AND status = 'Dominado'");
                        $query_selos->bindValue(":id", $user['id']);
                        $query_selos->execute();
                        $selos = $query_selos->fetchAll(PDO::FETCH_ASSOC);

                        if (count($selos) > 0) {
                            foreach ($selos as $selo) {
                                echo '<span class="badge badge-success text-uppercase" style="font-size: 10px;"><i class="fas fa-check"></i> ' . $selo['tecnica'] . '</span>';
                            }
                        } else {
                            echo '<small class="text-muted italic">Nenhum selo conquistado ainda.</small>';
                        }
                        ?>
            </div>
        </div>
    </div>
</div>
<?php
    }
    echo '</div></div>';
} else {
    echo '<div class="alert alert-light border shadow-sm">Nenhum colega encontrado com esse nome.</div>';
}
?>