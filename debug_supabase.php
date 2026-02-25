<?php
// Debug script to see what's wrong with Supabase connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'database/supabase.php';

echo "<h2>Supabase Connection Debug</h2>";

// Check if credentials are set
echo "<h3>1. Checking Credentials</h3>";
$reflection = new ReflectionClass($supabase);
$urlProperty = $reflection->getProperty('supabaseUrl');
$urlProperty->setAccessible(true);
$url = $urlProperty->getValue($supabase);

$keyProperty = $reflection->getProperty('supabaseKey');
$keyProperty->setAccessible(true);
$key = $keyProperty->getValue($supabase);

if ($url === 'YOUR_SUPABASE_URL' || $key === 'YOUR_SUPABASE_ANON_KEY') {
    echo "<p style='color: red;'>❌ Credentials not set! Please run setup_credentials.php</p>";
    echo "<p><a href='setup_credentials.php'>Go to Setup Page</a></p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ Credentials are set</p>";
    echo "<p>URL: " . substr($url, 0, 30) . "...</p>";
    echo "<p>Key: " . substr($key, 0, 20) . "...</p>";
}

// Test connection with a simple query
echo "<h3>2. Testing Connection</h3>";

// Enable curl verbose output
$ch = curl_init();
$testUrl = $url . '/rest/v1/accounts?select=count&limit=1';
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $key,
    'Authorization: Bearer ' . $key,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<p>HTTP Status Code: <strong>$httpCode</strong></p>";

if ($curlError) {
    echo "<p style='color: red;'>cURL Error: $curlError</p>";
}

echo "<p>Response:</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode === 200) {
    echo "<p style='color: green;'>✅ Connection successful!</p>";
} elseif ($httpCode === 401) {
    echo "<p style='color: red;'>❌ Authentication failed! Your API key might be wrong.</p>";
    echo "<p>Go to Supabase → Settings → API and copy the <strong>anon public</strong> key</p>";
} elseif ($httpCode === 404) {
    echo "<p style='color: red;'>❌ Table 'accounts' not found!</p>";
    echo "<p>Did you run the SQL schema in Supabase SQL Editor?</p>";
    echo "<p>Go to Supabase → SQL Editor and run the contents of supabase_schema.sql</p>";
} else {
    echo "<p style='color: red;'>❌ Connection failed with status $httpCode</p>";
}

// Test insert if connection works
if ($httpCode === 200 || $httpCode === 406) {
    echo "<h3>3. Testing Insert</h3>";
    
    $testData = [
        'username' => 'debugtest' . time(),
        'first_name' => 'Debug',
        'last_name' => 'Test',
        'email' => 'debug' . time() . '@test.com',
        'password_hash' => password_hash('Test123!', PASSWORD_DEFAULT),
        'is_admin' => false
    ];
    
    $insertUrl = $url . '/rest/v1/accounts';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $insertUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    
    $insertResponse = curl_exec($ch);
    $insertHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>Insert HTTP Status: <strong>$insertHttpCode</strong></p>";
    echo "<p>Insert Response:</p>";
    echo "<pre>" . htmlspecialchars($insertResponse) . "</pre>";
    
    if ($insertHttpCode === 201) {
        echo "<p style='color: green;'>✅ Insert successful!</p>";
        echo "<p>Check your Supabase dashboard → Table Editor → accounts</p>";
    } else {
        echo "<p style='color: red;'>❌ Insert failed!</p>";
        
        $errorData = json_decode($insertResponse, true);
        if ($errorData && isset($errorData['message'])) {
            echo "<p><strong>Error:</strong> " . $errorData['message'] . "</p>";
        }
        
        if ($insertHttpCode === 401) {
            echo "<p>Authentication issue - check your API key</p>";
        } elseif ($insertHttpCode === 403) {
            echo "<p>Permission denied - Row Level Security might be blocking inserts</p>";
            echo "<p><strong>Solution:</strong> Go to Supabase → Authentication → Policies</p>";
            echo "<p>Make sure the 'accounts' table has a policy allowing inserts</p>";
        }
    }
}

echo "<hr>";
echo "<p><a href='setup_credentials.php'>Setup Credentials</a> | ";
echo "<a href='test_supabase.php'>Run Basic Test</a> | ";
echo "<a href='login.php'>Go to Login</a></p>";
?>
