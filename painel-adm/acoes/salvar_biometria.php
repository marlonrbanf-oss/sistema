<?php
require_once("../../conexao.php");

// Recebe os dados via POST
$id = isset($_POST['id']) ? $_POST['id'] : null;
$biometria = isset($_POST['biometria']) ? $_POST['biometria'] : null;

// Verifica se os campos não estão vazios
if (!empty($id) && !empty($biometria)) {
    try {
        // Prepara a atualização do campo biometria no banco de dados
        $query = $pdo->prepare("UPDATE usuarios SET biometria = :bio WHERE id = :id");
        $query->bindValue(":bio", $biometria);
        $query->bindValue(":id", $id);
        $query->execute();

        // Retorno para o AJAX no modal-biometria.php
        echo "sucesso";
    } catch (Exception $e) {
        // Em caso de erro no banco de dados
        echo "erro ao salvar no banco";
    }
} else {
    echo "dados incompletos";
}
