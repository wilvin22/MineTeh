<?php
session_start();
include '../database/supabase.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$listing_id = (int)$data['listing_id'];
$favorite = (bool)$data['favorite'];

if ($favorite) {
    // Check if already exists
    $existing = $supabase->select('favorites', '*', [
        'user_id' => $user_id,
        'listing_id' => $listing_id
    ]);
    
    if (empty($existing)) {
        $supabase->insert('favorites', [
            'user_id' => $user_id,
            'listing_id' => $listing_id
        ]);
    }
} else {
    $supabase->delete('favorites', [
        'user_id' => $user_id,
        'listing_id' => $listing_id
    ]);
}

echo json_encode(['success'=>true]);
?>
