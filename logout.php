<?php
/**
 * LOGOUT SCRIPT - PRIME VIDEO CLONE
 * Encerra a sessão de forma segura e redireciona para o login.
 */

// 1. Inicia a sessão para poder manipulá-la
session_start();

// 2. Limpa todas as variáveis de sessão do sistema (remove dados do usuário)
$_SESSION = array();
unset($_SESSION["webTvplayer"]);

// 3. (Opcional) Destrói o cookie da sessão para segurança extra
// Isso garante que o ID da sessão antiga não possa ser reutilizado
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destrói a sessão no servidor
session_destroy();

// 5. Redireciona o usuário para a tela de login
header("Location: index.php");
exit;
?>
