<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

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
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .create-listing-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 36px;
            color: #333;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 16px;
        }

        .listing-form {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .form-section {
            padding: 30px;
            border-bottom: 1px solid #eee;
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
        }

        .section-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #945a9b, #6a406e);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }

        .form-group label .required {
            color: #e74c3c;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #945a9b;
            box-shadow: 0 0 0 3px rgba(148, 90, 155, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }

        .form-hint {
            font-size: 12px;
            color: #999;
            margin-top: 6px;
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
            padding: 30px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .submit-btn {
            padding: 16px 40px;
            background: linear-gradient(135deg, #945a9b, #6a406e);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(148, 90, 155, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(148, 90, 155, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .cancel-btn {
            padding: 16px 40px;
            background: white;
            color: #666;
            border: 2px solid #ddd;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
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

        @media (max-width: 768px) {
            .form-grid,
            .listing-type-cards {
                grid-template-columns: 1fr;
            }

            .bid-duration-options {
                grid-template-columns: repeat(2, 1fr);
            }

            .submit-section {
                flex-direction: column-reverse;
                gap: 15px;
            }

            .submit-btn,
            .cancel-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="create-listing-container">
            <div class="page-header">
                <h1><?php echo $edit_mode ? '✏️ Edit Listing' : '✨ Create New Listing'; ?></h1>
                <p><?php echo $edit_mode ? 'Update your listing details' : 'Share your item with the community'; ?></p>
            </div>

            <?php if ($success_message): ?>
                <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data" class="listing-form">
                <!-- Photos Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">📸</div>
                        <div class="section-title">Photos</div>
                    </div>
                    
                    <div class="photo-upload-area" onclick="document.getElementById('photos').click()">
                        <div class="upload-icon">🖼️</div>
                        <div class="upload-text">Click to upload photos <span class="required">*</span></div>
                        <div class="upload-hint">or drag and drop (Max 5 photos, JPG/PNG/WEBP/GIF)</div>
                    </div>
                    <input type="file" id="photos" name="photos[]" accept="image/*" multiple hidden <?php echo !$edit_mode ? 'required' : ''; ?>>
                    
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
                </div>

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
                            <input type="text" name="location" placeholder="e.g., Manila, Philippines" value="<?php echo $edit_mode ? htmlspecialchars($listing_data['location'] ?? '') : ''; ?>" required>
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
            </form>
        </div>
    </div>

    <script>
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
        }

        function handleFiles(files) {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            
            files.forEach(file => {
                if (filesArray.length >= 5) {
                    alert('Maximum 5 photos allowed');
                    return;
                }
                
                if (!allowedTypes.includes(file.type)) {
                    alert(`"${file.name}" is not a supported format`);
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
</body>
</html>
            if (confirm('Are you sure you want to delete this image?')) {
                const formData = new FormData();
                formData.append('image_id', imageId);
                formData.append('listing_id', <?php echo $edit_mode ? (int)$listing_id : 0; ?>);
                
                fetch('../api/delete-image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        button.closest('.preview-item').remove();
                        alert('Image deleted successfully');
                    } else {
                        alert('Error deleting image: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting image');
                });
            }
        }

        // Photo upload preview
        const photosInput = document.getElementById('photos');
        const preview = document.getElementById('preview');
        const uploadArea = document.querySelector('.photo-upload-area');

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#FF6B35';
            uploadArea.style.background = 'rgba(255, 107, 53, 0.05)';
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.style.borderColor = '#ddd';
            uploadArea.style.background = '#f9f9f9';
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#ddd';
            uploadArea.style.background = '#f9f9f9';
            
            const files = e.dataTransfer.files;
            photosInput.files = files;
            handleFiles(files);
        });

        photosInput.addEventListener('change', function() {
            handleFiles(this.files);
        });

        function handleFiles(files) {
            preview.innerHTML = '';
            
            if (files.length > 5) {
                alert('Maximum 5 photos allowed');
                photosInput.value = '';
                return;
            }

            Array.from(files).forEach((file, index) => {
                if (!file.type.startsWith('image/')) {
                    alert('Only image files are allowed');
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB: ' + file.name);
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview ${index + 1}">
                        <button type="button" class="remove-photo" onclick="removePhoto(${index})">×</button>
                        ${index === 0 ? '<div class="primary-badge">Primary</div>' : ''}
                    `;
                    preview.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }

        function removePhoto(index) {
            const dt = new DataTransfer();
            const files = photosInput.files;
            
            for (let i = 0; i < files.length; i++) {
                if (i !== index) {
                    dt.items.add(files[i]);
                }
            }
            
            photosInput.files = dt.files;
            handleFiles(photosInput.files);
        }

        // Form validation before submit
        document.querySelector('.listing-form').addEventListener('submit', function(e) {
            const listingType = document.querySelector('input[name="listing_type"]:checked');
            
            if (!listingType) {
                e.preventDefault();
                alert('Please select a listing type (Fixed Price or Auction)');
                return false;
            }

            if (listingType.value === 'BID') {
                const durationSelected = document.querySelector('input[name="bid_duration"]:checked');
                if (!durationSelected) {
                    e.preventDefault();
                    alert('Please select an auction duration');
                    return false;
                }

                if (durationSelected.value === 'custom') {
                    const customDate = document.getElementById('custom-bid-time').value;
                    if (!customDate) {
                        e.preventDefault();
                        alert('Please select a custom end date for the auction');
                        return false;
                    }

                    const selectedDate = new Date(customDate);
                    const now = new Date();
                    if (selectedDate <= now) {
                        e.preventDefault();
                        alert('Auction end date must be in the future');
                        return false;
                    }
                }
            }

            <?php if (!$edit_mode): ?>
            if (!photosInput.files || photosInput.files.length === 0) {
                e.preventDefault();
                alert('Please upload at least one photo');
                return false;
            }
            <?php endif; ?>

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = '<?php echo $edit_mode ? "Updating..." : "Creating..."; ?>';
        });

        console.log('Create listing page loaded successfully');
    </script>
</body>
</html>
