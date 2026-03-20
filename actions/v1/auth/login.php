<?php
// Suppress any PHP warnings/notices that could break JSON response
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../config.php';

// Get request data
$data = getRequestData();
$identifier = $data['identifier'] ?? '';
$password = $data['password'] ?? '';

// Validate input
if (empty($identifier) || empty($password)) {
    sendError('Email/username and password are required');
}

// Try to find user by email or username using custom query
$user = $supabase->customQuery('accounts', '*', 'or=(email.eq.' . urlencode($identifier) . ',username.eq.' . urlencode($identifier) . ')&limit=1');

if ($user === false) {
    $error = $supabase->getLastError();
    sendError('Database query failed: ' . json_encode($error), 500);
}

if (empty($user) || !is_array($user)) {
    sendError('Account not found with email/username: ' . $identifier, 404);
}

$user = $user[0];

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    sendError('Incorrect password', 401);
}

// Check user status
$user_status = $user['user_status'] ?? 'active';

if ($user_status === 'banned') {
    $reason = $user['status_reason'] ?? 'No reason provided';
    sendError('Your account has been banned. Reason: ' . $reason, 403);
}

if ($user_status === 'restricted') {
    // Check if restriction has expired
    $restriction_until = $user['restriction_until'] ?? null;
    if ($restriction_until && strtotime($restriction_until) <= time()) {
        // Restriction expired, reactivate user
        $supabase->update('accounts', [
            'user_status' => 'active',
            'restriction_until' => null,
            'status_reason' => null
        ], ['account_id' => $user['account_id']]);
        $user_status = 'active';
    }
}

// Generate token for mobile apps
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

// Store token in sessions table
$sessionData = [
    'user_id' => $user['account_id'],
    'token' => $token,
    'expires_at' => $expiresAt,
    'created_at' => date('Y-m-d H:i:s')
];

$supabase->insert('user_sessions', $sessionData);

// Set session for web compatibility
$_SESSION['user_id'] = $user['account_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['is_admin'] = $user['is_admin'] ?? false;
$_SESSION['user_status'] = $user_status;

// Remove sensitive data from response
unset($user['password_hash']);

// Send response
sendResponse(true, [
    'user' => $user,
    'token' => $token,
    'expires_at' => $expiresAt
], 'Login successful');
?>
