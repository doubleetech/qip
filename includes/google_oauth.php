<?php
/**
 * QIP — Google OAuth Wrapper
 * ------------------------------------------------------------
 * Thin wrapper around Google\Client for sign-in only.
 * Scope: openid, email, profile.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Build a configured Google\Client instance.
 */
function google_client(): Google\Client
{
    static $client = null;
    if ($client instanceof Google\Client) {
        return $client;
    }

    $client = new Google\Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
    $client->addScope('openid');
    $client->addScope('email');
    $client->addScope('profile');
    $client->setAccessType('online');
    $client->setPrompt('select_account');   // let user pick account each time
    $client->setIncludeGrantedScopes(true);

    return $client;
}

/**
 * Generate the Google consent URL and store state in session.
 */
function google_auth_url(): string
{
    session_boot();
    $client = google_client();
    $state  = bin2hex(random_bytes(16));
    $_SESSION['_oauth_state'] = $state;
    $client->setState($state);
    return $client->createAuthUrl();
}

/**
 * Verify the state parameter matches what we stored.
 */
function google_verify_state(?string $state): bool
{
    session_boot();
    if (!is_string($state) || empty($_SESSION['_oauth_state'])) {
        return false;
    }
    $ok = hash_equals($_SESSION['_oauth_state'], $state);
    unset($_SESSION['_oauth_state']);
    return $ok;
}

/**
 * Exchange the authorization code for an access token.
 * Returns the token array or null on failure.
 */
function google_exchange_code(string $code): ?array
{
    try {
        return google_client()->fetchAccessTokenWithAuthCode($code);
    } catch (Throwable $e) {
        if (QIP_DEBUG) {
            error_log('Google token exchange failed: ' . $e->getMessage());
        }
        return null;
    }
}

/**
 * Fetch the authenticated user's Google profile.
 * Returns ['sub','email','name','picture',...] or null.
 */
function google_fetch_profile(array $token): ?array
{
    try {
        $client = google_client();
        $client->setAccessToken($token);

        // Prefer ID token payload (no extra API call)
        if (!empty($token['id_token'])) {
            $payload = $client->verifyIdToken($token['id_token']);
            if ($payload && !empty($payload['sub'])) {
                return $payload;
            }
        }

        // Fallback: userinfo endpoint
        $oauth2 = new Google\Service\Oauth2($client);
        $info   = $oauth2->userinfo->get();
        return [
            'sub'     => $info->getId(),
            'email'   => $info->getEmail(),
            'name'    => $info->getName(),
            'picture' => $info->getPicture(),
        ];
    } catch (Throwable $e) {
        if (QIP_DEBUG) {
            error_log('Google profile fetch failed: ' . $e->getMessage());
        }
        return null;
    }
}