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
        case 'update_profile':
            // Get or create user profile
            $existing_profile = $supabase->select('user_profiles', '*', ['user_id' => $user_id], true);
            
            $profile_data = [
                'first_name' => trim($input['first_name'] ?? ''),
                'last_name' => trim($input['last_name'] ?? ''),
                'phone' => trim($input['phone'] ?? ''),
                'date_of_birth' => !empty($input['date_of_birth']) ? $input['date_of_birth'] : null
            ];
            
            if ($existing_profile) {
                $result = $supabase->update('user_profiles', $profile_data, ['user_id' => $user_id]);
            } else {
                $profile_data['user_id'] = $user_id;
                $result = $supabase->insert('user_profiles', $profile_data);
            }
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
            }
            break;
            
        case 'get_profile':
            $profile = $supabase->select('user_profiles', '*', ['user_id' => $user_id], true);
            $account = $supabase->select('accounts', 'username,email', ['account_id' => $user_id], true);
            
            echo json_encode([
                'success' => true, 
                'profile' => $profile,
                'account' => $account
            ]);
            break;
            
        case 'upload_avatar':
            // Handle file upload for profile picture
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
                exit;
            }
            
            $upload_dir = "../uploads/avatars/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $file_type = mime_content_type($_FILES['avatar']['tmp_name']);
            
            if (!in_array($file_type, $allowed_types)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, WEBP, and GIF allowed']);
                exit;
            }
            
            $file_ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $file_name = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $file_path)) {
                // Update profile with new avatar path
                $result = $supabase->update('user_profiles', ['profile_picture' => $file_path], ['user_id' => $user_id]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Avatar updated successfully', 'avatar_url' => $file_path]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update avatar in database']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>