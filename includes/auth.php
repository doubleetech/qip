<?php
/**
 * QIP — Authentication Helpers
 * ------------------------------------------------------------
 * Session-based auth on top of Google OAuth.
 * Users table holds identity; business_profiles holds workspace.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// ----- Session bootstrap ----------------------------------
function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (QIP_ENV === 'production'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_start();

    // Regenerate every 30 minutes to prevent fixation
    if (empty($_SESSION['_last_regen'])) {
        $_SESSION['_last_regen'] = time();
    } elseif (time() - $_SESSION['_last_regen'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = time();
    }
}

// ----- Login state ----------------------------------------
function is_logged_in(): bool
{
    session_boot();
    return !empty($_SESSION['user_id']);
}

function current_user_id(): ?int
{
    session_boot();
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Fetch the full user row for the current session.
 */
function current_user(): ?array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    if (!is_logged_in()) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([current_user_id()]);
    $row = $stmt->fetch();
    return $cache = ($row ?: null);
}

/**
 * Fetch the business profile for the current user.
 */
function current_business(): ?array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    if (!is_logged_in()) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM business_profiles WHERE user_id = ? LIMIT 1');
    $stmt->execute([current_user_id()]);
    $row = $stmt->fetch();
    return $cache = ($row ?: null);
}

function has_business_profile(): bool
{
    return current_business() !== null;
}

// ----- Session login/logout -------------------------------
function login_user(int $userId): void
{
    session_boot();
    session_regenerate_id(true);
    $_SESSION['user_id']    = $userId;
    $_SESSION['logged_in_at'] = time();
}

function logout_user(): void
{
    session_boot();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

// ----- Route guards ---------------------------------------
/**
 * Require the visitor to be authenticated.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        flash_set('error', 'Please sign in to continue.');
        redirect('index.php');
    }
}

/**
 * Require a completed business profile.
 * If authenticated but no profile, force them to setup.
 */
function require_business(): void
{
    require_login();
    if (!has_business_profile()) {
        redirect('setup/business.php');
    }
}

/**
 * If the user is logged in AND has a business profile,
 * bounce them away from login/setup pages.
 */
function redirect_if_ready(string $target = 'pages/dashboard.php'): void
{
    if (is_logged_in() && has_business_profile()) {
        redirect($target);
    }
}

// ----- User & business upsert -----------------------------
/**
 * Find or create a user from a Google profile payload.
 * Returns the user id.
 */
function find_or_create_user(array $googleUser): int
{
    $googleId = $googleUser['sub'] ?? null;
    $email    = $googleUser['email'] ?? null;
    $name     = $googleUser['name'] ?? 'QIP User';
    $avatar   = $googleUser['picture'] ?? null;

    if (!$googleId || !$email) {
        throw new RuntimeException('Invalid Google profile payload.');
    }

    $pdo = db();

    // Try by google_id first
    $stmt = $pdo->prepare('SELECT id FROM users WHERE google_id = ? LIMIT 1');
    $stmt->execute([$googleId]);
    if ($row = $stmt->fetch()) {
        // Refresh stored profile data
        $upd = $pdo->prepare(
            'UPDATE users SET email = ?, name = ?, avatar_url = ? WHERE id = ?'
        );
        $upd->execute([$email, $name, $avatar, $row['id']]);
        return (int) $row['id'];
    }

    // Try by email (in case google_id changed)
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($row = $stmt->fetch()) {
        $upd = $pdo->prepare(
            'UPDATE users SET google_id = ?, name = ?, avatar_url = ? WHERE id = ?'
        );
        $upd->execute([$googleId, $name, $avatar, $row['id']]);
        return (int) $row['id'];
    }

    // Create new user
    $ins = $pdo->prepare(
        'INSERT INTO users (google_id, email, name, avatar_url) VALUES (?, ?, ?, ?)'
    );
    $ins->execute([$googleId, $email, $name, $avatar]);
    return (int) $pdo->lastInsertId();
}


/**
 * Assert the current user owns the given user_id.
 * Use before any query that touches user-scoped rows.
 */
function require_ownership(int $resourceUserId): void
{
    if (current_user_id() !== $resourceUserId) {
        http_response_code(403);
        exit('Forbidden');
    }
}