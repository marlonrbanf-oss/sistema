<?php
require_once("../conexao.php");

// Define o delimitador do CSV (geralmente vírgula ou ponto e vírgula)
$delimitador = ',';
$cerca = '"';

if (isset($_FILES['arquivo_excel']) && $_FILES['arquivo_excel']['name'] != "") {

    $arquivo = $_FILES['arquivo_excel']['tmp_name'];
    $handle = fopen($arquivo, 'r');

    // Pula a primeira linha (cabeçalho)
    fgetcsv($handle, 1000, $delimitador, $cerca);

    $cont_sucesso = 0;
    $cont_erro = 0;

    while (($dados = fgetcsv($handle, 1000, $delimitador, $cerca)) !== FALSE) {
        if (empty($dados[0])) continue; // Pula linha vazia

        $nome = $dados[0];
        $email = $dados[1]; // Vai para a coluna 'usuario' no banco
        $cpf = $dados[2];
        $senha = $dados[3];
        $nivel = $dados[4] ?? 'Cliente';
        $telefone = $dados[5] ?? '';
        $data_venc = $dados[6] ?? date('Y-m-d', strtotime('+30 days'));

        // 1. Verificar se usuário já existe
        $query = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :u OR cpf = :c");
        $query->bindValue(":u", $email);
        $query->bindValue(":c", $cpf);
        $query->execute();

        if ($query->rowCount() == 0) {
            // 2. Inserir Usuário (Ajustado para suas colunas do banco)
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
            $res->bindValue(":usuario", $email);
            $res->bindValue(":cpf", $cpf);
            $res->bindValue(":senha", $senha);
            $res->bindValue(":nivel", $nivel);
            $res->bindValue(":telefone", $telefone);
            $res->bindValue(":venc", $data_venc);
            $res->execute();

            $id_novo_usuario = $pdo->lastInsertId();

            // 3. Criar Graduação Inicial (Se for Cliente)
            if ($nivel == 'Cliente') {
                $res_g = $pdo->prepare("INSERT INTO graduacoes SET 
                    usuario_id = :id, 
                    cor_faixa = 'Branca', 
                    graus = '0', 
                    total_aulas = '0'");
                $res_g->bindValue(":id", $id_novo_usuario);
                $res_g->execute();
            }
            $cont_sucesso++;
        } else {
            $cont_erro++;
        }
    }

    fclose($handle);
    echo "<script>alert('Importação Concluída! Sucesso: $cont_sucesso | Já existiam: $cont_erro'); window.location='usuarios.php';</script>";
} else {
    echo "Nenhum arquivo enviado.";
}
