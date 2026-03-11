<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

// Log the request
error_log('admin-rider-action.php called - Action: ' . ($_POST['action'] ?? 'none'));

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

$action = $_POST['action'] ?? '';

if ($action == 'add') {
    // Add new rider
    $account_id = $_POST['account_id'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $vehicle_type = $_POST['vehicle_type'] ?? 'motorcycle';
    $license_number = trim($_POST['license_number'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (!$full_name || !$phone_number) {
        echo json_encode(['success' => false, 'message' => 'Full name and phone number are required']);
        exit;
    }

    // If no existing account selected, create new account
    if (!$account_id) {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');

        if (!$username || !$email || !$password || !$first_name || !$last_name) {
            echo json_encode(['success' => false, 'message' => 'All account fields are required']);
            exit;
        }

        // Check if username already exists
        $existing = $supabase->select('accounts', 'account_id', ['username' => $username], true);
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            exit;
        }

        // Note: Email duplicates are allowed for riders

        // Create new account
        $account_data = [
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => $first_name,
            'last_name' => $last_name,
            'is_admin' => false,
            'is_rider' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $new_account = $supabase->insert('accounts', $account_data);
        if (!$new_account) {
            echo json_encode(['success' => false, 'message' => 'Failed to create account']);
            exit;
        }

        // Get the new account_id
        $account = $supabase->select('accounts', 'account_id', ['username' => $username], true);
        $account_id = $account['account_id'];
    } else {
        // Update existing account to mark as rider
        $supabase->update('accounts', ['is_rider' => true], ['account_id' => $account_id]);
    }

    // Create rider profile
    $rider_data = [
        'account_id' => $account_id,
        'full_name' => $full_name,
        'phone_number' => $phone_number,
        'vehicle_type' => $vehicle_type,
        'license_number' => $license_number,
        'status' => $status,
        'rating' => 5.00,
        'total_deliveries' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $result = $supabase->insert('riders', $rider_data);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Rider added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create rider profile']);
    }

} elseif ($action == 'edit') {
    // Edit existing rider
    $rider_id = (int)($_POST['rider_id'] ?? 0);
    $full_name = trim($_POST['full_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $vehicle_type = $_POST['vehicle_type'] ?? 'motorcycle';
    $license_number = trim($_POST['license_number'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (!$rider_id || !$full_name || !$phone_number) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $update_data = [
        'full_name' => $full_name,
        'phone_number' => $phone_number,
        'vehicle_type' => $vehicle_type,
        'license_number' => $license_number,
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $result = $supabase->update('riders', $update_data, ['rider_id' => $rider_id]);
    
    if ($result !== false) {
        echo json_encode(['success' => true, 'message' => 'Rider updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update rider']);
    }

} elseif ($action == 'update_status') {
    // Update rider status
    $rider_id = (int)($_POST['rider_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (!$rider_id || !in_array($status, ['active', 'inactive', 'suspended'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }

    $result = $supabase->update('riders', [
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ], ['rider_id' => $rider_id]);
    
    if ($result !== false) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
