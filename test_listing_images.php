<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Listing Images Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Test the array_map fix
echo "<h2>Testing array_map with empty array</h2>";

// Simulate the fixed code
$images = []; // Empty array (like when no images exist)

try {
    $js_images = json_encode(array_map(function($img) {
        return str_replace('../', '', $img['image_path']);
    }, $images ?? []));
    
    echo "<p class='success'>✅ Empty array handled correctly</p>";
    echo "<p class='info'>JavaScript array: $js_images</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// Test with sample data
echo "<h2>Testing array_map with sample data</h2>";

$images = [
    ['image_path' => '../uploads/image1.jpg'],
    ['image_path' => '../uploads/image2.jpg']
];

try {
    $js_images = json_encode(array_map(function($img) {
        return str_replace('../', '', $img['image_path']);
    }, $images ?? []));
    
    echo "<p class='success'>✅ Sample data handled correctly</p>";
    echo "<p class='info'>JavaScript array: $js_images</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// Test with null (the original problem)
echo "<h2>Testing array_map with null (original problem)</h2>";

$images = null;

try {
    $js_images = json_encode(array_map(function($img) {
        return str_replace('../', '', $img['image_path']);
    }, $images ?? []));
    
    echo "<p class='success'>✅ Null value handled correctly with ?? [] fallback</p>";
    echo "<p class='info'>JavaScript array: $js_images</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>🔗 Navigation</h2>";
echo "<p><a href='home/listing-details.php?id=1'>Test Listing Details (if listing exists)</a></p>";
?>