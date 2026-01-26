<?php
require_once("../conexao.php");
@session_start();

// --- FUNÇÃO PARA DOWNLOAD DO MODELO ---
if (isset($_GET['download_modelo'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=modelo_alunos.csv');
    $output = fopen('php://output', 'w');
    // Adiciona as duas linhas de cabeçalho que o seu sistema exige
    fputcsv($output, ['A (0)', 'B (1)', 'C (2)', 'D (3)', 'E (4)', 'F (5)', 'G (6)', 'H (7)', 'I (8)', 'J (9)'], ';');
    fputcsv($output, ['Nome', 'Email', 'CPF', 'Senha', 'Nível', 'Telefone', 'Vencimento', 'Faixa', 'Graus', 'Aulas'], ';');
    // Linha de exemplo
    fputcsv($output, ['Fulano de Tal', 'fulano@email.com', '00000000000', '123', 'aluno', '48999999999', '2026-02-25', 'branca', '0', '0'], ';');
    fclose($output);
    exit();
}

// --- INTERFACE VISUAL (MANUAL) ---
if (!isset($_FILES['arquivo_excel']) || $_FILES['arquivo_excel']['name'] == "") {
?>
    <!DOCTYPE html>
    <html lang="pt-br">

    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../css/bootstrap.css">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
        <title>Manual de Importação - Nova Arte</title>
        <style>
            .passo {
                background: #fff;
                border-left: 4px solid #ffc107;
                margin-bottom: 15px;
                padding: 15px;
                border-radius: 5px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .badge-numero {
                background: #343a40;
                color: #fff;
                border-radius: 50%;
                padding: 5px 10px;
                margin-right: 10px;
            }
        </style>
    </head>

    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card shadow">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <h5 class="m-0"><i class="fas fa-file-import text-warning mr-2"></i> ORIENTAÇÕES PARA IMPORTAÇÃO
                            </h5>
                            <a href="?download_modelo=1" class="btn btn-warning btn-sm font-weight-bold">
                                <i class="fas fa-download mr-1"></i> BAIXAR MODELO CSV
                            </a>
                        </div>
                        <div class="card-body">

                            <div class="passo">
                                <h6><span class="badge-numero">1</span> Use o Modelo Padrão</h6>
                                <p class="small text-muted">Baixe o modelo no botão acima. O sistema espera exatamente 10
                                    colunas (A até J). <b>Não apague as duas primeiras linhas</b> (a de índices e a de
                                    títulos).</p>
                            </div>

                            <div class="passo">
                                <h6><span class="badge-numero">2</span> Formato das Colunas</h6>
                                <ul class="small text-muted">
                                    <li><b>Email (Coluna B):</b> Obrigatório e único. Se o email já existir, o aluno não
                                        será duplicado.</li>
                                    <li><b>Vencimento (Coluna G):</b> Use o formato <b>AAAA-MM-DD</b> (Ex: 2026-01-30).</li>
                                    <li><b>Faixas (Coluna H):</b> branca, azul, roxa, marrom ou preta.</li>
                                </ul>
                            </div>

                            <div class="passo">
                                <h6><span class="badge-numero">3</span> Salvar como CSV</h6>
                                <p class="small text-muted">No Excel, escolha <b>"Salvar Como"</b> e selecione o tipo
                                    <b>"CSV (Separado por vírgulas)"</b>. Se usar o formato .xlsx padrão, a importação
                                    falhará.
                                </p>
                            </div>

                            <div class="alert alert-info d-flex align-items-center">
                                <i class="fas fa-lightbulb fa-2x mr-3"></i>
                                <span class="small">Após preparar o ficheiro, volte à página de Usuários, selecione o
                                    ficheiro CSV e clique em <b>Importar</b>.</span>
                            </div>

                            <div class="text-center mt-4">
                                <a href="index.php?pag=usuarios" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i> Voltar aos Usuários
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit();
}

// --- PROCESSAMENTO DO ARQUIVO ---
$arquivo = $_FILES['arquivo_excel']['tmp_name'];

// 1. Limpeza de Codificação para evitar Erro 3854
$conteudo = file_get_contents($arquivo);
$conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');

$handle = fopen('php://memory', 'r+');
fwrite($handle, $conteudo);
rewind($handle);

// 2. Detecção de Separador
$primeiraLinha = fgets($handle);
$delimitador = (strpos($primeiraLinha, ';') !== false) ? ';' : ',';
rewind($handle);

// 3. Pula as 2 linhas de cabeçalho
fgetcsv($handle, 1000, $delimitador);
fgetcsv($handle, 1000, $delimitador);

$cont_sucesso = 0;
$cont_erro = 0;
$ja_cadastrados = 0;

while (($dados = fgetcsv($handle, 1000, $delimitador)) !== FALSE) {
    if (!isset($dados[0]) || trim((string)$dados[0]) == "") continue;

    $nome        = trim((string)$dados[0]);
    $usuario     = trim(strtolower((string)($dados[1] ?? '')));
    $cpf         = preg_replace('/[^0-9]/', '', (string)($dados[2] ?? ''));
    $senha       = trim((string)($dados[3] ?? '123'));
    $nivel       = (!empty($dados[4])) ? ucfirst(strtolower(trim((string)$dados[4]))) : 'Cliente';
    $telefone    = preg_replace('/[^0-9]/', '', (string)($dados[5] ?? ''));
    $data_venc   = (!empty($dados[6])) ? trim((string)$dados[6]) : date('Y-m-d', strtotime('+30 days'));

    if (strtolower($nivel) == 'aluno') $nivel = 'Cliente';

    $cor_faixa   = trim((string)($dados[7] ?? 'Branca'));
    $graus       = (int)($dados[8] ?? 0);
    $total_aulas = (int)($dados[9] ?? 0);

    if ($usuario == "") continue;

    $query = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :u");
    $query->bindValue(":u", $usuario);
    $query->execute();

    if ($query->rowCount() == 0) {
        try {
            $res = $pdo->prepare("INSERT INTO usuarios SET 
                nome = :nome, usuario = :usuario, cpf = :cpf, senha = :senha, 
                nivel = :nivel, telefone = :telefone, ativo = 'Sim', 
                foto = 'sem-perfil.jpg', data_venc = :venc");

            $res->bindValue(":nome", $nome);
            $res->bindValue(":usuario", $usuario);
            $res->bindValue(":cpf", $cpf);
            $res->bindValue(":senha", $senha);
            $res->bindValue(":nivel", $nivel);
            $res->bindValue(":telefone", $telefone);
            $res->bindValue(":venc", $data_venc);
            $res->execute();

            $id_novo = $pdo->lastInsertId();

            if ($nivel == 'Cliente') {
                $res_g = $pdo->prepare("INSERT INTO graduacoes SET 
                    usuario_id = :id, cor_faixa = :cor, graus = :graus, total_aulas = :aulas");
                $res_g->bindValue(":id", $id_novo);
                $res_g->bindValue(":cor", $cor_faixa);
                $res_g->bindValue(":graus", $graus);
                $res_g->bindValue(":aulas", $total_aulas);
                $res_g->execute();
            }
            $cont_sucesso++;
        } catch (PDOException $e) {
            $cont_erro++;
        }
    } else {
        $ja_cadastrados++;
    }
}
fclose($handle);

echo "<script>
        alert('IMPORTAÇÃO CONCLUÍDA\\n✅ Sucesso: $cont_sucesso\\n⚠️ Já Existiam: $ja_cadastrados\\n❌ Erros: $cont_erro'); 
        window.location='index.php?pag=usuarios';
      </script>";
?>