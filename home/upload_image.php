<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if JSON data is valid
if (!$data || !isset($data['filename']) || !isset($data['image_data'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data. Expected filename and image_data fields.']);
    exit;
}

$filename = $data['filename'];
$base64_image = $data['image_data'];

// Validate filename
if (empty($filename) || !preg_match('/^[a-zA-Z0-9._-]+\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid filename format']);
    exit;
}

// Decode base64 image
$image_data = base64_decode($base64_image);
if ($image_data === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid base64 image data']);
    exit;
}

// Validate image size (max 10MB)
$max_size = 10 * 1024 * 1024; // 10MB
if (strlen($image_data) > $max_size) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Image too large. Maximum size is 10MB.']);
    exit;
}

$upload_dir = __DIR__ . "/uploads/";

// Create uploads directory if it doesn't exist
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit;
    }
}

// Sanitize filename
$safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
$file_path = $upload_dir . $safe_filename;

// Save image file
if (file_put_contents($file_path, $image_data) !== false) {
    // Success response
    echo json_encode([
        'success' => true, 
        'message' => 'Image uploaded successfully',
        'filename' => $safe_filename,
        'path' => 'uploads/' . $safe_filename,
        'url' => 'https://mineteh.infinityfree.me/home/uploads/' . $safe_filename,
        'size' => strlen($image_data)
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save image file']);
}
?>