<?php
require_once __DIR__ . '/security.php';

initSecureSession();

// Log the logout event
if (isLoggedIn()) {
    logSecurityEvent('logout', "User: " . getCurrentUsername());
}

// Unset all session variables
$_SESSION = [];

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login
header("Location: login.php");
exit;