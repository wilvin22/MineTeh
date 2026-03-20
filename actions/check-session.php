<?php
session_start();
header('Content-Type: application/json');

require_once '../database/supabase.php';

$response = [
    'logged_in' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? null,
    'username' => $_SESSION['username'] ?? null,
    'is_admin' => $_SESSION['is_admin'] ?? false,
    'user_status' => $_SESSION['user_status'] ?? null
];

if ($response['logged_in']) {
    try {
        $user = $supabase->select('accounts', '*', ['account_id' => $_SESSION['user_id']], true);
        if ($user) {
            $response['user_data'] = [
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'is_admin' => $user['is_admin']
            ];
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);
