<?php
/**
 * QIP — Global Helper Functions
 */

require_once __DIR__ . '/../config/config.php';

// ----- Output escaping ------------------------------------
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ----- Redirects ------------------------------------------
function redirect(string $path): void
{
    // Allow absolute URLs or relative paths
    if (!preg_match('#^https?://#i', $path)) {
        $path = APP_URL . '/' . ltrim($path, '/');
    }
    header('Location: ' . $path);
    exit;
}

// ----- URL helper -----------------------------------------
function url(string $path = ''): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

// ----- Flash messages -------------------------------------
function flash_set(string $key, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }
    $msg = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

function flash_has(string $key): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return isset($_SESSION['_flash'][$key]);
}

// ----- Input validation -----------------------------------
function post(string $key, $default = null)
{
    return $_POST[$key] ?? $default;
}

function get(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}

/**
 * Trim + nullify empty strings.
 */
function clean(?string $value): ?string
{
    if ($value === null) {
        return null;
    }
    $value = trim($value);
    return $value === '' ? null : $value;
}

/**
 * Basic email validation.
 */
function is_valid_email(?string $email): bool
{
    return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ----- CSRF -----------------------------------------------
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return is_string($token)
        && !empty($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $token);
}

// ----- Misc -----------------------------------------------
function current_page(): string
{
    return basename($_SERVER['PHP_SELF'] ?? '');
}