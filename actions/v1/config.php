<?php
// API Configuration and Helper Functions

// Prevent any output before JSON response
ob_start();

// Disable error display (errors should be logged, not displayed)
ini_set('display_errors', '0');
error_reporting(E_ALL);

// CORS headers for mobile app access
header('Access-Control-Allow-Origin: *'); // Allow all origins for testing, restrict in production
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../database/supabase.php';

// Start session for web compatibility
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get authenticated user ID from session or token
 */
function getAuthenticatedUserId() {
    // Check session first (for web)
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    
    // Check Authorization header (for mobile)
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        return validateToken($token);
    }
    
    return null;
}

/**
 * Validate API token and return user ID
 */
function validateToken($token) {
    global $supabase;
    
    // Query sessions table for valid token
    $session = $supabase->select('user_sessions', 'user_id,expires_at', ['token' => $token]);
    
    if (!empty($session) && is_array($session)) {
        $session = $session[0];
        
        // Check if token is expired
        if (strtotime($session['expires_at']) > time()) {
            return $session['user_id'];
        }
    }
    
    return null;
}

/**
 * Send JSON response
 */
function sendResponse($success, $data = null, $message = null, $statusCode = 200) {
    // Clear any output buffers to ensure clean JSON
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Set headers
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = ['success' => $success];
    
    if ($message !== null) {
        $response['message'] = $message;
    }
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send error response
 */
function sendError($message, $statusCode = 400) {
    sendResponse(false, null, $message, $statusCode);
}

/**
 * Require authentication
 */
function requireAuth() {
    $userId = getAuthenticatedUserId();
    if (!$userId) {
        sendError('Unauthorized. Please login.', 401);
    }
    return $userId;
}

/**
 * Get JSON input
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

/**
 * Get request data (works for both JSON and form data)
 */
function getRequestData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        return getJsonInput();
    }
    
    return $_POST;
}
?>
