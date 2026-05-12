<?php
// Configurações de conexão com o banco de dados do XAMPP
$host = 'localhost';      // O servidor é local
$db   = 'mural_fatec';    // O nome exato do banco que criaste no phpMyAdmin
$user = 'root';           // Usuário padrão do XAMPP
$pass = '';               // Senha padrão do XAMPP (vazia)

try {
    // Criando a conexão usando PDO (mais seguro)
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    
    // Configura o PDO para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Se a conexão falhar, exibe o erro e para o script
    die("Ops! Erro ao conectar com o banco de dados: " . $e->getMessage());
}
?>