<?php
require_once __DIR__ . '/config.php';

/**
 * Security Helper Functions
 * Provides CSRF protection, password hashing, input validation, and session management
 */

// Initialize secure session
function initSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => SESSION_SECURE,
            'httponly' => SESSION_HTTPONLY,
            'samesite' => SESSION_SAMESITE
        ]);
        session_name(SESSION_NAME);
        session_start();
        
        // Regenerate session ID periodically to prevent fixation
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
    }
}

// CSRF Protection
function generateCSRFToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCSRFToken(string $token): bool {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars(generateCSRFToken()) . '">';
}

// Password Hashing
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_HASH_ALGO, ['cost' => PASSWORD_HASH_COST]);
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

function needsRehash(string $hash): bool {
    return password_needs_rehash($hash, PASSWORD_HASH_ALGO, ['cost' => PASSWORD_HASH_COST]);
}

// Input Validation & Sanitization
function sanitizeString(string $input, int $maxLength = 255): string {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return mb_substr($input, 0, $maxLength, 'UTF-8');
}

function sanitizeEmail(string $email): string {
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return $email ?: '';
}

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateUsername(string $username): bool {
    // Alphanumeric and underscore, 3-30 characters
    return preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username) === 1;
}

function validatePassword(string $password): array {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    if (strlen($password) > 72) { // bcrypt limit
        $errors[] = 'Password must not exceed 72 characters';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    return $errors;
}

function sanitizeInt(int|string $input, int $min = 0, int $max = PHP_INT_MAX): int {
    $value = filter_var($input, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $min, 'max_range' => $max]
    ]);
    return $value ?? $min;
}

function sanitizeFloat(float|string $input, float $min = 0, float $max = PHP_FLOAT_MAX): float {
    $value = filter_var($input, FILTER_VALIDATE_FLOAT);
    if ($value === false) return $min;
    return max($min, min($max, $value));
}

// Authentication & Authorization
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) {
        $currentUrl = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header("Location: $redirect?redirect=$currentUrl");
        exit;
    }
}

function requireAdmin(string $redirect = '../login.php'): void {
    if (!isAdmin()) {
        header("Location: $redirect");
        exit;
    }
}

function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername(): ?string {
    return $_SESSION['username'] ?? null;
}

function getCurrentUserRole(): ?string {
    return $_SESSION['role'] ?? null;
}

// Rate Limiting (simple in-memory for demo, use Redis in production)
function checkRateLimit(string $key, int $maxAttempts = 5, int $windowSeconds = 300): bool {
    $now = time();
    $storageKey = "ratelimit_$key";
    
    if (!isset($_SESSION[$storageKey])) {
        $_SESSION[$storageKey] = [];
    }
    
    // Clean old entries
    $_SESSION[$storageKey] = array_filter($_SESSION[$storageKey], function($timestamp) use ($now, $windowSeconds) {
        return ($now - $timestamp) < $windowSeconds;
    });
    
    if (count($_SESSION[$storageKey]) >= $maxAttempts) {
        return false;
    }
    
    $_SESSION[$storageKey][] = $now;
    return true;
}

function clearRateLimit(string $key): void {
    $storageKey = "ratelimit_$key";
    unset($_SESSION[$storageKey]);
}

// Security Headers
function setSecurityHeaders(): void {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data: https:; font-src \'self\'; connect-src \'self\';');
    }
}

// Secure Redirect
function safeRedirect(string $url): void {
    // Validate URL is relative or same domain
    $parsed = parse_url($url);
    if (isset($parsed['host']) && $parsed['host'] !== parse_url(APP_URL, PHP_URL_HOST)) {
        $url = '/';
    }
    header("Location: $url");
    exit;
}

// File Upload Validation
function validateFileUpload(array $file, array $allowedTypes = ['pdf'], int $maxSize = 5242880): array {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error: ' . $file['error'];
        return $errors;
    }
    
    if ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds maximum allowed (' . ($maxSize / 1024 / 1024) . 'MB)';
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = [];
    if (in_array('pdf', $allowedTypes)) $allowedMimes[] = 'application/pdf';
    if (in_array('image', $allowedTypes)) $allowedMimes = array_merge($allowedMimes, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    
    if (!in_array($mimeType, $allowedMimes)) {
        $errors[] = 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes);
    }
    
    return $errors;
}

// Audit Logging
function logSecurityEvent(string $event, string $details = '', string $level = 'info'): void {
    $logEntry = sprintf(
        "[%s] %s: %s | User: %s | IP: %s | UA: %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $event,
        getCurrentUsername() ?? 'anonymous',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    );
    
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    file_put_contents(__DIR__ . '/logs/security.log', $logEntry, FILE_APPEND | LOCK_EX);
}