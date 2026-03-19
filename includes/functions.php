<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escapes output to prevent XSS.
 * @param string $string
 * @return string
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Checks if a user is logged in.
 * Optionally checks for a specific role.
 * @param string|null $role
 * @return bool
 */
function isLoggedIn($role = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    if ($role && $_SESSION['role'] !== $role) {
        return false;
    }
    return true;
}

/**
 * Redirects to a specific URL.
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Generates a CSRF token.
 * @return string
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a CSRF token.
 * @param string $token
 * @return bool
 */
function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sets a flash message.
 * @param string $key
 * @param string $message
 * @param string $type (success, danger, warning, info)
 */
function setFlashMessage($key, $message, $type = 'info') {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Gets and clears a flash message.
 * @param string $key
 * @return string|null
 */
function getFlashMessage($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return "<div class='alert alert-{$msg['type']} alert-dismissible fade show' role='alert'>
                    {$msg['message']}
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
    return null;
}
?>
