<?php
/**
 * MineTeh Configuration File
 * 
 * This file contains environment-specific configuration.
 * Update these values when deploying to production.
 */

// Determine if we're in production or development
// Auto-detect based on domain
$is_production = isset($_SERVER['HTTP_HOST']) && 
                 (strpos($_SERVER['HTTP_HOST'], 'infinityfreeapp.com') !== false || 
                  strpos($_SERVER['HTTP_HOST'], 'infinityfree.com') !== false);

define('ENVIRONMENT', $is_production ? 'production' : 'development');

// Base URL Configuration
// For development: http://localhost/MineTeh
// For production: https://yourdomain.com
if (ENVIRONMENT === 'production') {
    define('BASE_URL', 'https://mineteh.infinityfreeapp.com');
} else {
    // Auto-detect localhost URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // Check if we're in a subfolder (localhost) or root (production-like local)
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    $base_path = ($script_path === '/' || $script_path === '\\') ? '' : $script_path;
    
    define('BASE_URL', $protocol . '://' . $host . $base_path);
}

// API Base URL
define('API_BASE_URL', BASE_URL . '/api/v1');

// Timezone
define('TIMEZONE', 'Asia/Manila');

// Session Configuration
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// File Upload Configuration
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('MAX_IMAGES_PER_LISTING', 5);
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Supabase Configuration (already in database/supabase.php)
// Keep Supabase credentials in database/supabase.php for security

// Error Reporting
if (ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Helper function to get full URL
function getUrl($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

// Helper function to get API URL
function getApiUrl($endpoint = '') {
    return API_BASE_URL . '/' . ltrim($endpoint, '/');
}

// Helper function to get proper image URL
function getImageUrl($imagePath) {
    if (empty($imagePath)) {
        return BASE_URL . '/assets/no-image.png';
    }
    
    // If it's already an absolute URL, return as-is
    if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
        return $imagePath;
    }
    
    // Extract just the filename from paths like "../uploads/img_xxx.jpg" or "uploads/img_xxx.jpg"
    $filename = basename($imagePath);
    
    // Return absolute URL to the image
    return BASE_URL . '/uploads/' . $filename;
}
?>
