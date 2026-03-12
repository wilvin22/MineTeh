<?php
// Test the API login endpoint
header('Content-Type: text/html; charset=utf-8');

echo "<h1>API Login Test</h1>";

// Test data
$testData = [
    'identifier' => 'testuser',
    'password' => 'TestPass1!'
];

$url = 'https://mineteh.infinityfreeapp.com/actions/v1/auth/login.php';

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

echo "<h2>Request:</h2>";
echo "<pre>";
echo "URL: $url\n";
echo "Method: POST\n";
echo "Headers: Content-Type: application/json\n";
echo "Body: " . json_encode($testData, JSON_PRETTY_PRINT);
echo "</pre>";

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h2>Response:</h2>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($error) {
    echo "<p style='color:red'><strong>cURL Error:</strong> $error</p>";
}

echo "<h3>Raw Response:</h3>";
echo "<pre style='background:#f5f5f5;padding:10px;border:1px solid #ddd;'>";
echo htmlspecialchars($response);
echo "</pre>";

echo "<h3>Response Analysis:</h3>";
$decoded = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<p style='color:green'>✓ Valid JSON</p>";
    echo "<pre>";
    print_r($decoded);
    echo "</pre>";
} else {
    echo "<p style='color:red'>✗ Invalid JSON - Error: " . json_last_error_msg() . "</p>";
    echo "<p>The API is returning HTML or plain text instead of JSON.</p>";
    
    // Check if it's HTML
    if (strpos($response, '<') !== false) {
        echo "<p style='color:orange'>⚠ Response contains HTML tags</p>";
    }
}
?>
