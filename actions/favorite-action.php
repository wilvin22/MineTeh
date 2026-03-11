<?php
session_start();
header('Content-Type: application/json');
include '../database/supabase.php';
include '../database/notifications_helper.php';

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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

try {
    if ($action === 'add') {
        // Check if already exists
        $existing = $supabase->select('favorites', '*', [
            'user_id' => $user_id,  // Correct column name from favorites table
            'listing_id' => $listing_id
        ]);
        
        if (empty($existing)) {
            $result = $supabase->insert('favorites', [
                'user_id' => $user_id,  // Correct column name from favorites table
                'listing_id' => $listing_id
            ]);
            
            if ($result === false) {
                $error = $supabase->getLastError();
                echo json_encode([
                    'success'=>false, 
                    'message' => 'Failed to add favorite',
                    'error' => $error
                ]);
                exit;
            }
            
            // Get listing and user info for notification
            $listing = $supabase->select('listings', 'title,seller_id', ['id' => $listing_id], true);
            $user = $supabase->select('accounts', 'username', ['account_id' => $user_id], true);
            
            if ($listing && $user && $listing['seller_id'] != $user_id) {
                // Notify seller that someone favorited their listing
                try {
                    $notificationHelper = new NotificationsHelper();
                    $notificationHelper->createNotification(
                        $listing['seller_id'],
                        'listing_sold',
                        'Someone saved your listing!',
                        $user['username'] . ' added "' . $listing['title'] . '" to their favorites',
                        'listing-details.php?id=' . $listing_id
                    );
                } catch (Exception $e) {
                    // Don't fail the favorite action if notification fails
                    error_log("Notification failed: " . $e->getMessage());
                }
            }
        }
        echo json_encode(['success'=>true, 'message' => 'Added to favorites']);
        
    } elseif ($action === 'remove') {
        $result = $supabase->delete('favorites', [
            'user_id' => $user_id,  // Correct column name from favorites table
            'listing_id' => $listing_id
        ]);
        
        // Delete returns an array (even if empty) on success, or false on error
        if ($result !== false) {
            echo json_encode(['success'=>true, 'message' => 'Removed from favorites']);
        } else {
            $error = $supabase->getLastError();
            echo json_encode([
                'success'=>false, 
                'message' => 'Failed to remove favorite', 
                'error' => $error
            ]);
        }
        
    } else {
        echo json_encode(['success'=>false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Favorites action error: " . $e->getMessage());
    echo json_encode([
        'success'=>false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
