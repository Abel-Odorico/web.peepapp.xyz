<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. CONFIGURAÇÃO DE BANCO EMUTÁVEL (Substitui os Includes)
$configDir = __DIR__ . '/api/';
if (!is_dir($configDir)) { @mkdir($configDir, 0755, true); }

// Define o Admin Master caso o arquivo config não exista
$admin_user = 'ADM'; 
$admin_pass = 'ADM'; 

try {
    $dbPath = $configDir . '/.db.db'; 
    $db = new SQLite3($dbPath);
    $db->busyTimeout(5000);
    // Garante que a tabela de revendedores exista para não dar erro no SELECT
    $db->exec("CREATE TABLE IF NOT EXISTS resellers(id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT)");
} catch (Exception $e) {
    die("Erro Crítico: O servidor não tem permissão de escrita na pasta api/.");
}

// 2. LÓGICA DE LOGIN
$loginError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $userForm = trim($_POST['user'] ?? '');
    $passForm = trim($_POST['password'] ?? '');

    // Verifica Admin Master
    if ($userForm === $admin_user && $passForm === $admin_pass) {
        $_SESSION['logged_dns'] = true;
        $_SESSION['role'] = 'admin';
        $_SESSION['user_id'] = 0;
        header("Location: dns.php");
        exit;
    } 
    
    // Verifica Revendedor no Banco (Prevenindo SQL Injection)
    $stmt = $db->prepare("SELECT * FROM resellers WHERE username = :u AND password = :p");
    $stmt->bindValue(':u', $userForm, SQLITE3_TEXT);
    $stmt->bindValue(':p', $passForm, SQLITE3_TEXT);
    $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($res) {
        $_SESSION['logged_dns'] = true;
        $_SESSION['role'] = 'reseller';
        $_SESSION['user_id'] = $res['id'];
        header("Location: dns.php");
        exit;
    } else {
        $loginError = "Usuário ou senha incorretos!";
    }
}
?>