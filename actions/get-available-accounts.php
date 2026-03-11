<?php
session_start();
header('Content-Type: application/json');

require_once '../database/supabase.php';

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$admin = $supabase->select('accounts', '*', ['account_id' => $_SESSION['user_id']], true);
if (!$admin || !$admin['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Get accounts that are not riders yet
try {
    $accounts = $supabase->customQuery('accounts', 'account_id,username,first_name,last_name,email,is_rider', 
        'is_rider=eq.false&order=username.asc') ?? [];
    
    echo json_encode([
        'success' => true,
        'accounts' => $accounts,
        'count' => count($accounts)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching accounts: ' . $e->getMessage()
    ]);
}
