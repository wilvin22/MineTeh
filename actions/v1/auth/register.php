<?php
// Suppress any PHP warnings/notices that could break JSON response
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../config.php';

// Get request data
$data = getRequestData();

$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$firstName = trim($data['first_name'] ?? '');
$lastName = trim($data['last_name'] ?? '');

// Validation array
$errors = [];

// Required fields
if (empty($username)) $errors[] = 'Username is required';
if (empty($email)) $errors[] = 'Email is required';
if (empty($password)) $errors[] = 'Password is required';
if (empty($firstName)) $errors[] = 'First name is required';
if (empty($lastName)) $errors[] = 'Last name is required';

// Username validation: at least 6 characters and contains a number
if (!empty($username)) {
    if (strlen($username) < 6) {
        $errors[] = 'Username must be at least 6 characters long';
    }
    if (!preg_match('/\d/', $username)) {
        $errors[] = 'Username must contain at least one number';
    }
}

// Name validation
if (!empty($firstName)) {
    if (strlen($firstName) < 2) {
        $errors[] = 'First name must be at least 2 characters';
    }
    if (preg_match('/\d/', $firstName)) {
        $errors[] = 'First name cannot contain numbers';
    }
}

if (!empty($lastName)) {
    if (strlen($lastName) < 2) {
        $errors[] = 'Last name must be at least 2 characters';
    }
    if (preg_match('/\d/', $lastName)) {
        $errors[] = 'Last name cannot contain numbers';
    }
}

// Email validation
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

// Password validation: 6-20 chars, 1 uppercase, 1 number, 1 special char
if (!empty($password)) {
    if (strlen($password) < 6 || strlen($password) > 20) {
        $errors[] = 'Password must be between 6 and 20 characters';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    if (!preg_match('/\d/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
}

// If there are validation errors, return them
if (!empty($errors)) {
    sendError(implode('. ', $errors), 400);
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
    'is_admin' => false,
    'created_at' => date('Y-m-d H:i:s')
];

$result = $supabase->insert('accounts', $accountData);

if ($result === false || empty($result)) {
    sendError('Failed to create account. Please try again.', 500);
}

// Get the created user
$newUser = $supabase->select('accounts', 'account_id,username,email,first_name,last_name,is_admin,is_rider', ['username' => $username]);

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

// Set session for web compatibility
$_SESSION['user_id'] = $newUser['account_id'];
$_SESSION['username'] = $newUser['username'];
$_SESSION['is_admin'] = $newUser['is_admin'] ?? false;
$_SESSION['is_rider'] = $newUser['is_rider'] ?? false;

// Send response
sendResponse(true, [
    'user' => $newUser,
    'token' => $token,
    'expires_at' => $expiresAt
], 'Registration successful', 201);
?>
