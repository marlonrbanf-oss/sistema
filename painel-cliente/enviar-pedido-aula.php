<?php
@session_start();
require_once("../conexao.php");

$id_aluno = $_SESSION['id_usuario'];
$id_professor = $_POST['id_professor'];
$data_aula = $_POST['data_aula'];
$horario_aula = $_POST['horario_aula'];

if ($id_aluno == "" || $id_professor == "") {
    echo "Erro nos dados do usuário.";
    exit();
}

$res = $pdo->prepare("INSERT INTO pedidos_aulas SET id_aluno = :aluno, id_professor = :prof, data_aula = :data, horario_aula = :hora, status = 'Pendente'");

$res->bindValue(":aluno", $id_aluno);
$res->bindValue(":prof", $id_professor);
$res->bindValue(":data", $data_aula);
$res->bindValue(":hora", $horario_aula);

if (strtotime($data_aula) < strtotime(date('Y-m-d'))) {
    echo "Não é possível agendar aulas para datas passadas.";
    exit();
}
$res->execute();

echo "Sucesso";
