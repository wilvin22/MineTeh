<?php
session_start();
include "../database/supabase.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Action required']);
    exit;
}

$action = $input['action'];

try {
    switch ($action) {
        case 'add':
            $address_data = [
                'user_id' => $user_id,
                'address_type' => $input['address_type'] ?? 'home',
                'address_label' => trim($input['address_label'] ?? ''),
                'full_name' => trim($input['full_name']),
                'phone' => trim($input['phone'] ?? ''),
                'address_line1' => trim($input['address_line1']),
                'address_line2' => trim($input['address_line2'] ?? ''),
                'city' => trim($input['city']),
                'state_province' => trim($input['state_province'] ?? ''),
                'postal_code' => trim($input['postal_code'] ?? ''),
                'country' => trim($input['country'] ?? 'Philippines'),
                'is_default' => $input['is_default'] ?? false
            ];
            
            // If this is set as default, unset other defaults
            if ($address_data['is_default']) {
                $supabase->update('user_addresses', ['is_default' => false], ['user_id' => $user_id]);
            }
            
            $result = $supabase->insert('user_addresses', $address_data);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Address added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add address']);
            }
            break;
            
        case 'update':
            $address_id = (int)$input['address_id'];
            
            // Verify ownership
            $existing = $supabase->select('user_addresses', 'user_id', ['address_id' => $address_id], true);
            if (!$existing || $existing['user_id'] != $user_id) {
                echo json_encode(['success' => false, 'message' => 'Address not found']);
                exit;
            }
            
            $address_data = [
                'address_type' => $input['address_type'] ?? 'home',
                'address_label' => trim($input['address_label'] ?? ''),
                'full_name' => trim($input['full_name']),
                'phone' => trim($input['phone'] ?? ''),
                'address_line1' => trim($input['address_line1']),
                'address_line2' => trim($input['address_line2'] ?? ''),
                'city' => trim($input['city']),
                'state_province' => trim($input['state_province'] ?? ''),
                'postal_code' => trim($input['postal_code'] ?? ''),
                'country' => trim($input['country'] ?? 'Philippines'),
                'is_default' => $input['is_default'] ?? false
            ];
            
            // If this is set as default, unset other defaults
            if ($address_data['is_default']) {
                $supabase->update('user_addresses', ['is_default' => false], ['user_id' => $user_id]);
            }
            
            $result = $supabase->update('user_addresses', $address_data, ['address_id' => $address_id]);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Address updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update address']);
            }
            break;
            
        case 'delete':
            $address_id = (int)$input['address_id'];
            
            // Verify ownership
            $existing = $supabase->select('user_addresses', 'user_id', ['address_id' => $address_id], true);
            if (!$existing || $existing['user_id'] != $user_id) {
                echo json_encode(['success' => false, 'message' => 'Address not found']);
                exit;
            }
            
            $result = $supabase->delete('user_addresses', ['address_id' => $address_id]);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Address deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete address']);
            }
            break;
            
        case 'set_default':
            $address_id = (int)$input['address_id'];
            
            // Verify ownership
            $existing = $supabase->select('user_addresses', 'user_id', ['address_id' => $address_id], true);
            if (!$existing || $existing['user_id'] != $user_id) {
                echo json_encode(['success' => false, 'message' => 'Address not found']);
                exit;
            }
            
            // Unset all defaults first
            $supabase->update('user_addresses', ['is_default' => false], ['user_id' => $user_id]);
            
            // Set new default
            $result = $supabase->update('user_addresses', ['is_default' => true], ['address_id' => $address_id]);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Default address updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update default address']);
            }
            break;
            
        case 'get_all':
            $addresses = $supabase->select('user_addresses', '*', ['user_id' => $user_id]);
            echo json_encode(['success' => true, 'addresses' => $addresses]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>