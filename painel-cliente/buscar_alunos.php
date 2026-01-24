<?php
require_once("../conexao.php");
@session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$busca = '%' . ($_POST['query'] ?? '') . '%';

// Ajuste para usar a coluna correta de nível que vimos no seu DESCRIBE
// Filtramos para buscar apenas Clientes ou Atletas, evitando que apareçam Administradores na lista de parceiros
$query = $pdo->prepare("SELECT id, nome FROM usuarios 
                        WHERE nome LIKE :busca 
                        AND id != :id 
                        AND (nivel_usuario = 'Cliente' OR nivel_usuario = 'Atleta') 
                        ORDER BY nome ASC 
                        LIMIT 5");

$query->bindValue(":busca", $busca);
$query->bindValue(":id", $id_usuario);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($res) > 0) {
    foreach ($res as $row) {
        // Retornamos um link formatado para ser capturado pelo jQuery do arquivo anterior
        echo "<a href='#' class='item-aluno' 
                 data-id='{$row['id']}' 
                 data-nome='" . htmlspecialchars($row['nome']) . "'>
                 <i class='fas fa-user-circle mr-2 text-muted'></i>" . htmlspecialchars($row['nome']) . "
              </a>";
    }
} else {
    echo "<div class='p-2 text-muted small'><i class='fas fa-exclamation-circle mr-1'></i> Nenhum aluno encontrado</div>";
}
