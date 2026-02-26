<?php
session_start();
header('Content-Type: application/json');
include '../database/supabase.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['listing_id']) || !isset($data['action'])) {
    echo json_encode(['success'=>false, 'message' => 'Missing required fields']);
    exit;
}

$listing_id = (int)$data['listing_id'];
$action = $data['action'];

if ($action === 'add') {
    // Check if already exists
    $existing = $supabase->select('favorites', '*', [
        'user_id' => $user_id,
        'listing_id' => $listing_id
    ]);
    
    if (empty($existing)) {
        $result = $supabase->insert('favorites', [
            'user_id' => $user_id,
            'listing_id' => $listing_id
        ]);
        
        if ($result === false) {
            echo json_encode(['success'=>false, 'message' => 'Failed to add favorite']);
            exit;
        }
    }
    echo json_encode(['success'=>true, 'message' => 'Added to favorites']);
} elseif ($action === 'remove') {
    $result = $supabase->delete('favorites', [
        'user_id' => $user_id,
        'listing_id' => $listing_id
    ]);
    
    // Delete returns an array (even if empty) on success, or false on error
    if ($result !== false) {
        echo json_encode(['success'=>true, 'message' => 'Removed from favorites']);
    } else {
        $error = $supabase->getLastError();
        echo json_encode(['success'=>false, 'message' => 'Failed to remove favorite', 'error' => $error]);
    }
    exit;
} else {
    echo json_encode(['success'=>false, 'message' => 'Invalid action']);
}
?>
