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

$input = json_decode(file_get_contents('php://input'), true);
$delivery_id = (int)($input['delivery_id'] ?? 0);
$new_status = $input['status'] ?? '';

if (!$delivery_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate status
$valid_statuses = ['assigned', 'picked_up', 'in_transit', 'delivered', 'failed'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Get delivery
$delivery = $supabase->select('deliveries', '*', ['delivery_id' => $delivery_id], true);
if (!$delivery || $delivery['rider_id'] != $rider['rider_id']) {
    echo json_encode(['success' => false, 'message' => 'Delivery not found or access denied']);
    exit;
}

// Update delivery status
$update_data = [
    'delivery_status' => $new_status,
    'updated_at' => date('Y-m-d H:i:s')
];

// Set timestamp based on status
if ($new_status == 'picked_up') {
    $update_data['picked_up_at'] = date('Y-m-d H:i:s');
} elseif ($new_status == 'delivered') {
    $update_data['delivered_at'] = date('Y-m-d H:i:s');
    $update_data['actual_delivery_time'] = date('Y-m-d H:i:s');
}

$result = $supabase->update('deliveries', $update_data, ['delivery_id' => $delivery_id]);

if ($result === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    exit;
}

// Add tracking entry
$supabase->insert('delivery_tracking', [
    'delivery_id' => $delivery_id,
    'status' => $new_status,
    'notes' => 'Status updated by rider',
    'created_by' => $_SESSION['user_id'],
    'created_at' => date('Y-m-d H:i:s')
]);

// Get order info for notification
$order = $supabase->select('orders', '*', ['order_id' => $delivery['order_id']], true);

if ($order) {
    // Notify buyer about status change
    $status_messages = [
        'picked_up' => 'Your order has been picked up by the rider',
        'in_transit' => 'Your order is on the way',
        'delivered' => 'Your order has been delivered',
        'failed' => 'Delivery attempt failed'
    ];
    
    if (isset($status_messages[$new_status])) {
        create_notification(
            $order['buyer_id'],
            'delivery_update',
            $status_messages[$new_status],
            $order['listing_id']
        );
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Status updated successfully',
    'new_status' => $new_status
]);
