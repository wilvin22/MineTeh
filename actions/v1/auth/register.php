<?php
require_once __DIR__ . '/../config.php';

// Get request data
$data = getRequestData();

$username = $data['username'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$firstName = $data['first_name'] ?? '';
$lastName = $data['last_name'] ?? '';

// Validate input
if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
    sendError('All fields are required');
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendError('Invalid email format');
}

// Check if username exists
$existingUser = $supabase->select('accounts', 'account_id', ['username' => $username]);
if (!empty($existingUser)) {
    sendError('Username already taken', 409);
}

// Check if email exists
$existingEmail = $supabase->select('accounts', 'account_id', ['email' => $email]);
if (!empty($existingEmail)) {
    sendError('Email already registered', 409);
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Create account
$accountData = [
    'username' => $username,
    'email' => $email,
    'password_hash' => $hashedPassword,
    'first_name' => $firstName,
    'last_name' => $lastName,
    'created_at' => date('Y-m-d H:i:s')
];

$result = $supabase->insert('accounts', $accountData);

if ($result === false) {
    sendError('Failed to create account. Please try again.', 500);
}

// Get the created user
$newUser = $supabase->select('accounts', 'account_id,username,email,first_name,last_name', ['username' => $username]);

if (empty($newUser)) {
    sendError('Account created but failed to retrieve details', 500);
}

$newUser = $newUser[0];

// Generate token
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

// Store token
$sessionData = [
    'user_id' => $newUser['account_id'],
    'token' => $token,
    'expires_at' => $expiresAt,
    'created_at' => date('Y-m-d H:i:s')
];
$supabase->insert('user_sessions', $sessionData);

// Set session for web
$_SESSION['user_id'] = $newUser['account_id'];
$_SESSION['username'] = $newUser['username'];

// Send response
sendResponse(true, [
    'user' => $newUser,
    'token' => $token,
    'expires_at' => $expiresAt
], 'Registration successful', 201);
?>
