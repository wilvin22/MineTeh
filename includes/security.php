<?php
/**
 * Security Configuration and Helper Functions
 * 
 * Include this file at the top of every page that needs security features
 */

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Regenerate session ID on login to prevent session fixation
function security_regenerate_session() {
    session_regenerate_id(true);
}

// Force HTTPS in production
function security_force_https() {
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        if (php_sapi_name() !== 'cli') {
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirect, true, 301);
            exit;
        }
    }
}

// Set security headers
function security_set_headers() {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (adjust as needed)
    header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https:; img-src 'self' data: https:; font-src 'self' data: https:;");
    
    // Remove PHP version header
    header_remove('X-Powered-By');
}

// Sanitize input
function security_sanitize($input, $type = 'string') {
    if (is_array($input)) {
        return array_map(function($item) use ($type) {
            return security_sanitize($item, $type);
        }, $input);
    }
    
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        
        case 'html':
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        case 'string':
        default:
            return trim(strip_tags($input));
    }
}

// Validate input
function security_validate($input, $type, $options = []) {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
        
        case 'int':
            $result = filter_var($input, FILTER_VALIDATE_INT, $options);
            return $result !== false;
        
        case 'float':
            $result = filter_var($input, FILTER_VALIDATE_FLOAT, $options);
            return $result !== false;
        
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL) !== false;
        
        case 'username':
            // 3-20 characters, alphanumeric and underscore only
            return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $input);
        
        case 'password':
            // At least 8 characters, with uppercase, lowercase, number, and special char
            $min_length = $options['min_length'] ?? 8;
            if (strlen($input) < $min_length) return false;
            
            $require_uppercase = $options['require_uppercase'] ?? true;
            $require_lowercase = $options['require_lowercase'] ?? true;
            $require_number = $options['require_number'] ?? true;
            $require_special = $options['require_special'] ?? true;
            
            if ($require_uppercase && !preg_match('/[A-Z]/', $input)) return false;
            if ($require_lowercase && !preg_match('/[a-z]/', $input)) return false;
            if ($require_number && !preg_match('/[0-9]/', $input)) return false;
            if ($require_special && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $input)) return false;
            
            return true;
        
        case 'phone':
            // Philippine phone number format
            return preg_match('/^(09|\+639)\d{9}$/', $input);
        
        default:
            return !empty($input);
    }
}

// Check if user is logged in
function security_require_login($redirect = '../login.php') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $redirect);
        exit;
    }
}

// Check if user is admin
function security_require_admin($redirect = '../login.php') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        header('Location: ' . $redirect);
        exit;
    }
}

// Check if user is active (not banned or restricted)
function security_check_user_status() {
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    require_once __DIR__ . '/../database/supabase.php';
    global $supabase;
    
    $user_id = $_SESSION['user_id'];
    $users = $supabase->customQuery('users', 'status,restriction_until', "user_id=eq.$user_id");
    
    if ($users && !empty($users)) {
        $user = $users[0];
        
        // Check if banned
        if ($user['status'] === 'banned') {
            session_destroy();
            header('Location: ../login.php?error=banned');
            exit;
        }
        
        // Check if restricted
        if ($user['status'] === 'restricted') {
            $restriction_until = $user['restriction_until'] ?? null;
            
            // Check if restriction has expired
            if ($restriction_until && strtotime($restriction_until) < time()) {
                // Lift restriction
                $supabase->update('users', ['status' => 'active', 'restriction_until' => null], ['user_id' => $user_id]);
            } else {
                // Still restricted
                $_SESSION['is_restricted'] = true;
                $_SESSION['restriction_until'] = $restriction_until;
            }
        } else {
            unset($_SESSION['is_restricted']);
            unset($_SESSION['restriction_until']);
        }
    }
}

// Prevent restricted users from certain actions
function security_require_active_status($message = 'Your account is restricted. You cannot perform this action.') {
    if (isset($_SESSION['is_restricted']) && $_SESSION['is_restricted'] === true) {
        http_response_code(403);
        
        // Check if API request
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $message,
                'restriction_until' => $_SESSION['restriction_until'] ?? null
            ]);
        } else {
            echo "<!DOCTYPE html>
<html>
<head>
    <title>Account Restricted</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .error-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
        }
        .error-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        h1 {
            color: #e74c3c;
            margin-bottom: 10px;
        }
        p {
            color: #666;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class='error-box'>
        <div class='error-icon'>🚫</div>
        <h1>Account Restricted</h1>
        <p>$message</p>
        <p><a href='javascript:history.back()'>Go Back</a></p>
    </div>
</body>
</html>";
        }
        exit;
    }
}

// Log security events
function security_log($event, $details = []) {
    $log_file = __DIR__ . '/../logs/security.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_id = $_SESSION['user_id'] ?? 'guest';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $log_entry = [
        'timestamp' => $timestamp,
        'event' => $event,
        'user_id' => $user_id,
        'ip' => $ip,
        'user_agent' => $user_agent,
        'details' => $details
    ];
    
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND);
}

// Apply security headers
security_set_headers();

// Check user status on every page load
if (isset($_SESSION['user_id'])) {
    security_check_user_status();
}
?>
