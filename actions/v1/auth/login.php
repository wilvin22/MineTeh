<?php
require_once __DIR__ . '/../config.php';

// Get request data
$data = getRequestData();
$identifier = $data['identifier'] ?? '';
$password = $data['password'] ?? '';

// Validate input
if (empty($identifier) || empty($password)) {
    sendError('Email/username and password are required');
}

// Check if user exists
// Try username first
$user = $supabase->select('accounts', 'account_id,username,email,password_hash,first_name,last_name', ['username' => $identifier]);

// Debug: Check what we got
if ($user === false) {
    $error = $supabase->getLastError();
    sendError('Database query failed: ' . json_encode($error), 500);
}

// If not found by username, try email
if (empty($user)) {
    $user = $supabase->select('accounts', 'account_id,username,email,password_hash,first_name,last_name', ['email' => $identifier]);
    
    if ($user === false) {
        $error = $supabase->getLastError();
        sendError('Database query failed: ' . json_encode($error), 500);
    }
}

if (empty($user) || !is_array($user)) {
    sendError('Account not found with email/username: ' . $identifier, 404);
}

$user = $user[0];

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    sendError('Incorrect password', 401);
}

// Generate token for mobile apps
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

// Store token in sessions table (create table if needed)
$sessionData = [
    'user_id' => $user['account_id'],
    'token' => $token,
    'expires_at' => $expiresAt,
    'created_at' => date('Y-m-d H:i:s')
];

// Try to insert session (table might not exist yet)
$supabase->insert('user_sessions', $sessionData);

// Set session for web compatibility
$_SESSION['user_id'] = $user['account_id'];
$_SESSION['username'] = $user['username'];

// Remove password from response
unset($user['password_hash']);

// Send response
sendResponse(true, [
    'user' => $user,
    'token' => $token,
    'expires_at' => $expiresAt
], 'Login successful');
?>
