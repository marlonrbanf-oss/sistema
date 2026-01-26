<?php
require_once("../conexao.php");
@session_start();

// Configurações iniciais
$delimitador = ',';
$cerca = '"';

if (isset($_FILES['arquivo_excel']) && $_FILES['arquivo_excel']['name'] != "") {
    $arquivo = $_FILES['arquivo_excel']['tmp_name'];
    $handle = fopen($arquivo, 'r');

    // 1. Detecta o separador (vírgula ou ponto-e-vírgula)
    $primeiraLinha = fgets($handle);
    $delimitador = (strpos($primeiraLinha, ';') !== false) ? ';' : ',';
    rewind($handle);

    // 2. Pula o cabeçalho
    fgetcsv($handle, 1000, $delimitador, $cerca);

    $cont_sucesso = 0;
    $cont_erro = 0;
    $ja_cadastrados = 0;

    // 3. Loop pelas linhas
    while (($dados = fgetcsv($handle, 1000, $delimitador, $cerca)) !== FALSE) {

        // Verifica se a linha tem o mínimo de colunas necessárias ou se está vazia
        if (!isset($dados[0]) || trim($dados[0]) == "") continue;

        // Função para converter acentuação de forma moderna (substitui utf8_encode)
        $converter = function ($texto) {
            if ($texto === null) return "";
            // Converte de Windows-1252 (Excel) para UTF-8 se necessário
            return mb_convert_encoding(trim($texto), 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
        };

        // Mapeamento Seguro (Uso de null coalescing ?? para evitar "Undefined array key")
        $nome        = $converter($dados[0] ?? '');
        $usuario     = trim($dados[1] ?? ''); // E-mail
        $cpf         = preg_replace('/[^0-9]/', '', (string)($dados[2] ?? ''));
        $senha       = trim((string)($dados[3] ?? ''));
        $nivel       = (!empty($dados[4])) ? ucfirst(strtolower(trim($dados[4]))) : 'Cliente';
        $telefone    = preg_replace('/[^0-9]/', '', (string)($dados[5] ?? ''));
        $data_venc   = (!empty($dados[6])) ? trim($dados[6]) : date('Y-m-d', strtotime('+30 days'));

        if ($nivel == 'Aluno') $nivel = 'Cliente';

        // Dados de Graduação (Colunas H, I, J)
        $cor_faixa   = $converter($dados[7] ?? 'Branca');
        $graus       = (int)($dados[8] ?? 0);
        $total_aulas = (int)($dados[9] ?? 0);

        if ($usuario == "") continue;

        // Verificar se o Usuário já existe
        $query = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :u");
        $query->bindValue(":u", $usuario);
        $query->execute();

        if ($query->rowCount() == 0) {
            try {
                $res = $pdo->prepare("INSERT INTO usuarios SET 
                    nome = :nome, 
                    usuario = :usuario, 
                    cpf = :cpf, 
                    senha = :senha, 
                    nivel = :nivel, 
                    telefone = :telefone, 
                    ativo = 'Sim', 
                    foto = 'sem-perfil.jpg',
                    data_venc = :venc");

                $res->bindValue(":nome", $nome);
                $res->bindValue(":usuario", $usuario);
                $res->bindValue(":cpf", $cpf);
                $res->bindValue(":senha", $senha);
                $res->bindValue(":nivel", $nivel);
                $res->bindValue(":telefone", $telefone);
                $res->bindValue(":venc", $data_venc);
                $res->execute();

                $id_novo_usuario = $pdo->lastInsertId();

                if ($nivel == 'Cliente') {
                    $res_g = $pdo->prepare("INSERT INTO graduacoes SET 
                        usuario_id = :id, 
                        cor_faixa = :cor, 
                        graus = :graus, 
                        total_aulas = :aulas");

                    $res_g->bindValue(":id", $id_novo_usuario);
                    $res_g->bindValue(":cor", $cor_faixa);
                    $res_g->bindValue(":graus", $graus);
                    $res_g->bindValue(":aulas", $total_aulas);
                    $res_g->execute();
                }
                $cont_sucesso++;
            } catch (Exception $e) {
                $cont_erro++;
            }
        } else {
            $ja_cadastrados++;
        }
    }
    fclose($handle);

    echo "<script>
            alert('Importação Concluída!\\nSucesso: $cont_sucesso\\nExistentes: $ja_cadastrados\\nErros: $cont_erro'); 
            window.location='index.php?pag=usuarios';
          </script>";
} else {
    echo "<script>alert('Arquivo inválido!'); window.location='index.php?pag=usuarios';</script>";
}
