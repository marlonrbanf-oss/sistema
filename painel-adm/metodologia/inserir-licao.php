<?php
require_once("../../conexao.php");
@session_start();

// 1. VALIDAÇÃO DE SEGURANÇA
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "Acesso negado.";
    exit();
}

// 2. CAPTURA E HIGIENIZAÇÃO (Evita XSS e espaços desnecessários)
$titulo    = isset($_GET['titulo']) ? trim($_GET['titulo']) : "";
$numero    = isset($_GET['numero']) ? (int)$_GET['numero'] : 0;
$categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : "Licao";
$id        = isset($_GET['id']) ? $_GET['id'] : "";

// 3. VALIDAÇÃO DE CAMPOS OBRIGATÓRIOS
if ($titulo == "" || $numero <= 0) {
    echo "<script>alert('Título e Número são obrigatórios!'); window.location='../metodologia.php';</script>";
    exit();
}

try {
    if ($id == "") {
        // 4. INSERIR NOVA LIÇÃO
        // Verificação extra: Evitar duplicidade de número na mesma categoria
        $check = $pdo->prepare("SELECT id FROM licoes WHERE numero = :num AND categoria = :cat");
        $check->execute([':num' => $numero, ':cat' => $categoria]);

        if ($check->rowCount() > 0 && $categoria == "Licao") {
            echo "<script>alert('Já existe uma lição com este número!'); window.history.back();</script>";
            exit();
        }

        $res = $pdo->prepare("INSERT INTO licoes (numero, titulo, categoria) VALUES (:numero, :titulo, :categoria)");
    } else {
        // 5. EDITAR EXISTENTE
        $res = $pdo->prepare("UPDATE licoes SET numero = :numero, titulo = :titulo, categoria = :categoria WHERE id = :id");
        $res->bindValue(":id", $id);
    }

    $res->bindValue(":numero", $numero);
    $res->bindValue(":titulo", $titulo);
    $res->bindValue(":categoria", $categoria);
    $res->execute();

    // REDIRECIONAMENTO SUCESSO
    echo "<script>window.location='../metodologia.php'</script>";
} catch (PDOException $e) {
    echo "Erro ao salvar: " . $e->getMessage();
}
