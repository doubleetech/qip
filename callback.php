<?php
/**
 * QIP — Google OAuth Callback
 * Receives ?code=...&state=... from Google.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/google_oauth.php';

// 1. User cancelled or Google returned an error
if (isset($_GET['error'])) {
    flash_set('error', 'Sign-in was cancelled.');
    redirect('index.php');
}

// 2. Must have a code
$code = $_GET['code'] ?? null;
if (!$code) {
    flash_set('error', 'Missing authorization code.');
    redirect('index.php');
}

// 3. Verify state (CSRF protection for OAuth)
if (!google_verify_state($_GET['state'] ?? null)) {
    flash_set('error', 'Invalid session state. Please try again.');
    redirect('index.php');
}

// 4. Exchange code for access token
$token = google_exchange_code($code);
if (!$token || !empty($token['error'])) {
    flash_set('error', 'Google authentication failed. Please try again.');
    redirect('index.php');
}

// 5. Fetch the Google profile
$profile = google_fetch_profile($token);
if (!$profile || empty($profile['sub']) || empty($profile['email'])) {
    flash_set('error', 'Could not read your Google profile.');
    redirect('index.php');
}

// 6. Upsert user & start session
try {
    $userId = find_or_create_user($profile);
    login_user($userId);
} catch (Throwable $e) {
    if (QIP_DEBUG) {
        error_log('User upsert failed: ' . $e->getMessage());
    }
    flash_set('error', 'Could not create your account. Please try again.');
    redirect('index.php');
}

// 7. Route based on whether they've completed setup
if (!has_business_profile()) {
    redirect('setup/business.php');
}

redirect('pages/dashboard.php');