<?php
require_once("../../conexao.php");

// Proteção básica para garantir que o nome foi enviado
if (!isset($_POST['nome'])) {
    exit();
}

$busca = "%" . $_POST['nome'] . "%";

// Busca os alunos limitando a 10 para manter a performance
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

        // Verifica se a foto existe, se não usa o ícone padrão
        $foto = (!empty($aluno['foto']) && $aluno['foto'] != 'sem-foto.jpg') ? $aluno['foto'] : 'usuario-icone-claro.png';

        echo "
        <div class='user-card d-flex align-items-center justify-content-between animate__animated animate__fadeIn'>
            <div class='d-flex align-items-center'>
                <img src='../images/perfil/{$foto}' class='rounded-circle mr-3' style='width:50px; height:50px; object-fit:cover; border: 2px solid #dee2e6;'>
                <div>
                    <h6 class='mb-0 font-weight-bold'>" . mb_strtoupper($aluno['nome']) . "</h6>
                    <span class='status-badge bg-{$cor_status} text-white' style='font-size:0.65rem; padding: 2px 8px; border-radius:10px;'>{$texto_status}</span>
                </div>
            </div>
            <div>
                <button class='btn btn-primary btn-sm px-3 shadow-sm' onclick=\"abrirModalBio('{$aluno['id']}', '" . addslashes($aluno['nome']) . "')\">
                    <i class='fas fa-camera mr-1'></i> {$btn_texto}
                </button>
            </div>
        </div>";
    }
} else {
    echo "<div class='text-center py-4'>
            <i class='fas fa-search fa-2x mb-2 text-muted' style='opacity:0.5;'></i>
            <p class='text-muted'>Nenhum aluno encontrado.</p>
          </div>";
}
