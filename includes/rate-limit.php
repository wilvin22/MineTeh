<?php
/**
 * Rate Limiting System
 * 
 * Prevents brute force attacks and API abuse
 * 
 * Usage:
 * rate_limit('login', 5, 300); // 5 attempts per 5 minutes
 */

/**
 * Check if rate limit is exceeded
 * 
 * @param string $action Action identifier (e.g., 'login', 'api_call')
 * @param int $max_attempts Maximum attempts allowed
 * @param int $time_window Time window in seconds
 * @param string $identifier User identifier (defaults to IP address)
 * @return bool True if rate limit exceeded, false otherwise
 */
function rate_limit_exceeded($action, $max_attempts = 5, $time_window = 300, $identifier = null) {
    if ($identifier === null) {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    $key = "rate_limit_{$action}_{$identifier}";
    
    // Initialize or get current attempts
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
    }
    
    $data = $_SESSION[$key];
    $current_time = time();
    $time_elapsed = $current_time - $data['first_attempt'];
    
    // Reset if time window has passed
    if ($time_elapsed > $time_window) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'first_attempt' => $current_time
        ];
        return false;
    }
    
    // Check if limit exceeded
    if ($data['attempts'] >= $max_attempts) {
        $time_remaining = $time_window - $time_elapsed;
        $_SESSION["{$key}_blocked_until"] = $current_time + $time_remaining;
        return true;
    }
    
    // Increment attempts
    $_SESSION[$key]['attempts']++;
    
    return false;
}

/**
 * Get time remaining until rate limit reset
 * 
 * @param string $action Action identifier
 * @param string $identifier User identifier
 * @return int Seconds remaining, or 0 if not blocked
 */
function rate_limit_time_remaining($action, $identifier = null) {
    if ($identifier === null) {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    $key = "rate_limit_{$action}_{$identifier}_blocked_until";
    
    if (!isset($_SESSION[$key])) {
        return 0;
    }
    
    $blocked_until = $_SESSION[$key];
    $current_time = time();
    
    if ($current_time >= $blocked_until) {
        unset($_SESSION[$key]);
        return 0;
    }
    
    return $blocked_until - $current_time;
}

/**
 * Reset rate limit for an action
 * 
 * @param string $action Action identifier
 * @param string $identifier User identifier
 */
function rate_limit_reset($action, $identifier = null) {
    if ($identifier === null) {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    $key = "rate_limit_{$action}_{$identifier}";
    unset($_SESSION[$key]);
    unset($_SESSION["{$key}_blocked_until"]);
}

/**
 * Rate limit with automatic response
 * Dies with error message if limit exceeded
 * 
 * @param string $action Action identifier
 * @param int $max_attempts Maximum attempts allowed
 * @param int $time_window Time window in seconds
 */
function rate_limit($action, $max_attempts = 5, $time_window = 300) {
    if (rate_limit_exceeded($action, $max_attempts, $time_window)) {
        $time_remaining = rate_limit_time_remaining($action);
        $minutes = ceil($time_remaining / 60);
        
        http_response_code(429);
        
        // Check if this is an API request
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Too many attempts. Please try again in $minutes minute(s).",
                'retry_after' => $time_remaining
            ]);
        } else {
            echo "<!DOCTYPE html>
<html>
<head>
    <title>Too Many Requests</title>
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
        .countdown {
            font-size: 24px;
            font-weight: bold;
            color: #FF6B35;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class='error-box'>
        <div class='error-icon'>⏱️</div>
        <h1>Too Many Requests</h1>
        <p>You've made too many attempts. Please wait before trying again.</p>
        <div class='countdown' id='countdown'>$minutes minute(s)</div>
        <p><a href='javascript:history.back()'>Go Back</a></p>
    </div>
    <script>
        let seconds = $time_remaining;
        const countdown = document.getElementById('countdown');
        setInterval(() => {
            if (seconds <= 0) {
                location.reload();
                return;
            }
            seconds--;
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            countdown.textContent = mins + ':' + secs.toString().padStart(2, '0');
        }, 1000);
    </script>
</body>
</html>";
        }
        exit;
    }
}
?>
