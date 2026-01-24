<?php
require_once("../conexao.php");
@session_start();

// Verifica se o usuário está logado para evitar acesso externo ao script
if (!isset($_SESSION['id_usuario'])) {
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$busca = '%' . (isset($_POST['query']) ? $_POST['query'] : '') . '%';

// --- LOGICA DE COMPATIBILIDADE DE COLUNA ---
// Verificamos qual coluna de nível existe na tabela para evitar o erro "Unknown column"
$coluna_nivel = 'nivel'; // Padrão
try {
    $pdo->query("SELECT nivel_usuario FROM usuarios LIMIT 1");
    $coluna_nivel = 'nivel_usuario';
} catch (Exception $e) {
    // Mantém 'nivel' se 'nivel_usuario' falhar
}

// Buscamos apenas outros usuários (id != logado) que sejam Clientes ou Atletas
$sql = "SELECT id, nome FROM usuarios 
        WHERE nome LIKE :busca 
        AND id != :id 
        AND ($coluna_nivel = 'Cliente' OR $coluna_nivel = 'Atleta') 
        ORDER BY nome ASC 
        LIMIT 6";

$query = $pdo->prepare($sql);
$query->bindValue(":busca", $busca);
$query->bindValue(":id", $id_usuario);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);

// Renderização dos resultados para o AJAX do front-end
if (count($res) > 0) {
    foreach ($res as $row) {
        $nome_formatado = htmlspecialchars($row['nome']);
        echo "<a href='javascript:void(0)' class='item-aluno' 
                 data-id='{$row['id']}' 
                 data-nome='{$nome_formatado}'>
                 <div class='d-flex align-items-center'>
                    <i class='fas fa-user-friends mr-2 text-primary' style='opacity: 0.6;'></i>
                    <span>{$nome_formatado}</span>
                 </div>
              </a>";
    }
} else {
    echo "<div class='p-2 text-muted small'>
            <i class='fas fa-search mr-1'></i> Nenhum parceiro encontrado
          </div>";
}
