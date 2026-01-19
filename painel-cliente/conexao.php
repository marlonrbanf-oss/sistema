<?php
// Configurações do Banco de Dados
$host = 'localhost';
$usuario = 'root'; // No XAMPP o padrão é root
$senha = '';       // No XAMPP o padrão é vazio
$banco = 'nome_do_seu_banco'; // SUBSTITUA PELO NOME QUE VOCÊ DEU AO BANCO

// Fuso horário para os registros de mensalidades e pedidos
date_default_timezone_set('America/Sao_Paulo');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8", "$usuario", "$senha");
    // Ativa o modo de erros para facilitar o desenvolvimento
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo "Erro ao conectar com o banco de dados: " . $e->getMessage();
}
