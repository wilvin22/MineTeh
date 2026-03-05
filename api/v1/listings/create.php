<?php
require_once __DIR__ . '/../config.php';

// Enable error logging for debugging
error_log("=== CREATE LISTING REQUEST ===");
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

$userId = requireAuth();

// Get request data - handle both JSON and form-data
$data = [];
if (!empty($_POST)) {
    $data = $_POST;
} else {
    $data = getRequestData();
}

$title = $data['title'] ?? '';
$description = $data['description'] ?? '';
$price = $data['price'] ?? 0;
$location = $data['location'] ?? '';
$category = $data['category'] ?? '';
$listingType = $data['listing_type'] ?? 'FIXED';
$endTime = $data['end_time'] ?? null;
$minBidIncrement = $data['min_bid_increment'] ?? 1;

// Validate required fields
if (empty($title) || empty($description) || empty($location) || empty($category)) {
    sendError('Title, description, location, and category are required');
}

if ($price <= 0) {
    sendError('Price must be greater than 0');
}

// Validate images are uploaded
if (empty($_FILES['images']['name'][0])) {
    sendError('At least one image is required');
}

// Prepare listing data
$listingData = [
    'seller_id' => $userId,
    'title' => $title,
    'description' => $description,
    'price' => floatval($price),
    'location' => $location,
    'category' => $category,
    'listing_type' => $listingType,
    'status' => 'active',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

// Add bid-specific fields
if ($listingType === 'BID') {
    $listingData['starting_price'] = floatval($price);
    $listingData['current_price'] = floatval($price);
    $listingData['min_bid_increment'] = floatval($minBidIncrement);
    
    if ($endTime) {
        $listingData['end_time'] = $endTime;
    }
}

// Insert listing
$result = $supabase->insert('listings', $listingData);

if ($result === false) {
    error_log("Failed to insert listing: " . print_r($supabase->getLastError(), true));
    sendError('Failed to create listing', 500);
}

// Get the created listing ID
if (empty($result) || !isset($result[0]['id'])) {
    error_log("No listing ID returned from insert");
    sendError('Listing created but failed to get ID', 500);
}

$listingId = $result[0]['id'];
error_log("Created listing with ID: " . $listingId);

// Handle image uploads
$uploadDir = __DIR__ . '/../../../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
    error_log("Created uploads directory: " . $uploadDir);
}

$maxPhotos = 5;
$uploadedCount = 0;
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

if (!empty($_FILES['images']['name'])) {
    $fileCount = is_array($_FILES['images']['name']) ? count($_FILES['images']['name']) : 1;
    error_log("Processing $fileCount image(s)");
    
    for ($i = 0; $i < $fileCount && $uploadedCount < $maxPhotos; $i++) {
        // Handle both array and single file upload
        $fileName = is_array($_FILES['images']['name']) ? $_FILES['images']['name'][$i] : $_FILES['images']['name'];
        $fileTmpName = is_array($_FILES['images']['tmp_name']) ? $_FILES['images']['tmp_name'][$i] : $_FILES['images']['tmp_name'];
        $fileError = is_array($_FILES['images']['error']) ? $_FILES['images']['error'][$i] : $_FILES['images']['error'];
        $fileSize = is_array($_FILES['images']['size']) ? $_FILES['images']['size'][$i] : $_FILES['images']['size'];
        
        error_log("Processing file $i: $fileName (error: $fileError, size: $fileSize)");
        
        if ($fileError !== UPLOAD_ERR_OK) {
            error_log("Upload error for file $i: " . $fileError);
            continue;
        }
        
        if (!is_uploaded_file($fileTmpName)) {
            error_log("File $i is not an uploaded file");
            continue;
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmpName);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            error_log("Invalid MIME type for file $i: $mimeType");
            continue;
        }
        
        // Generate unique filename
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid('img_', true) . '.' . $fileExt;
        $uploadPath = $uploadDir . $newFileName;
        $relativePath = '../uploads/' . $newFileName;
        
        error_log("Attempting to move file to: " . $uploadPath);
        
        if (move_uploaded_file($fileTmpName, $uploadPath)) {
            error_log("File moved successfully: " . $uploadPath);
            
            // Insert into listing_images table
            $imageData = [
                'listing_id' => $listingId,
                'image_path' => $relativePath
            ];
            
            $imageResult = $supabase->insert('listing_images', $imageData);
            
            if ($imageResult === false) {
                error_log("Failed to insert image into database: " . print_r($supabase->getLastError(), true));
            } else {
                error_log("Image inserted into database successfully");
                $uploadedCount++;
            }
        } else {
            error_log("Failed to move uploaded file to: " . $uploadPath);
        }
    }
}

error_log("Total images uploaded: $uploadedCount");

if ($uploadedCount === 0) {
    // Delete the listing if no images were uploaded
    $supabase->delete('listings', ['id' => $listingId]);
    sendError('Failed to upload images. Please try again.', 500);
}

// Get the created listing with images
$newListing = $supabase->select('listings', '*', ['id' => $listingId], true);
$images = $supabase->select('listing_images', 'image_path', ['listing_id' => $listingId]);
$newListing['images'] = $images;

error_log("Listing created successfully with $uploadedCount images");
sendResponse(true, $newListing, 'Listing created successfully', 201);
?>
