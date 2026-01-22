<?php
require_once("../../conexao.php");

$busca = "%" . $_POST['nome'] . "%";
$query = $pdo->prepare("SELECT id, nome, foto, biometria FROM usuarios WHERE nome LIKE :nome AND nivel = 'Cliente' LIMIT 10");
$query->bindValue(":nome", $busca);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($res) > 0) {
    foreach ($res as $aluno) {
        $tem_bio = !empty($aluno['biometria']);
        $cor_status = $tem_bio ? 'success' : 'danger';
        $texto_status = $tem_bio ? 'CADASTRADO' : 'NÃO POSSUI';
        $btn_texto = $tem_bio ? 'RECADASTRAR / EDITAR' : 'CADASTRAR FACIAL';
        $foto = $aluno['foto'] ?: 'usuario-icone-claro.png';

        echo "
        <div class='user-card d-flex align-items-center justify-content-between'>
            <div class='d-flex align-items-center'>
                <img src='../images/perfil/{$foto}' class='rounded-circle mr-3' style='width:50px; height:50px; object-fit:cover;'>
                <div>
                    <h6 class='mb-0 font-weight-bold'>" . mb_strtoupper($aluno['nome']) . "</h6>
                    <span class='status-badge bg-{$cor_status} text-white'>{$texto_status}</span>
                </div>
            </div>
            <div>
                <button class='btn btn-primary btn-sm' onclick=\"abrirModalBio('{$aluno['id']}', '{$aluno['nome']}')\">
                    <i class='fas fa-camera mr-1'></i> {$btn_texto}
                </button>
            </div>
        </div>";
    }
} else {
    echo "<div class='alert alert-light text-center'>Nenhum aluno encontrado.</div>";
}
