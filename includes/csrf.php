<?php
/**
 * CSRF Protection Helper Functions
 * 
 * Usage:
 * 1. In forms: <?php echo csrf_field(); ?>
 * 2. Before processing: csrf_verify() or die('CSRF validation failed');
 */

/**
 * Generate CSRF token and store in session
 */
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate CSRF hidden input field for forms
 */
function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verify CSRF token from POST request
 */
function csrf_verify() {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    if (!isset($_POST['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Verify CSRF token or die with error
 */
function csrf_protect() {
    if (!csrf_verify()) {
        http_response_code(403);
        die('CSRF token validation failed. Please refresh the page and try again.');
    }
}

/**
 * Regenerate CSRF token (call after successful form submission)
 */
function csrf_regenerate() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
