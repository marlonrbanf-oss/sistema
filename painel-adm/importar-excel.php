<?php
require_once("../conexao.php");
@session_start();

// --- FUNÇÕES DE APOIO ---
function gerarSenha($tamanho = 6)
{
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, $tamanho);
}

function tratarDuplicado($pdo, $campo, $valor, $nome_aluno, &$relatorio)
{
    $valorOriginal = $valor;
    $novoValor = $valor;
    $i = 1;
    while (true) {
        $query = $pdo->prepare("SELECT id FROM usuarios WHERE $campo = :v");
        $query->bindValue(":v", $novoValor);
        $query->execute();
        if ($query->rowCount() == 0) {
            if ($valorOriginal !== $novoValor) {
                $relatorio[] = ['aluno' => $nome_aluno, 'tipo' => "Ajuste de " . strtoupper($campo), 'detalhe' => "O valor '$valorOriginal' já existia. Alterado para: <b>$novoValor</b>"];
            }
            return $novoValor;
        }
        $novoValor = $valor . $i;
        $i++;
    }
}

// --- PARTE 3: EXECUÇÃO FINAL APÓS CONFIRMAÇÃO ---
if (isset($_POST['confirmar_importacao'])) {
    $alunos = json_decode($_POST['dados_alunos'], true);
    $cont_sucesso = 0;

    foreach ($alunos as $aluno) {
        try {
            $pdo->beginTransaction();
            $res = $pdo->prepare("INSERT INTO usuarios SET nome = :n, usuario = :u, cpf = :c, senha = :s, nivel = :niv, telefone = :t, ativo = 'Sim', foto = 'sem-perfil.jpg', data_venc = :v, data_cad = CURDATE()");
            $res->execute([
                ":n" => $aluno['nome'],
                ":u" => $aluno['usuario'],
                ":c" => $aluno['cpf'],
                ":s" => $aluno['senha'],
                ":niv" => $aluno['nivel'],
                ":t" => $aluno['telefone'],
                ":v" => $aluno['venc']
            ]);

            $id_novo = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO graduacoes SET usuario_id = ?, cor_faixa = ?, graus = ?, total_aulas = ?")
                ->execute([$id_novo, $aluno['faixa'], $aluno['graus'], $aluno['aulas']]);

            $pdo->commit();
            $cont_sucesso++;
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
    echo "<script>alert('Sucesso! $cont_sucesso alunos foram cadastrados.'); window.location='index.php?pag=usuarios';</script>";
    exit();
}

// --- PARTE 1: INTERFACE INICIAL COM ORIENTAÇÕES ---
if (!isset($_FILES['arquivo_excel']) || $_FILES['arquivo_excel']['name'] == "") {
?>
    <!DOCTYPE html>
    <html lang="pt-br">

    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../css/bootstrap.css">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
        <title>Importar Alunos</title>
    </head>

    <body class="bg-light">
        <div class="container mt-5">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white">
                    <h5><i class="fas fa-file-csv mr-2 text-warning"></i> Importar Alunos</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning border-warning text-dark">
                        <h6><b><i class="fas fa-exclamation-triangle"></i> OBSERVAÇÃO IMPORTANTE:</b></h6>
                        <p class="mb-2">Para que o cadastro funcione perfeitamente, você precisa converter sua planilha para
                            CSV:</p>
                        <ul class="small">
                            <li>Abra sua planilha original no <b>Excel</b>.</li>
                            <li>Vá em <b>Arquivo > Salvar Como</b>.</li>
                            <li>No campo "Tipo", escolha: <b>CSV (Separado por vírgulas) (*.csv)</b> ou <b>CSV UTF-8</b>.
                            </li>
                            <li>Clique em <b>Salvar</b> e use esse arquivo abaixo.</li>
                        </ul>
                    </div>
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group"><input type="file" name="arquivo_excel" accept=".csv" required
                                class="form-control-file"></div>
                        <button type="submit" class="btn btn-primary">Verificar Relatório de Prévia</button>
                    </form>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit();
}

// --- PARTE 2: GERAÇÃO DA PRÉVIA (SEM SALVAR) ---
$extensao = pathinfo($_FILES['arquivo_excel']['name'], PATHINFO_EXTENSION);
if (strtolower($extensao) != 'csv') {
    echo "<script>alert('ERRO: Apenas arquivos .CSV são aceitos!'); window.location='index.php?pag=usuarios';</script>";
    exit();
}

$handle = fopen($_FILES['arquivo_excel']['tmp_name'], 'r');
$primeiraLinha = fgets($handle);
$delimitador = (strpos($primeiraLinha, ';') !== false) ? ';' : ',';
rewind($handle);

$alunos_para_cadastrar = [];
$relatorio_previa = [];

while (($dados = fgetcsv($handle, 1000, $delimitador)) !== FALSE) {
    if (!isset($dados[0]) || trim((string)$dados[0]) == "" || strtolower($dados[0]) == 'nome') continue;

    $nome = trim((string)$dados[0]);
    $user_temp = trim(strtolower((string)($dados[1] ?? '')));
    $cpf_temp = preg_replace('/[^0-9]/', '', (string)($dados[2] ?? ''));
    $senha_temp = trim((string)($dados[3] ?? ''));

    if ($senha_temp == "") {
        $senha_temp = gerarSenha();
        $relatorio_previa[] = ['aluno' => $nome, 'tipo' => 'Senha Gerada', 'detalhe' => "Senha automática: <b>$senha_temp</b>"];
    }

    $cpf_final = ($cpf_temp != "") ? tratarDuplicado($pdo, 'cpf', $cpf_temp, $nome, $relatorio_previa) : "";
    $user_final = tratarDuplicado($pdo, 'usuario', $user_temp, $nome, $relatorio_previa);

    $alunos_para_cadastrar[] = [
        'nome' => $nome,
        'usuario' => $user_final,
        'cpf' => $cpf_final,
        'senha' => $senha_temp,
        'nivel' => (!empty($dados[4]) && strtolower($dados[4]) != 'aluno') ? ucfirst(strtolower($dados[4])) : 'Cliente',
        'telefone' => preg_replace('/[^0-9]/', '', (string)($dados[5] ?? '')),
        'venc' => (!empty($dados[6])) ? $dados[6] : date('Y-m-d', strtotime('+30 days')),
        'faixa' => ($dados[7] ?? 'Branca'),
        'graus' => ($dados[8] ?? 0),
        'aulas' => ($dados[9] ?? 0)
    ];
}
fclose($handle);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <title>Conferir Importação</title>
</head>

<body class="bg-light">
    <div class="container mt-4">
        <div class="card shadow border-0">
            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <h5 class="m-0">Prévia da Importação (Verifique antes de confirmar)</h5>
                <button onclick="window.print();" class="btn btn-sm btn-light btn-print"><i
                        class="fas fa-print"></i></button>
            </div>
            <div class="card-body">
                <p>O sistema identificou <b><?php echo count($alunos_para_cadastrar); ?></b> alunos prontos para o
                    cadastro.</p>
                <table class="table table-sm table-hover border">
                    <thead class="thead-light">
                        <tr>
                            <th>Aluno</th>
                            <th>Ocorrência</th>
                            <th>Ajuste na Prévia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($relatorio_previa as $item): ?>
                            <tr>
                                <td><?php echo $item['aluno']; ?></td>
                                <td><span class="badge badge-warning"><?php echo $item['tipo']; ?></span></td>
                                <td><?php echo $item['detalhe']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($relatorio_previa)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">Nenhum ajuste foi necessário. Todos os dados
                                    são novos!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="alert alert-info mt-4 text-center">
                    <h5>Deseja cadastrar os alunos selecionados?</h5>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="dados_alunos"
                            value='<?php echo htmlspecialchars(json_encode($alunos_para_cadastrar), ENT_QUOTES, 'UTF-8'); ?>'>
                        <button type="submit" name="confirmar_importacao" class="btn btn-success btn-lg px-5">Sim,
                            Confirmar Cadastro</button>
                    </form>
                    <a href="index.php?pag=usuarios" class="btn btn-danger btn-lg px-5">Não, Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>