<?php
session_start();
header('Content-Type: application/json');
include '../database/supabase.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['listing_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$listing_id = (int)$data['listing_id'];
$action = $data['action'];

$result = false;

switch ($action) {
    case 'add':
        // Verify listing exists and is FIXED type (only fixed price items can be added to cart)
        $listing = $supabase->select('listings', 'listing_type,seller_id', ['id' => $listing_id], true);

        if (!$listing) {
            echo json_encode(['success' => false, 'message' => 'Listing not found']);
            exit;
        }

        if ($listing['listing_type'] !== 'FIXED') {
            echo json_encode(['success' => false, 'message' => 'Only fixed price items can be added to cart']);
            exit;
        }

        if ($listing['seller_id'] == $user_id) {
            echo json_encode(['success' => false, 'message' => 'Cannot add your own listing to cart']);
            exit;
        }

        // Check if cart table exists by trying to query it
        try {
            $existing = $supabase->select('cart', '*', [
                'user_id' => $user_id,  // Correct column name from cart table
                'listing_id' => $listing_id
            ]);
            
            if ($existing === false) {
                // Table might not exist
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cart table not found. Please run add_cart_table.sql in Supabase SQL Editor',
                    'error' => $supabase->getLastError()
                ]);
                exit;
            }
            
            if (!empty($existing)) {
                echo json_encode(['success' => false, 'message' => 'Item already in cart']);
                exit;
            }
            
            // Add to cart
            $result = $supabase->insert('cart', [
                'user_id' => $user_id,  // Correct column name from cart table
                'listing_id' => $listing_id,
                'quantity' => 1
            ]);
            
            if ($result === false) {
                $error = $supabase->getLastError();
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to add to cart',
                    'error' => $error
                ]);
                exit;
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ]);
            exit;
        }
        break;
        
    case 'remove':
        // For remove action, we don't need to check listing type or seller
        // Just remove the item from the user's cart
        try {
            $result = $supabase->delete('cart', [
                'user_id' => $user_id,  // Correct column name from cart table
                'listing_id' => $listing_id
            ]);
            
            if ($result === false) {
                $error = $supabase->getLastError();
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to remove from cart',
                    'error' => $error
                ]);
                exit;
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Error removing from cart: ' . $e->getMessage()
            ]);
            exit;
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

if ($result !== false) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Operation failed']);
}
?>
