<?php
session_start();

// Limpa todas as variáveis da sessão
$_SESSION = array();

// Se existirem cookies de sessão, elimina-os
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão no servidor
session_destroy();

// Redireciona o utilizador de volta para a página inicial
header("Location: index.php");
exit;