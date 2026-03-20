<?php
session_start();

// Block admin access to user pages
require_once __DIR__ . '/../includes/block_admin_access.php';

// Set timezone to match your local timezone (adjust as needed)
date_default_timezone_set('Asia/Manila'); // Change this to your timezone

include "../database/supabase.php";

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in");
}

// Check if user is restricted
$user_status = isset($_SESSION['user_status']) ? $_SESSION['user_status'] : 'active';
if ($user_status === 'restricted') {
    // Get user details to check restriction expiry
    $user = $supabase->select('accounts', 'restriction_until, status_reason', ['account_id' => $_SESSION['user_id']], true);
    
    $restriction_until = isset($user['restriction_until']) ? $user['restriction_until'] : null;
    $is_expired = false;
    
    if ($restriction_until && strtotime($restriction_until) <= time()) {
        // Restriction expired, reactivate user
        $supabase->update('accounts', [
            'user_status' => 'active',
            'restriction_until' => null,
            'status_reason' => null
        ], ['account_id' => $_SESSION['user_id']]);
        $_SESSION['user_status'] = 'active';
        $is_expired = true;
    }
    
    if (!$is_expired) {
        $reason = isset($user['status_reason']) ? $user['status_reason'] : 'No reason provided';
        $until_text = $restriction_until ? ' until ' . date('F j, Y g:i A', strtotime($restriction_until)) : ' permanently';
        
        die('
        <!DOCTYPE html>
        <html>
        <head>
            <title>Account Restricted</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                }
                .restriction-notice {
                    background: white;
                    padding: 40px;
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                    max-width: 500px;
                    text-align: center;
                }
                .restriction-icon {
                    font-size: 60px;
                    margin-bottom: 20px;
                }
                h1 {
                    color: #e74c3c;
                    margin-bottom: 15px;
                }
                .reason {
                    background: #fff3cd;
                    padding: 15px;
                    border-radius: 8px;
                    margin: 20px 0;
                    color: #856404;
                }
                .back-btn {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 12px 24px;
                    background: #667eea;
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                }
            </style>
        </head>
        <body>
            <div class="restriction-notice">
                <div class="restriction-icon">⚠️</div>
                <h1>Account Restricted</h1>
                <p>Your account has been restricted' . $until_text . '.</p>
                <div class="reason">
                    <strong>Reason:</strong><br>
                    ' . htmlspecialchars($reason) . '
                </div>
                <p>You cannot create listings while your account is restricted.</p>
                <a href="homepage.php" class="back-btn">Back to Homepage</a>
            </div>
        </body>
        </html>
        ');
    }
}

// Check if we're in edit mode
$edit_mode = false;
$listing_data = null;
$listing_images = [];

if (isset($_GET['edit'])) {
    $edit_listing_id = (int)$_GET['edit'];
    
    // Get listing data for editing
    $listing_data = $supabase->select('listings', '*', ['id' => $edit_listing_id], true);
    
    if (!$listing_data) {
        die("Listing not found");
    }
    
    // Verify ownership
    if ($listing_data['seller_id'] != $_SESSION['user_id']) {
        die("You can only edit your own listings");
    }
    
    $edit_mode = true;
    
    // Get existing images
    $listing_images = $supabase->select('listing_images', '*', ['listing_id' => $edit_listing_id]);
}

// Get categories from database
$categories = $supabase->select('categories', '*', ['is_active' => true]);
if (empty($categories)) {
    // Fallback to default categories if table doesn't exist yet
    $categories = [
        ['category_id' => 1, 'category_slug' => 'electronics', 'category_name' => 'Electronics', 'category_icon' => '📱'],
        ['category_id' => 2, 'category_slug' => 'vehicle', 'category_name' => 'Vehicles', 'category_icon' => '🚗'],
        ['category_id' => 3, 'category_slug' => 'property', 'category_name' => 'Property', 'category_icon' => '🏠'],
        ['category_id' => 4, 'category_slug' => 'fashion', 'category_name' => 'Fashion', 'category_icon' => '👕'],
        ['category_id' => 5, 'category_slug' => 'home', 'category_name' => 'Home & Garden', 'category_icon' => '🛋️'],
        ['category_id' => 6, 'category_slug' => 'sports', 'category_name' => 'Sports', 'category_icon' => '⚽'],
        ['category_id' => 7, 'category_slug' => 'books', 'category_name' => 'Books', 'category_icon' => '📚'],
        ['category_id' => 8, 'category_slug' => 'other', 'category_name' => 'Other', 'category_icon' => '📦']
    ];
}

$success_message = '';
$error_message = '';

if (isset($_POST['create_listing'])) {
    $seller_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $starting_price = $price;
    $current_price = $price;
    $category = $_POST['category'];
    $location = trim($_POST['location']);
    $listing_type = $_POST['listing_type'];

    // Validate photos are uploaded for new listings
    if (!$edit_mode && (empty($_FILES['photos']['name'][0]) || $_FILES['photos']['error'][0] === UPLOAD_ERR_NO_FILE)) {
        $error_message = "At least one photo is required for new listings.";
    } else {

    // Get category_id from category_slug
    $category_id = null;
    foreach ($categories as $cat) {
        if ($cat['category_slug'] === $category) {
            $category_id = $cat['category_id'];
            break;
        }
    }

    $listingData = [
        'title' => $title,
        'description' => $description,
        'price' => $price,
        'starting_price' => $starting_price,
        'current_price' => $current_price,
        'listing_type' => $listing_type,
        'location' => $location,
        'category' => $category, // Keep for backward compatibility
        'category_id' => $category_id, // New category system
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if (!$edit_mode) {
        $listingData['seller_id'] = $seller_id;
        $listingData['status'] = 'active';
    } else {
        // In edit mode, handle status toggle
        $listingData['status'] = isset($_POST['listing_status']) && $_POST['listing_status'] === 'active' ? 'active' : 'inactive';
    }

    // Only BID listings have end time and min bid increment
    if ($listing_type === "BID") {
        // Handle min bid increment
        $min_bid_increment = isset($_POST['min_bid_increment']) ? floatval($_POST['min_bid_increment']) : 1.00;
        $listingData['min_bid_increment'] = $min_bid_increment;
        
        // Handle end time - preserve original end time in edit mode unless duration is changed
        if ($edit_mode && !empty($listing_data['end_time'])) {
            // In edit mode, keep the original end time unless user changes duration
            $listingData['end_time'] = $listing_data['end_time'];
            
            // Only update end time if user explicitly changes duration
            if (!empty($_POST['custom-bid-end-time'])) {
                $bid_end_time = date('Y-m-d H:i:s', strtotime($_POST['custom-bid-end-time']));
                $listingData['end_time'] = $bid_end_time;
            } elseif (!empty($_POST['bid-end-time'])) {
                // Check if the selected duration is different from current
                $current_end_time = new DateTime($listing_data['end_time']);
                $current_created_time = new DateTime($listing_data['created_at']);
                $current_duration_diff = $current_created_time->diff($current_end_time);
                $current_total_days = $current_duration_diff->days;
                
                $new_duration_days = intval($_POST['bid-end-time']);
                
                // Only update if duration actually changed
                if ($new_duration_days != $current_total_days) {
                    $bid_end_time = date('Y-m-d H:i:s', strtotime("+$new_duration_days days"));
                    $listingData['end_time'] = $bid_end_time;
                }
            }
        } else {
            // New listing - calculate end time normally
            if (!empty($_POST['custom-bid-end-time'])) {
                $bid_end_time = date('Y-m-d H:i:s', strtotime($_POST['custom-bid-end-time']));
            } elseif (!empty($_POST['bid-end-time'])) {
                $days = intval($_POST['bid-end-time']);
                $bid_end_time = date('Y-m-d H:i:s', strtotime("+$days days"));
            } else {
                $bid_end_time = date('Y-m-d H:i:s', strtotime('+7 days'));
            }
            $listingData['end_time'] = $bid_end_time;
        }
    }

    // Insert listing
    if ($edit_mode) {
        // Update existing listing
        $result = $supabase->update('listings', $listingData, ['id' => $edit_listing_id]);
        $listing_id = $edit_listing_id;
    } else {
        // Insert new listing
        $result = $supabase->insert('listings', $listingData);
        $listing_id = $result && !empty($result[0]) ? $result[0]['id'] : null;
    }
    if ($result && $listing_id) {
        $upload_dir = __DIR__ . "/uploads/";
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $max_photos = 5;
        $uploaded = 0;
        $upload_errors = []; // Track upload errors for debugging

        if (!empty($_FILES['photos']['name'][0])) {
            $allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            
            foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                if ($uploaded >= $max_photos) break;
                
                if ($_FILES['photos']['error'][$key] !== 0 || !is_uploaded_file($tmp_name)) {
                    $upload_errors[] = "File $key: Upload error code " . $_FILES['photos']['error'][$key];
                    continue;
                }

                $mime_type = finfo_file($finfo, $tmp_name);
                if (!in_array($mime_type, $allowed_mime)) {
                    $upload_errors[] = "File $key: Invalid MIME type $mime_type";
                    continue;
                }

                $file_ext = pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION);
                $file_name = uniqid('img_', true) . '.' . $file_ext;
                $file_path = __DIR__ . "/uploads/" . $file_name;

                if (move_uploaded_file($tmp_name, $file_path)) {
                    // Save only the relative path without ../ for database
                    $db_path = "uploads/" . $file_name;
                    
                    $supabase->insert('listing_images', [
                        'listing_id' => $listing_id,
                        'image_path' => $db_path
                    ]);
                    $uploaded++;
                } else {
                    $upload_errors[] = "File $key: move_uploaded_file failed for $file_name. Check folder permissions.";
                }
            }
            finfo_close($finfo);
            
            // Log errors if any occurred
            if (!empty($upload_errors)) {
                error_log("Image upload errors: " . implode("; ", $upload_errors));
            }
        }

        $success_message = $edit_mode ? "Listing updated successfully!" : "Listing created successfully!";
        header("Location: listing-details.php?id=$listing_id");
        exit;
    } else {
        $error_message = $edit_mode ? "Error updating listing. Please try again." : "Error creating listing. Please try again.";
    }
    } // Close the photo validation else block
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit Listing' : 'Create Listing'; ?> - MineTeh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f7;
            min-height: 100vh;
        }

        /* Top bar */
        .top-bar {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 0 40px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }

        .back-link:hover { color: #945a9b; }

        .top-bar-title {
            font-size: 17px;
            font-weight: 600;
            color: #333;
        }

        .top-bar-actions {
            display: flex;
            gap: 12px;
        }

        /* Page layout */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 40px;
        }

        .create-listing-container { width: 100%; }

        /* Two-column layout */
        .listing-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: white;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            overflow: hidden;
        }

        /* Left column: photos */
        .col-photos {
            border-right: 1px solid #e0e0e0;
            padding: 32px;
            background: #fafafa;
        }

        /* Right column: all other sections stacked */
        .col-details {
            display: flex;
            flex-direction: column;
        }

        .form-section {
            padding: 28px 32px;
            border-bottom: 1px solid #f0f0f0;
        }

        .form-section:last-child { border-bottom: none; }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .section-icon {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #945a9b, #6a406e);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #444;
            font-size: 13px;
        }

        .form-group label .required {
            color: #e74c3c;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            font-family: inherit;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #945a9b;
            box-shadow: 0 0 0 3px rgba(148, 90, 155, 0.08);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            line-height: 1.6;
        }

        .form-hint {
            font-size: 11px;
            color: #aaa;
            margin-top: 4px;
        }

        /* Upload Info Banner */
        .upload-info-banner {
            background: linear-gradient(135deg, #e3f2fd 0%, #e1f5fe 100%);
            border: 2px solid #2196F3;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .info-icon {
            font-size: 28px;
            flex-shrink: 0;
        }

        .info-content {
            flex: 1;
        }

        .info-content strong {
            color: #1976D2;
            font-size: 16px;
            display: block;
            margin-bottom: 10px;
        }

        .info-content ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-content li {
            padding: 6px 0;
            color: #555;
            font-size: 14px;
            line-height: 1.5;
        }

        .info-content li strong {
            display: inline;
            color: #1976D2;
            font-size: 14px;
        }

        /* Photo Upload Section */
        .photo-upload-area {
            border: 3px dashed #ddd;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: #fafafa;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .photo-upload-area:hover {
            border-color: #945a9b;
            background: #f8f4f9;
        }

        .photo-upload-area.drag-over {
            border-color: #945a9b;
            background: #f0e6f3;
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .upload-text {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
        }

        .upload-hint {
            font-size: 13px;
            color: #999;
            margin-bottom: 15px;
        }

        .upload-counter {
            display: inline-block;
            background: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            color: #666;
            border: 2px solid #ddd;
            margin-top: 10px;
        }

        .upload-counter #currentCount {
            color: #945a9b;
            font-size: 18px;
        }

        .upload-counter.limit-reached {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }

        .upload-counter.limit-reached #currentCount {
            color: #dc3545;
        }

        #preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .preview-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .remove-photo {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 28px;
            height: 28px;
            background: rgba(231, 76, 60, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .remove-photo:hover {
            background: #c0392b;
            transform: scale(1.1);
        }

        /* Listing Type Cards */
        .listing-type-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .type-card {
            position: relative;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .type-card:hover {
            border-color: #945a9b;
            background: #f8f4f9;
        }

        .type-card input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .type-card input[type="radio"]:checked + .type-card-content {
            border-color: #945a9b;
        }

        .type-card.selected {
            border-color: #945a9b;
            background: #f8f4f9;
        }

        .type-card-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .type-card-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .type-card-desc {
            font-size: 13px;
            color: #666;
        }

        /* Bid Duration */
        .bid-duration-options {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 10px;
        }

        .duration-option {
            padding: 18px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .duration-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #945a9b, #6a406e);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .duration-option:hover {
            border-color: #945a9b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(148, 90, 155, 0.2);
        }

        .duration-option.selected {
            border-color: #945a9b;
            background: linear-gradient(135deg, #945a9b, #6a406e);
            color: white;
            box-shadow: 0 4px 15px rgba(148, 90, 155, 0.3);
        }

        .duration-option input[type="radio"] {
            display: none;
        }

        .duration-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 4px;
            position: relative;
            z-index: 1;
        }

        .duration-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 1;
        }

        .duration-option.selected .duration-value,
        .duration-option.selected .duration-label {
            color: white;
        }

        /* Custom Date Picker */
        #custom-date-container {
            margin-top: 20px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .custom-date-wrapper {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f4f9 0%, #f0e6f3 100%);
            border: 2px solid #945a9b;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(148, 90, 155, 0.1);
        }

        .date-icon {
            font-size: 48px;
            flex-shrink: 0;
        }

        .date-input-group {
            flex: 1;
        }

        .date-input-label {
            display: block;
            font-weight: 600;
            color: #6a406e;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .custom-datetime-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #945a9b;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            background: white;
            color: #333;
            transition: all 0.3s ease;
        }

        .custom-datetime-input:focus {
            outline: none;
            border-color: #6a406e;
            box-shadow: 0 0 0 3px rgba(148, 90, 155, 0.2);
        }

        .custom-datetime-input::-webkit-calendar-picker-indicator {
            cursor: pointer;
            filter: invert(35%) sepia(50%) saturate(500%) hue-rotate(260deg);
        }

        .date-hint {
            font-size: 12px;
            color: #6a406e;
            margin-top: 6px;
            font-style: italic;
        }

        /* Status Toggle Styles */
        .status-toggle-container {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            border: 2px solid #e9ecef;
        }

        .status-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .status-label {
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }

        .status-value {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .status-value.status-active {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-value.status-inactive {
            background: #f8d7da;
            color: #842029;
        }

        .status-toggle {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #dc3545;
            transition: 0.4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        .toggle-switch input:checked + .toggle-slider {
            background-color: #28a745;
        }

        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        .toggle-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .toggle-text-active {
            color: #28a745;
        }

        .toggle-text-inactive {
            color: #dc3545;
        }

        .status-description {
            font-size: 13px;
            color: #666;
            font-style: italic;
        }

        /* Submit Button */
        .submit-section {
            padding: 24px 32px;
            background: #fafafa;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
            grid-column: 1 / -1;
        }

        .submit-btn {
            padding: 12px 32px;
            background: linear-gradient(135deg, #945a9b, #6a406e);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(148, 90, 155, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(148, 90, 155, 0.4);
        }

        .submit-btn:active { transform: translateY(0); }

        .cancel-btn {
            padding: 12px 24px;
            background: white;
            color: #666;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .cancel-btn:hover {
            border-color: #999;
            color: #333;
        }   cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .cancel-btn:hover {
            border-color: #999;
            color: #333;
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Location Autocomplete Styles */
        .location-input-wrapper {
            position: relative;
        }

        .location-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: none;
        }

        .location-suggestions.active {
            display: block;
        }

        .location-suggestion-item {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .location-suggestion-item:last-child {
            border-bottom: none;
        }

        .location-suggestion-item:hover {
            background: #f8f9fa;
        }

        .location-suggestion-item.selected {
            background: #e9ecef;
        }

        .location-icon {
            color: #945a9b;
            font-size: 16px;
        }

        .location-text {
            flex: 1;
        }

        .location-name {
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .location-details {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }

        .location-loading {
            padding: 15px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        .location-no-results {
            padding: 15px;
            text-align: center;
            color: #999;
            font-size: 14px;
        }

        .location-hint {
            margin-top: 8px;
        }

        #locationInput:focus {
            border-color: #945a9b;
            box-shadow: 0 0 0 3px rgba(148, 90, 155, 0.1);
        }

        @media (max-width: 900px) {
            .listing-form {
                grid-template-columns: 1fr;
            }

            .col-photos {
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }

            .container {
                padding: 20px 16px;
            }

            .top-bar {
                padding: 0 16px;
            }

            .bid-duration-options {
                grid-template-columns: repeat(2, 1fr);
            }

            .submit-section {
                flex-direction: row;
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <a href="javascript:history.back()" class="back-link">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <span class="top-bar-title"><?php echo $edit_mode ? '✏️ Edit Listing' : '✨ Create New Listing'; ?></span>
        <div style="width:80px;"></div><!-- spacer to center title -->
    </div>

    <div class="container">
        <div class="create-listing-container">

            <?php if ($success_message): ?>
                <div class="message success" style="margin-bottom:20px;"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="message error" style="margin-bottom:20px;"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data" class="listing-form">
                <!-- Left column: Photos -->
                <div class="col-photos">
                    <div class="section-header">
                        <div class="section-icon">📸</div>
                        <div class="section-title">Product Photos</div>
                    </div>
                    
                    <!-- Upload Limit Info Banner -->
                    <div class="upload-info-banner">
                        <div class="info-icon">ℹ️</div>
                        <div class="info-content">
                            <strong>Photo Requirements:</strong>
                            <ul>
                                <li>📷 <strong>Maximum 5 photos</strong> per listing</li>
                                <li>✅ Accepted formats: JPG, PNG, WEBP, GIF</li>
                                <li>❌ Videos are not supported</li>
                                <li>💡 First photo will be the main display image</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="photo-upload-area" onclick="document.getElementById('photos').click()">
                        <div class="upload-icon">🖼️</div>
                        <div class="upload-text">
                            Click to upload photos <span class="required">*</span>
                        </div>
                        <div class="upload-hint">or drag and drop images here</div>
                        <div class="upload-counter" id="uploadCounter">
                            <span id="currentCount">0</span> / 5 photos selected
                        </div>
                    </div>
                    <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden <?php echo !$edit_mode ? 'required' : ''; ?>>
                    
                    <?php if ($edit_mode && !empty($listing_images)): ?>
                    <div id="existing-images" style="margin-top: 20px;">
                        <h4 style="margin-bottom: 15px; color: #666;">Current Images:</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px;">
                            <?php foreach ($listing_images as $image): 
                                // Fix image path - remove ../ if present and ensure it points to uploads
                                $img_path = $image['image_path'] ?? '';
                                $img_path = str_replace('../uploads/', 'uploads/', $img_path);
                            ?>
                            <div class="preview-item">
                                <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Listing image">
                                <button type="button" class="remove-photo" onclick="removeExistingImage(<?php echo (int)$image['image_id']; ?>, this)">×</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div id="preview"></div>
                </div><!-- /.col-photos -->

                <!-- Right column: all other sections -->
                <div class="col-details">

                <!-- Basic Information -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">📝</div>
                        <div class="section-title">Basic Information</div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Title <span class="required">*</span></label>
                            <input type="text" name="title" placeholder="e.g., iPhone 13 Pro Max 256GB" value="<?php echo $edit_mode ? htmlspecialchars($listing_data['title'] ?? '') : ''; ?>" required>
                            <div class="form-hint">Be specific and descriptive</div>
                        </div>

                        <div class="form-group full-width">
                            <label>Description <span class="required">*</span></label>
                            <textarea name="description" placeholder="Describe your item in detail..." required><?php echo $edit_mode ? htmlspecialchars($listing_data['description'] ?? '') : ''; ?></textarea>
                            <div class="form-hint">Include condition, features, and any defects</div>
                        </div>

                        <div class="form-group">
                            <label>Category <span class="required">*</span></label>
                            <select name="category" required>
                                <option value="">Select category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_slug']; ?>" 
                                        <?php 
                                        $selected = false;
                                        if ($edit_mode && $listing_data) {
                                            // Check both category_id and category fields for backward compatibility
                                            if (isset($listing_data['category_id']) && isset($category['category_id']) && 
                                                (int)$listing_data['category_id'] === (int)$category['category_id']) {
                                                $selected = true;
                                            } elseif (isset($listing_data['category']) && isset($category['category_slug']) && 
                                                     trim($listing_data['category']) === trim($category['category_slug'])) {
                                                $selected = true;
                                            }
                                        }
                                        echo $selected ? 'selected' : '';
                                        ?>>
                                    <?php echo $category['category_icon']; ?> <?php echo $category['category_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Location <span class="required">*</span></label>
                            <div class="location-input-wrapper" style="position: relative;">
                                <input type="text" 
                                       id="locationInput"
                                       name="location" 
                                       placeholder="Start typing your location (e.g., Manila, Quezon City, Cebu...)" 
                                       value="<?php echo $edit_mode ? htmlspecialchars($listing_data['location'] ?? '') : ''; ?>" 
                                       autocomplete="off"
                                       required>
                                <div id="locationSuggestions" class="location-suggestions"></div>
                                <div class="location-hint">
                                    <span style="font-size: 12px; color: #666;">
                                        💡 Type your city, municipality, or barangay in the Philippines
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Listing Type -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">💰</div>
                        <div class="section-title">Pricing & Type</div>
                    </div>

                    <div class="form-group">
                        <label>Listing Type <span class="required">*</span></label>
                        <div class="listing-type-cards">
                            <label class="type-card" id="fixed-card">
                                <input type="radio" name="listing_type" value="FIXED" <?php echo (!$edit_mode || ($listing_data['listing_type'] ?? 'FIXED') == 'FIXED') ? 'checked' : ''; ?>>
                                <div class="type-card-content">
                                    <div class="type-card-icon">🏷️</div>
                                    <div class="type-card-title">Fixed Price</div>
                                    <div class="type-card-desc">Sell at a set price</div>
                                </div>
                            </label>

                            <label class="type-card" id="bid-card">
                                <input type="radio" name="listing_type" value="BID" <?php echo ($edit_mode && ($listing_data['listing_type'] ?? '') == 'BID') ? 'checked' : ''; ?>>
                                <div class="type-card-content">
                                    <div class="type-card-icon">⚡</div>
                                    <div class="type-card-title">Auction</div>
                                    <div class="type-card-desc">Let buyers bid</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label id="price-label">Price <span class="required">*</span></label>
                        <input type="number" name="price" placeholder="0.00" step="0.01" min="0" value="<?php echo $edit_mode ? ($listing_data['price'] ?? '') : ''; ?>" required>
                        <div class="form-hint" id="price-hint">Set your selling price</div>
                    </div>

                    <div class="form-group" id="min-bid-increment-group" style="display: none;">
                        <label>Minimum Bid Increment <span class="required">*</span></label>
                        <input type="number" name="min_bid_increment" id="min-bid-increment" placeholder="0.00" step="0.01" min="0.01" value="<?php echo $edit_mode ? ($listing_data['min_bid_increment'] ?? '1.00') : '1.00'; ?>">
                        <div class="form-hint">Minimum amount each bid must increase (e.g., ₱1.00)</div>
                    </div>

                    <div class="form-group" id="bid-duration-group" style="display: none;">
                        <label>Auction Duration <span class="required">*</span></label>
                        <?php
                        // Calculate current duration for edit mode
                        $selected_duration = '3'; // default
                        $custom_end_time = '';
                        
                        if ($edit_mode && !empty($listing_data['end_time'])) {
                            $end_time = new DateTime($listing_data['end_time']);
                            $created_time = new DateTime($listing_data['created_at']);
                            $duration_diff = $created_time->diff($end_time);
                            $total_days = $duration_diff->days;
                            
                            // Determine which duration option was selected
                            if ($total_days == 1) {
                                $selected_duration = '1';
                            } elseif ($total_days == 3) {
                                $selected_duration = '3';
                            } elseif ($total_days == 7) {
                                $selected_duration = '7';
                            } else {
                                $selected_duration = 'custom';
                                $custom_end_time = $end_time->format('Y-m-d\TH:i');
                            }
                        }
                        ?>
                        <div class="bid-duration-options">
                            <label class="duration-option">
                                <input type="radio" name="bid-end-time" value="1" <?php echo ($selected_duration === '1') ? 'checked' : ''; ?>>
                                <div class="duration-value">1</div>
                                <div class="duration-label">Day</div>
                            </label>
                            <label class="duration-option">
                                <input type="radio" name="bid-end-time" value="3" <?php echo ($selected_duration === '3') ? 'checked' : ''; ?>>
                                <div class="duration-value">3</div>
                                <div class="duration-label">Days</div>
                            </label>
                            <label class="duration-option">
                                <input type="radio" name="bid-end-time" value="7" <?php echo ($selected_duration === '7') ? 'checked' : ''; ?>>
                                <div class="duration-value">7</div>
                                <div class="duration-label">Days</div>
                            </label>
                            <label class="duration-option">
                                <input type="radio" name="bid-end-time" value="custom" <?php echo ($selected_duration === 'custom') ? 'checked' : ''; ?>>
                                <div class="duration-value">📅</div>
                                <div class="duration-label">Custom</div>
                            </label>
                        </div>
                        
                        <div id="custom-date-container" style="display: <?php echo ($selected_duration === 'custom') ? 'block' : 'none'; ?>;">
                            <div class="custom-date-wrapper">
                                <div class="date-icon">🗓️</div>
                                <div class="date-input-group">
                                    <label class="date-input-label">Select End Date & Time</label>
                                    <input type="datetime-local" name="custom-bid-end-time" id="custom-bid-time" class="custom-datetime-input" value="<?php echo $custom_end_time; ?>">
                                    <div class="date-hint">Choose when your auction should end</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($edit_mode): ?>
                <!-- Listing Status Section (Edit Mode Only) -->
                <div class="form-section">
                    <h3 class="section-title">📊 Listing Status</h3>
                    <div class="status-toggle-container">
                        <div class="status-info">
                            <div class="status-label">Current Status:</div>
                            <div class="status-value <?php echo $listing_data['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo strtoupper($listing_data['status']); ?>
                            </div>
                        </div>
                        <div class="status-toggle">
                            <label class="toggle-switch">
                                <input type="checkbox" name="listing_status" value="active" <?php echo $listing_data['status'] === 'active' ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <div class="toggle-label">
                                <span class="toggle-text-active">Active</span>
                                <span class="toggle-text-inactive">Disabled</span>
                            </div>
                        </div>
                        <div class="status-description">
                            <?php if ($listing_data['status'] === 'active'): ?>
                                Your listing is visible to buyers
                            <?php else: ?>
                                Your listing is hidden from buyers
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Submit Section -->
                <div class="submit-section">
                    <a href="homepage.php" class="cancel-btn">Cancel</a>
                    <button type="submit" name="create_listing" class="submit-btn">
                        <?php echo $edit_mode ? '💾 Update Listing' : '🚀 Publish Listing'; ?>
                    </button>
                </div>
            </div><!-- /.col-details -->
            </form>
        </div>
    </div>

    <script>
        // ===== LOCATION AUTOCOMPLETE =====
        const locationInput = document.getElementById('locationInput');
        const suggestionsContainer = document.getElementById('locationSuggestions');
        let debounceTimer;
        let selectedIndex = -1;
        let suggestions = [];

        // Philippine locations database (common cities and provinces)
        const philippineLocations = [
            // NCR
            'Manila, Metro Manila', 'Quezon City, Metro Manila', 'Makati, Metro Manila', 
            'Pasig, Metro Manila', 'Taguig, Metro Manila', 'Mandaluyong, Metro Manila',
            'Pasay, Metro Manila', 'Parañaque, Metro Manila', 'Las Piñas, Metro Manila',
            'Muntinlupa, Metro Manila', 'Caloocan, Metro Manila', 'Malabon, Metro Manila',
            'Navotas, Metro Manila', 'Valenzuela, Metro Manila', 'Marikina, Metro Manila',
            'San Juan, Metro Manila', 'Pateros, Metro Manila',
            
            // Luzon
            'Baguio City, Benguet', 'Dagupan City, Pangasinan', 'San Fernando, Pampanga',
            'Angeles City, Pampanga', 'Olongapo City, Zambales', 'Batangas City, Batangas',
            'Lipa City, Batangas', 'Lucena City, Quezon', 'Naga City, Camarines Sur',
            'Legazpi City, Albay', 'Cabanatuan City, Nueva Ecija', 'San Jose, Nueva Ecija',
            'Tarlac City, Tarlac', 'Urdaneta, Pangasinan', 'Vigan City, Ilocos Sur',
            'Laoag City, Ilocos Norte', 'Tuguegarao, Cagayan', 'Cauayan, Isabela',
            'Santiago City, Isabela', 'Antipolo, Rizal', 'Bacoor, Cavite', 'Dasmariñas, Cavite',
            'Imus, Cavite', 'Cavite City, Cavite', 'Tagaytay, Cavite', 'Biñan, Laguna',
            'Santa Rosa, Laguna', 'Calamba, Laguna', 'San Pablo, Laguna',
            
            // Visayas
            'Cebu City, Cebu', 'Mandaue City, Cebu', 'Lapu-Lapu City, Cebu', 'Talisay, Cebu',
            'Toledo City, Cebu', 'Iloilo City, Iloilo', 'Bacolod City, Negros Occidental',
            'Dumaguete City, Negros Oriental', 'Tacloban City, Leyte', 'Ormoc City, Leyte',
            'Calbayog City, Samar', 'Catbalogan, Samar', 'Tagbilaran City, Bohol',
            'Roxas City, Capiz', 'Kalibo, Aklan', 'Boracay, Aklan',
            
            // Mindanao
            'Davao City, Davao del Sur', 'Cagayan de Oro, Misamis Oriental', 
            'General Santos City, South Cotabato', 'Zamboanga City, Zamboanga del Sur',
            'Butuan City, Agusan del Norte', 'Iligan City, Lanao del Norte',
            'Cotabato City, Maguindanao', 'Dipolog City, Zamboanga del Norte',
            'Pagadian City, Zamboanga del Sur', 'Koronadal City, South Cotabato',
            'Kidapawan City, Cotabato', 'Tagum City, Davao del Norte',
            'Mati City, Davao Oriental', 'Digos City, Davao del Sur'
        ];

        locationInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            
            if (query.length < 2) {
                hideSuggestions();
                return;
            }
            
            debounceTimer = setTimeout(() => {
                searchLocations(query);
            }, 300);
        });

        function searchLocations(query) {
            // Filter Philippine locations
            const filtered = philippineLocations.filter(location => 
                location.toLowerCase().includes(query.toLowerCase())
            );
            
            suggestions = filtered.slice(0, 8); // Limit to 8 results
            
            if (suggestions.length > 0) {
                displaySuggestions(suggestions);
            } else {
                // Fallback to Nominatim API for more locations
                fetchFromNominatim(query);
            }
        }

        function fetchFromNominatim(query) {
            showLoading();
            
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ', Philippines')}&limit=8&addressdetails=1`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        suggestions = data.map(item => {
                            const address = item.address || {};
                            const city = address.city || address.town || address.village || address.municipality || '';
                            const province = address.state || address.province || '';
                            return city && province ? `${city}, ${province}` : item.display_name;
                        });
                        displaySuggestions(suggestions);
                    } else {
                        showNoResults();
                    }
                })
                .catch(error => {
                    console.error('Location search error:', error);
                    showNoResults();
                });
        }

        function displaySuggestions(items) {
            suggestionsContainer.innerHTML = '';
            selectedIndex = -1;
            
            items.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'location-suggestion-item';
                div.dataset.index = index;
                
                const parts = item.split(',');
                const city = parts[0]?.trim() || '';
                const province = parts[1]?.trim() || '';
                
                div.innerHTML = `
                    <span class="location-icon">📍</span>
                    <div class="location-text">
                        <div class="location-name">${city}</div>
                        ${province ? `<div class="location-details">${province}</div>` : ''}
                    </div>
                `;
                
                div.addEventListener('click', () => selectSuggestion(item));
                div.addEventListener('mouseenter', () => {
                    selectedIndex = index;
                    updateSelection();
                });
                
                suggestionsContainer.appendChild(div);
            });
            
            suggestionsContainer.classList.add('active');
        }

        function showLoading() {
            suggestionsContainer.innerHTML = '<div class="location-loading">🔍 Searching locations...</div>';
            suggestionsContainer.classList.add('active');
        }

        function showNoResults() {
            suggestionsContainer.innerHTML = '<div class="location-no-results">No locations found. Try a different search.</div>';
            suggestionsContainer.classList.add('active');
        }

        function selectSuggestion(location) {
            locationInput.value = location;
            hideSuggestions();
            locationInput.focus();
        }

        function hideSuggestions() {
            suggestionsContainer.classList.remove('active');
            suggestionsContainer.innerHTML = '';
            selectedIndex = -1;
        }

        function updateSelection() {
            const items = suggestionsContainer.querySelectorAll('.location-suggestion-item');
            items.forEach((item, index) => {
                item.classList.toggle('selected', index === selectedIndex);
            });
        }

        // Keyboard navigation
        locationInput.addEventListener('keydown', function(e) {
            const items = suggestionsContainer.querySelectorAll('.location-suggestion-item');
            
            if (!suggestionsContainer.classList.contains('active') || items.length === 0) {
                return;
            }
            
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    updateSelection();
                    items[selectedIndex]?.scrollIntoView({ block: 'nearest' });
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, 0);
                    updateSelection();
                    items[selectedIndex]?.scrollIntoView({ block: 'nearest' });
                    break;
                    
                case 'Enter':
                    e.preventDefault();
                    if (selectedIndex >= 0 && suggestions[selectedIndex]) {
                        selectSuggestion(suggestions[selectedIndex]);
                    }
                    break;
                    
                case 'Escape':
                    hideSuggestions();
                    break;
            }
        });

        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!locationInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                hideSuggestions();
            }
        });

        // ===== PHOTO UPLOAD =====
        const photosInput = document.getElementById('photos');
        const preview = document.getElementById('preview');
        const uploadArea = document.querySelector('.photo-upload-area');
        let filesArray = [];

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('drag-over');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });

        function syncFiles() {
            const datatransfer = new DataTransfer();
            filesArray.forEach(file => datatransfer.items.add(file));
            photosInput.files = datatransfer.files;
            updatePhotoCounter();
        }

        function updatePhotoCounter() {
            const counter = document.getElementById('uploadCounter');
            const currentCount = document.getElementById('currentCount');
            const count = filesArray.length;
            
            if (currentCount) {
                currentCount.textContent = count;
            }
            
            if (counter) {
                if (count >= 5) {
                    counter.classList.add('limit-reached');
                    counter.innerHTML = '<span id="currentCount">5</span> / 5 photos (Maximum reached)';
                } else {
                    counter.classList.remove('limit-reached');
                }
            }
        }

        function handleFiles(files) {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            let rejectedFiles = [];
            let videoFiles = [];
            
            files.forEach(file => {
                if (filesArray.length >= 5) {
                    return;
                }
                
                // Check if it's a video
                if (file.type.startsWith('video/')) {
                    videoFiles.push(file.name);
                    return;
                }
                
                if (!allowedTypes.includes(file.type)) {
                    rejectedFiles.push(file.name);
                    return;
                }
                
                filesArray.push(file);
                
                const container = document.createElement('div');
                container.className = 'preview-item';
                
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                container.appendChild(img);
                
                const btn = document.createElement('button');
                btn.className = 'remove-photo';
                btn.innerHTML = '×';
                btn.type = 'button';
                btn.onclick = () => {
                    const index = filesArray.indexOf(file);
                    if (index > -1) filesArray.splice(index, 1);
                    container.remove();
                    syncFiles();
                };
                
                container.appendChild(btn);
                preview.appendChild(container);
            });
            
            syncFiles();
            
            // Show error messages for rejected files
            if (filesArray.length >= 5 && files.length > filesArray.length) {
                alert('⚠️ Maximum Limit Reached\n\nYou can only upload 5 photos per listing. Some files were not added.');
            }
            
            if (videoFiles.length > 0) {
                alert('❌ Videos Not Supported\n\nThe following files were rejected because videos are not allowed:\n\n' + videoFiles.join('\n') + '\n\nPlease upload images only (JPG, PNG, WEBP, GIF).');
            }
            
            if (rejectedFiles.length > 0 && videoFiles.length === 0) {
                alert('❌ Unsupported File Format\n\nThe following files were rejected:\n\n' + rejectedFiles.join('\n') + '\n\nSupported formats: JPG, PNG, WEBP, GIF');
            }
        }

        photosInput.addEventListener('change', function() {
            handleFiles(Array.from(this.files));
        });

        // Listing type selection
        const typeCards = document.querySelectorAll('.type-card');
        const bidDurationGroup = document.getElementById('bid-duration-group');
        const priceLabel = document.getElementById('price-label');
        const priceHint = document.getElementById('price-hint');

        typeCards.forEach(card => {
            card.addEventListener('click', function() {
                typeCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                
                const input = this.querySelector('input[type="radio"]');
                input.checked = true;
                
                const minBidIncrementGroup = document.getElementById('min-bid-increment-group');
                const minBidIncrementInput = document.getElementById('min-bid-increment');
                
                if (input.value === 'BID') {
                    bidDurationGroup.style.display = 'block';
                    minBidIncrementGroup.style.display = 'block';
                    minBidIncrementInput.required = true;
                    priceLabel.innerHTML = 'Starting Price <span class="required">*</span>';
                    priceHint.textContent = 'Minimum bid amount';
                } else {
                    bidDurationGroup.style.display = 'none';
                    minBidIncrementGroup.style.display = 'none';
                    minBidIncrementInput.required = false;
                    priceLabel.innerHTML = 'Price <span class="required">*</span>';
                    priceHint.textContent = 'Set your selling price';
                }
            });
        });

        // Set initial selected state
        document.getElementById('fixed-card').classList.add('selected');

        // Duration options
        const durationOptions = document.querySelectorAll('.duration-option');
        const customDateContainer = document.getElementById('custom-date-container');
        const customBidTime = document.getElementById('custom-bid-time');

        durationOptions.forEach(option => {
            option.addEventListener('click', function() {
                durationOptions.forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                
                const input = this.querySelector('input[type="radio"]');
                input.checked = true;
                
                if (input.value === 'custom') {
                    customDateContainer.style.display = 'block';
                    customBidTime.required = true;
                    
                    // Set minimum date to now
                    const now = new Date();
                    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                    customBidTime.min = now.toISOString().slice(0, 16);
                    
                    // Set default to 7 days from now
                    const defaultDate = new Date();
                    defaultDate.setDate(defaultDate.getDate() + 7);
                    defaultDate.setMinutes(defaultDate.getMinutes() - defaultDate.getTimezoneOffset());
                    customBidTime.value = defaultDate.toISOString().slice(0, 16);
                } else {
                    customDateContainer.style.display = 'none';
                    customBidTime.required = false;
                    customBidTime.value = '';
                }
            });
        });

        // Set initial duration selection
        <?php if ($edit_mode && ($listing_data['listing_type'] ?? '') == 'BID'): ?>
        // In edit mode, select the appropriate duration option
        const selectedDuration = '<?php echo $selected_duration; ?>';
        durationOptions.forEach((option, index) => {
            const radioInput = option.querySelector('input[type="radio"]');
            if (radioInput && radioInput.value === selectedDuration) {
                option.classList.add('selected');
            }
        });
        
        // Show custom date container if custom is selected
        if (selectedDuration === 'custom') {
            document.getElementById('custom-date-container').style.display = 'block';
        }
        <?php else: ?>
        // Default to 3 days for new listings
        durationOptions[1].classList.add('selected');
        <?php endif; ?>

        <?php if ($edit_mode && ($listing_data['listing_type'] ?? '') == 'BID'): ?>
        // Show auction fields for edit mode
        document.getElementById('min-bid-increment-group').style.display = 'block';
        document.getElementById('bid-duration-group').style.display = 'block';
        document.getElementById('price-label').textContent = 'Starting Price';
        document.getElementById('price-hint').textContent = 'Set the starting bid amount';
        document.getElementById('bid-card').classList.add('selected');
        document.getElementById('fixed-card').classList.remove('selected');
        <?php endif; ?>

        // Function to remove existing images
        function removeExistingImage(imageId, button) {
            if (!imageId || imageId === 0) {
                alert('Invalid image ID');
                return;
            }
            
            if (confirm('Are you sure you want to remove this image?')) {
                fetch('../api/delete-image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ image_id: parseInt(imageId) })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        button.parentElement.remove();
                    } else {
                        alert('Failed to remove image: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to remove image: ' + error.message);
                });
            }
        }

        // Form validation for photo requirement
        const listingForm = document.querySelector('.listing-form');
        const isEditMode = <?php echo $edit_mode ? 'true' : 'false'; ?>;
        const hasExistingImages = <?php echo ($edit_mode && !empty($listing_images)) ? 'true' : 'false'; ?>;

        listingForm.addEventListener('submit', function(e) {
            let errors = [];
            
            // Check photos (only for new listings)
            if (!isEditMode && filesArray.length === 0) {
                errors.push({
                    field: 'photos',
                    message: 'Please upload at least one photo for your listing.',
                    element: uploadArea
                });
            }
            
            // Check title
            const titleInput = document.querySelector('input[name="title"]');
            if (!titleInput.value.trim()) {
                errors.push({
                    field: 'title',
                    message: 'Please enter a title for your listing.',
                    element: titleInput
                });
            }
            
            // Check description
            const descInput = document.querySelector('textarea[name="description"]');
            if (!descInput.value.trim()) {
                errors.push({
                    field: 'description',
                    message: 'Please enter a description for your listing.',
                    element: descInput
                });
            }
            
            // Check category
            const categorySelect = document.querySelector('select[name="category"]');
            if (!categorySelect.value) {
                errors.push({
                    field: 'category',
                    message: 'Please select a category for your listing.',
                    element: categorySelect
                });
            }
            
            // Check location
            const locationInput = document.querySelector('input[name="location"]');
            if (!locationInput.value.trim()) {
                errors.push({
                    field: 'location',
                    message: 'Please enter a location for your listing.',
                    element: locationInput
                });
            }
            
            // Check price
            const priceInput = document.querySelector('input[name="price"]');
            if (!priceInput.value || parseFloat(priceInput.value) <= 0) {
                errors.push({
                    field: 'price',
                    message: 'Please enter a valid price greater than 0.',
                    element: priceInput
                });
            }
            
            // Check listing type
            const listingTypeChecked = document.querySelector('input[name="listing_type"]:checked');
            if (!listingTypeChecked) {
                errors.push({
                    field: 'listing_type',
                    message: 'Please select a listing type (Fixed Price or Auction).',
                    element: document.querySelector('.listing-type-cards')
                });
            }
            
            // If there are errors, show them one by one
            if (errors.length > 0) {
                e.preventDefault();
                
                // Show first error
                const firstError = errors[0];
                alert(firstError.message);
                
                // Scroll to and highlight the field
                firstError.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                if (firstError.element.tagName === 'INPUT' || 
                    firstError.element.tagName === 'TEXTAREA' || 
                    firstError.element.tagName === 'SELECT') {
                    firstError.element.focus();
                    firstError.element.style.border = '2px solid #dc3545';
                    setTimeout(() => {
                        firstError.element.style.border = '';
                    }, 3000);
                } else {
                    firstError.element.style.border = '2px solid #dc3545';
                    setTimeout(() => {
                        firstError.element.style.border = '2px dashed #ddd';
                    }, 3000);
                }
                
                return false;
            }
        });

    </script>
</div><!-- /.create-listing-container -->
</div><!-- /.container -->
</body>
</html>