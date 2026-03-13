<?php
session_start();
header('Content-Type: application/json');

require_once '../database/supabase.php';
require_once '../database/notifications_helper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get rider info
$rider = $supabase->select('riders', '*', ['account_id' => $_SESSION['user_id']], true);
if (!$rider) {
    echo json_encode(['success' => false, 'message' => 'Not authorized as rider']);
    exit;
}

$delivery_id = (int)($_POST['delivery_id'] ?? 0);
$delivery_notes = $_POST['delivery_notes'] ?? '';
$signature_data = $_POST['signature'] ?? '';

if (!$delivery_id) {
    echo json_encode(['success' => false, 'message' => 'Missing delivery ID']);
    exit;
}

// Get delivery
$delivery = $supabase->select('deliveries', '*', ['delivery_id' => $delivery_id], true);
if (!$delivery || $delivery['rider_id'] != $rider['rider_id']) {
    echo json_encode(['success' => false, 'message' => 'Delivery not found or access denied']);
    exit;
}

// Handle photo upload
$photo_url = null;
if (isset($_FILES['delivery_photo']) && $_FILES['delivery_photo']['error'] === 0) {
    $upload_dir = __DIR__ . '/../uploads/delivery_proofs/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $file_type = $_FILES['delivery_photo']['type'];
    
    if (in_array($file_type, $allowed_types)) {
        $file_ext = pathinfo($_FILES['delivery_photo']['name'], PATHINFO_EXTENSION);
        $file_name = 'delivery_' . $delivery_id . '_' . time() . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['delivery_photo']['tmp_name'], $file_path)) {
            $photo_url = 'uploads/delivery_proofs/' . $file_name;
        }
    }
}

// Handle signature
$signature_url = null;
if ($signature_data) {
    // Save signature as image
    $signature_dir = __DIR__ . '/../uploads/signatures/';
    if (!is_dir($signature_dir)) {
        mkdir($signature_dir, 0777, true);
    }
    
    $signature_file = 'signature_' . $delivery_id . '_' . time() . '.png';
    $signature_path = $signature_dir . $signature_file;
    
    // Remove data:image/png;base64, prefix
    $signature_data = str_replace('data:image/png;base64,', '', $signature_data);
    $signature_data = str_replace(' ', '+', $signature_data);
    $decoded = base64_decode($signature_data);
    
    if ($decoded !== false) {
        file_put_contents($signature_path, $decoded);
        $signature_url = 'uploads/signatures/' . $signature_file;
    }
}

// Update delivery
$update_data = [
    'delivery_status' => 'delivered',
    'delivered_at' => date('Y-m-d H:i:s'),
    'actual_delivery_time' => date('Y-m-d H:i:s'),
    'delivery_notes' => $delivery_notes,
    'updated_at' => date('Y-m-d H:i:s')
];

if ($photo_url) {
    $update_data['proof_of_delivery_photo'] = $photo_url;
}

if ($signature_url) {
    $update_data['recipient_signature'] = $signature_url;
}

$result = $supabase->update('deliveries', $update_data, ['delivery_id' => $delivery_id]);

if ($result === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to update delivery']);
    exit;
}

// Add tracking entry
$supabase->insert('delivery_tracking', [
    'delivery_id' => $delivery_id,
    'status' => 'delivered',
    'notes' => 'Delivery completed with proof',
    'created_by' => $_SESSION['user_id'],
    'created_at' => date('Y-m-d H:i:s')
]);

// Update rider stats
$supabase->update('riders', [
    'total_deliveries' => $rider['total_deliveries'] + 1,
    'updated_at' => date('Y-m-d H:i:s')
], ['rider_id' => $rider['rider_id']]);

// Create rider earning record
$supabase->insert('rider_earnings', [
    'rider_id' => $rider['rider_id'],
    'delivery_id' => $delivery_id,
    'amount' => $delivery['delivery_fee'],
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s')
]);

// Get order info for notification
$order = $supabase->select('orders', '*', ['order_id' => $delivery['order_id']], true);

if ($order) {
    // Notify buyer
    create_notification(
        $order['buyer_id'],
        'delivery_completed',
        'Your order has been delivered successfully!',
        $order['listing_id']
    );
    
    // Notify seller
    create_notification(
        $order['seller_id'],
        'order_delivered',
        'Your item has been delivered to the buyer',
        $order['listing_id']
    );
}

echo json_encode([
    'success' => true,
    'message' => 'Delivery completed successfully',
    'delivery_id' => $delivery_id
]);
