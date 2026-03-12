<?php
/**
 * API Response Diagnostic Tool
 * Tests the login API and shows exactly what's being returned
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Response Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; border: 1px solid #ddd; }
        .section { margin: 20px 0; padding: 15px; border-left: 4px solid #4CAF50; background: #f9f9f9; }
        .hex-dump { font-family: 'Courier New', monospace; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #4CAF50; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 API Response Diagnostic Tool</h1>
        
        <?php
        // Test credentials - CHANGE THESE to match your test account
        $testData = [
            'identifier' => 'testuser',  // Change this
            'password' => 'TestPass1!'    // Change this
        ];
        
        $apiUrl = 'https://mineteh.infinityfreeapp.com/actions/v1/auth/login.php';
        
        echo "<div class='section'>";
        echo "<h2>📤 Request Details</h2>";
        echo "<table>";
        echo "<tr><th>Property</th><th>Value</th></tr>";
        echo "<tr><td>URL</td><td>" . htmlspecialchars($apiUrl) . "</td></tr>";
        echo "<tr><td>Method</td><td>POST</td></tr>";
        echo "<tr><td>Content-Type</td><td>application/json</td></tr>";
        echo "<tr><td>Body</td><td><pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre></td></tr>";
        echo "</table>";
        echo "</div>";
        
        // Initialize cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in output
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        
        // Separate headers and body
        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);
        
        curl_close($ch);
        
        // Display HTTP Status
        echo "<div class='section'>";
        echo "<h2>📥 Response Status</h2>";
        if ($httpCode >= 200 && $httpCode < 300) {
            echo "<p class='success'>✓ HTTP $httpCode - Success</p>";
        } else if ($httpCode >= 400) {
            echo "<p class='error'>✗ HTTP $httpCode - Error</p>";
        } else {
            echo "<p class='warning'>⚠ HTTP $httpCode</p>";
        }
        
        if ($error) {
            echo "<p class='error'><strong>cURL Error:</strong> " . htmlspecialchars($error) . "</p>";
        }
        echo "</div>";
        
        // Display Response Headers
        echo "<div class='section'>";
        echo "<h2>📋 Response Headers</h2>";
        echo "<pre>" . htmlspecialchars($responseHeaders) . "</pre>";
        
        // Check Content-Type header
        if (preg_match('/Content-Type:\s*(.+)/i', $responseHeaders, $matches)) {
            $contentType = trim($matches[1]);
            if (strpos($contentType, 'application/json') !== false) {
                echo "<p class='success'>✓ Content-Type is JSON</p>";
            } else {
                echo "<p class='error'>✗ Content-Type is NOT JSON: " . htmlspecialchars($contentType) . "</p>";
            }
        }
        echo "</div>";
        
        // Display Raw Response Body
        echo "<div class='section'>";
        echo "<h2>📄 Raw Response Body</h2>";
        echo "<pre>" . htmlspecialchars($responseBody) . "</pre>";
        echo "</div>";
        
        // Hex Dump (first 500 bytes)
        echo "<div class='section'>";
        echo "<h2>🔢 Hex Dump (First 500 bytes)</h2>";
        echo "<div class='hex-dump'>";
        $hexData = substr($responseBody, 0, 500);
        for ($i = 0; $i < strlen($hexData); $i += 16) {
            $hex = '';
            $ascii = '';
            for ($j = 0; $j < 16 && ($i + $j) < strlen($hexData); $j++) {
                $byte = ord($hexData[$i + $j]);
                $hex .= sprintf('%02X ', $byte);
                $ascii .= ($byte >= 32 && $byte <= 126) ? $hexData[$i + $j] : '.';
            }
            printf("%04X: %-48s %s<br>", $i, $hex, htmlspecialchars($ascii));
        }
        echo "</div>";
        echo "</div>";
        
        // JSON Validation
        echo "<div class='section'>";
        echo "<h2>✅ JSON Validation</h2>";
        
        $decoded = json_decode($responseBody, true);
        $jsonError = json_last_error();
        
        if ($jsonError === JSON_ERROR_NONE) {
            echo "<p class='success'>✓ Valid JSON Response</p>";
            echo "<h3>Parsed Data:</h3>";
            echo "<pre>" . print_r($decoded, true) . "</pre>";
            
            // Check response structure
            if (isset($decoded['success'])) {
                echo "<p class='success'>✓ Has 'success' field: " . ($decoded['success'] ? 'true' : 'false') . "</p>";
            }
            if (isset($decoded['message'])) {
                echo "<p class='success'>✓ Has 'message' field: " . htmlspecialchars($decoded['message']) . "</p>";
            }
            if (isset($decoded['data'])) {
                echo "<p class='success'>✓ Has 'data' field</p>";
            }
        } else {
            echo "<p class='error'>✗ Invalid JSON</p>";
            echo "<p><strong>JSON Error:</strong> " . json_last_error_msg() . " (Code: $jsonError)</p>";
            
            // Diagnose common issues
            echo "<h3>Diagnosis:</h3>";
            echo "<ul>";
            
            if (strpos($responseBody, '<') !== false && strpos($responseBody, '>') !== false) {
                echo "<li class='error'>Response contains HTML tags</li>";
            }
            
            if (strpos($responseBody, 'Warning:') !== false || strpos($responseBody, 'Notice:') !== false) {
                echo "<li class='error'>Response contains PHP warnings/notices</li>";
            }
            
            if (strpos($responseBody, 'Fatal error:') !== false) {
                echo "<li class='error'>Response contains PHP fatal error</li>";
            }
            
            if (preg_match('/^\s+/', $responseBody)) {
                echo "<li class='warning'>Response has leading whitespace</li>";
            }
            
            if (preg_match('/\s+$/', $responseBody)) {
                echo "<li class='warning'>Response has trailing whitespace</li>";
            }
            
            $firstChar = substr(trim($responseBody), 0, 1);
            if ($firstChar !== '{' && $firstChar !== '[') {
                echo "<li class='error'>Response doesn't start with { or [ (starts with: '" . htmlspecialchars($firstChar) . "')</li>";
            }
            
            echo "</ul>";
        }
        echo "</div>";
        
        // Android Integration Test
        echo "<div class='section'>";
        echo "<h2>📱 Android Integration Check</h2>";
        if ($jsonError === JSON_ERROR_NONE && isset($decoded['success'])) {
            echo "<p class='success'>✓ This response should work with Android Gson parser</p>";
            echo "<h3>Expected Kotlin Data Class:</h3>";
            echo "<pre>";
            echo "data class LoginResponse(\n";
            echo "    val success: Boolean,\n";
            echo "    val message: String?,\n";
            echo "    val data: LoginData?\n";
            echo ")\n\n";
            echo "data class LoginData(\n";
            echo "    val user: User,\n";
            echo "    val token: String,\n";
            echo "    val expires_at: String\n";
            echo ")";
            echo "</pre>";
        } else {
            echo "<p class='error'>✗ This response will cause 'Expected BEGIN_OBJECT but was STRING' error in Android</p>";
            echo "<p><strong>Reason:</strong> The API is not returning valid JSON. Android's Gson parser expects a JSON object but is receiving " . 
                 (empty($responseBody) ? "empty response" : "plain text/HTML") . "</p>";
        }
        echo "</div>";
        
        ?>
        
        <div class="section">
            <h2>💡 Troubleshooting Tips</h2>
            <ul>
                <li>Make sure the test credentials match an existing account in your database</li>
                <li>Check that PHP error display is disabled in the API files</li>
                <li>Verify no HTML/text is output before the JSON response</li>
                <li>Ensure all PHP files use <code>&lt;?php</code> without any text before it</li>
                <li>Check for BOM (Byte Order Mark) at the start of PHP files</li>
                <li>Verify database connection doesn't output errors</li>
            </ul>
        </div>
    </div>
</body>
</html>
