<?php
require_once("../conexao.php");

$id_recebido = $_POST['id'];
$token_lido = $_POST['token_professor'];
$modo = isset($_POST['modo']) ? $_POST['modo'] : 'individual';

// A mesma chave que você usou no gerar-qr.php
$chave_secreta = "MINHA_NOVA_ARTE_2026";
$token_correto_hoje = md5(date('Y-m-d') . $chave_secreta);

if ($token_lido === $token_correto_hoje) {

    // Transformamos o ID em um array, mesmo que seja apenas um
    // Se vier '12,13,14', vira um array com 3 itens. Se vier '12', vira array com 1 item.
    $ids = explode(',', $id_recebido);

    foreach ($ids as $id) {
        $id = trim($id); // Limpeza por segurança
        if (!empty($id)) {
            // Valida a técnica e sobe o nível para 100%
            $res = $pdo->prepare("UPDATE habilidades SET status = 'Dominado', nivel = 100 WHERE id = :id");
            $res->bindValue(":id", $id);
            $res->execute();
        }
    }

    echo "Sucesso";
} else {
    echo "Código Inválido ou Expirado";
}
