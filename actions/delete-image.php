<?php
session_start();
include "../database/supabase.php";

// Ensure we only output JSON
ob_clean();
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['image_id']) || !is_numeric($input['image_id'])) {
        echo json_encode(['success' => false, 'message' => 'Valid image ID required']);
        exit;
    }

    $image_id = (int)$input['image_id'];

    // Get image details
    $image = $supabase->select('listing_images', '*', ['image_id' => $image_id], true);
    
    if (!$image) {
        echo json_encode(['success' => false, 'message' => 'Image not found']);
        exit;
    }
    
    // Verify ownership by checking the listing
    $listing = $supabase->select('listings', 'seller_id', ['id' => $image['listing_id']], true);
    
    if (!$listing || $listing['seller_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }
    
    // Delete file if it exists
    if (isset($image['image_path']) && file_exists($image['image_path'])) {
        @unlink($image['image_path']); // @ suppresses warnings if file can't be deleted
    }
    
    // Delete record from database
    $result = $supabase->delete('listing_images', ['image_id' => $image_id]);
    
    echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
