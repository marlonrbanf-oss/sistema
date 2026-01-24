<?php
@session_start();
require_once("../conexao.php");

$id_aluno = @$_SESSION['id_usuario'];
$id_professor = @$_POST['id_professor'];
$data_aula = @$_POST['data_aula'];
$horario_aula = @$_POST['horario_aula'];

// 1. Validação de dados básicos
if ($id_aluno == "" || $id_professor == "" || $data_aula == "" || $horario_aula == "") {
    echo "Preencha todos os campos obrigatórios.";
    exit();
}

// 2. Validação de data (impedir datas passadas)
if (strtotime($data_aula) < strtotime(date('Y-m-d'))) {
    echo "Não é possível agendar aulas para datas passadas.";
    exit();
}

// 3. Inserção no banco de dados
$res = $pdo->prepare("INSERT INTO pedidos_aulas (id_aluno, id_professor, data_aula, horario_aula, status, data_pedido) 
                      VALUES (:aluno, :prof, :data, :hora, 'Pendente', curDate())");

$res->bindValue(":aluno", $id_aluno);
$res->bindValue(":prof", $id_professor);
$res->bindValue(":data", $data_aula);
$res->bindValue(":hora", $horario_aula);

if ($res->execute()) {
    echo "Sucesso";
} else {
    echo "Erro ao salvar no banco de dados.";
}
