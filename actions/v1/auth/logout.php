<?php
require_once __DIR__ . '/../config.php';

$userId = requireAuth();

// Get token from header
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    
    // Delete token from database
    $supabase->delete('user_sessions', ['token' => $token]);
}

// Destroy session
session_destroy();

sendResponse(true, null, 'Logged out successfully');
?>
