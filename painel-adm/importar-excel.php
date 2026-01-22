<?php
require_once("../conexao.php");
@session_start();

// Configurações do interpretador CSV
// Ajustado para aceitar vírgula ou ponto-e-vírgula (comum em Excels brasileiros)
$delimitador = ',';
$cerca = '"';

if (isset($_FILES['arquivo_excel']) && $_FILES['arquivo_excel']['name'] != "") {

    $arquivo = $_FILES['arquivo_excel']['tmp_name'];

    // Abre o arquivo para leitura
    $handle = fopen($arquivo, 'r');

    // Detecta se o separador é vírgula ou ponto-e-vírgula na primeira linha
    $primeiraLinha = fgets($handle);
    if (strpos($primeiraLinha, ';') !== false) {
        $delimitador = ';';
    }
    rewind($handle); // Volta para o início do arquivo

    // Pula a linha do cabeçalho
    fgetcsv($handle, 1000, $delimitador, $cerca);

    $cont_sucesso = 0;
    $cont_erro = 0;
    $ja_cadastrados = 0;

    // Loop pelas linhas do arquivo
    while (($dados = fgetcsv($handle, 1000, $delimitador, $cerca)) !== FALSE) {

        // Se a linha estiver vazia ou o nome estiver em branco, pula
        if (empty($dados[0]) || trim($dados[0]) == "") continue;

        // Mapeamento das 10 Colunas (Ordem da Planilha)
        $nome        = trim($dados[0]);
        $usuario     = trim($dados[1]); // E-mail usado para login
        $cpf         = trim($dados[2]);
        $senha       = trim($dados[3]);
        $nivel       = (!empty($dados[4])) ? trim($dados[4]) : 'Cliente';
        $telefone    = trim($dados[5] ?? '');
        $data_venc   = (!empty($dados[6])) ? trim($dados[6]) : date('Y-m-d', strtotime('+30 days'));

        // Dados de Graduação (Colunas H, I, J)
        $cor_faixa   = mb_strtolower(trim($dados[7] ?? 'branca'));
        $graus       = (int)($dados[8] ?? 0);
        $total_aulas = (int)($dados[9] ?? 0);

        // 1. Verificar se o Usuário/E-mail já existe no banco
        $query = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :u");
        $query->bindValue(":u", $usuario);
        $query->execute();

        if ($query->rowCount() == 0) {
            try {
                // 2. Inserir na tabela USUARIOS
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

                // 3. Inserir na tabela GRADUACOES se o nível for Cliente
                if ($nivel == 'Cliente' || $nivel == 'Aluno') {
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

    // Feedback visual para o usuário
    echo "<script>
            alert('Processamento concluído!\\n\\n✅ Sucesso: $cont_sucesso\\n⚠️ Já cadastrados: $ja_cadastrados\\n❌ Erros técnicos: $cont_erro'); 
            window.location='usuarios.php';
          </script>";
} else {
    echo "<script>alert('Selecione um arquivo CSV válido!'); window.location='usuarios.php';</script>";
}
