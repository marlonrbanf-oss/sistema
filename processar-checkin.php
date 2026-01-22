<?php
require_once("conexao.php");
@session_start();
date_default_timezone_set('America/Sao_Paulo');

$data_hoje = date('Y-m-d');
$id_aluno = null;

// Verifica se veio por Senha ou por Reconhecimento (ID enviado pelo JS)
if (isset($_POST['senha'])) {
  $senha = $_POST['senha'];
  $query = $pdo->prepare("SELECT id, nome FROM usuarios WHERE senha = :senha AND nivel = 'Cliente' LIMIT 1");
  $query->bindValue(":senha", $senha);
  $query->execute();
  $res = $query->fetch(PDO::FETCH_ASSOC);
  if ($res) {
    $id_aluno = $res['id'];
    $nome = $res['nome'];
  }
} elseif (isset($_POST['id_facial'])) {
  $id_aluno = $_POST['id_facial'];
  $query = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id AND nivel = 'Cliente' LIMIT 1");
  $query->bindValue(":id", $id_aluno);
  $query->execute();
  $res = $query->fetch(PDO::FETCH_ASSOC);
  $nome = $res['nome'];
}

if ($id_aluno) {
  // 2. REGRA DE FREQUÊNCIA: Bloquear check-in duplo
  $check_duplo = $pdo->prepare("SELECT id FROM logs WHERE acao LIKE :acao AND data >= :data_inicio");
  $check_duplo->bindValue(":acao", "Auto-Checkin realizado pelo aluno: " . $nome . "%");
  $check_duplo->bindValue(":data_inicio", $data_hoje . " 00:00:00");
  $check_duplo->execute();

  if ($check_duplo->rowCount() > 0) {
    echo "<div class='alert alert-info text-dark shadow-sm'>
                <i class='fas fa-info-circle fa-2x mb-2'></i><br>
                <b>OLÁ, " . mb_strtoupper($nome) . "!</b><br>
                Sua presença já foi registrada hoje.
              </div>";
    exit();
  }

  // 3. REGISTRO DE PRESENÇA
  $res_presenca = $pdo->prepare("UPDATE graduacoes SET total_aulas = total_aulas + 1 WHERE usuario_id = :id");
  $res_presenca->bindValue(":id", $id_aluno);
  $res_presenca->execute();

  // 4. LOG
  $acao = "Auto-Checkin realizado pelo aluno: " . $nome;
  $res_log = $pdo->prepare("INSERT INTO logs (usuario, acao, data) VALUES ('Sistema', :acao, NOW())");
  $res_log->bindValue(":acao", $acao);
  $res_log->execute();

  echo "<div class='alert alert-success shadow-lg border-0'>
            <i class='fas fa-check-circle fa-3x mb-2'></i><br>
            <h4 class='alert-heading'>OSS, " . mb_strtoupper($nome) . "!</h4>
            <p>Presença confirmada!</p>
          </div>";
} else {
  echo "<div class='alert alert-danger text-white border-0 shadow-sm'>
            <i class='fas fa-times-circle fa-2x mb-2'></i><br>
            <b>ERRO!</b><br>
            Dados não reconhecidos. Tente a senha manual.
          </div>";
}
