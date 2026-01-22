<?php
/**
 * EficienSys - Configuracao do Banco de Dados
 * Configure as credenciais do seu banco MySQL
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'eficiensys');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Conexao PDO
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("Erro de conexao: " . $e->getMessage());
    }
}

// Funcao para sanitizar entrada
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Funcao para gerar hash de senha
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Funcao para verificar senha
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
