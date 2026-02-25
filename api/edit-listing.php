<?php
session_start();
header('Content-Type: application/json');
include '../database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['listing_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing listing ID']);
    exit;
}

$listing_id = (int)$data['listing_id'];

// Verify the user owns this listing
$listing = $supabase->select('listings', 'seller_id,listing_type', ['id' => $listing_id], true);

if (!$listing) {
    echo json_encode(['success' => false, 'message' => 'Listing not found']);
    exit;
}

if ($listing['seller_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'You do not own this listing']);
    exit;
}

// Validate required fields
$required_fields = ['title', 'description', 'price', 'location'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || trim($data[$field]) === '') {
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit;
    }
}

// Prepare update data
$update_data = [
    'title' => trim($data['title']),
    'description' => trim($data['description']),
    'price' => floatval($data['price']),
    'location' => trim($data['location']),
    'updated_at' => date('Y-m-d H:i:s')
];

// Add min_bid_increment for auction listings
if ($listing['listing_type'] === 'BID' && isset($data['min_bid_increment'])) {
    $update_data['min_bid_increment'] = floatval($data['min_bid_increment']);
}

// Update starting_price for auction listings
if ($listing['listing_type'] === 'BID') {
    $update_data['starting_price'] = floatval($data['price']);
}

// Update the listing
$result = $supabase->update('listings', $update_data, ['id' => $listing_id]);

if ($result !== false) {
    echo json_encode(['success' => true, 'message' => 'Listing updated successfully']);
} else {
    $error = $supabase->getLastError();
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update listing',
        'error' => $error
    ]);
}
?>