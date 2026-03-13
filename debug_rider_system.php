<!DOCTYPE html>
<html>
<head>
    <title>Rider System Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        h2 { border-bottom: 2px solid #333; padding-bottom: 10px; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        .test-btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .test-btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <h1>🔍 Rider System Debug Tool</h1>

<?php
session_start();
require_once 'database/supabase.php';

function testSection($title, $callback) {
    echo "<div class='section'>";
    echo "<h2>$title</h2>";
    try {
        $callback();
    } catch (Exception $e) {
        echo "<p class='error'>✗ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
}

// Test 1: Database Tables
testSection("1. Database Tables Check", function() use ($supabase) {
    $tables = ['accounts', 'riders', 'deliveries', 'delivery_tracking', 'rider_earnings'];
    foreach ($tables as $table) {
        try {
            $result = $supabase->customQuery($table, '*', 'limit=1');
            echo "<p class='success'>✓ Table '$table' exists</p>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ Table '$table' missing or error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
});

// Test 2: Accounts Table Structure
testSection("2. Accounts Table - is_rider Column", function() use ($supabase) {
    $accounts = $supabase->customQuery('accounts', 'account_id,username,is_admin,is_rider', 'limit=5');
    if ($accounts) {
        echo "<p class='success'>✓ is_rider column exists</p>";
        echo "<pre>" . json_encode($accounts, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p class='error'>✗ Could not query accounts table</p>";
    }
});

// Test 3: Session Check
testSection("3. Current Session", function() {
    if (isset($_SESSION['user_id'])) {
        echo "<p class='success'>✓ User logged in</p>";
        echo "<pre>";
        echo "user_id: " . $_SESSION['user_id'] . "\n";
        echo "username: " . ($_SESSION['username'] ?? 'not set') . "\n";
        echo "is_admin: " . (($_SESSION['is_admin'] ?? false) ? 'true' : 'false') . "\n";
        echo "is_rider: " . (($_SESSION['is_rider'] ?? false) ? 'true' : 'false') . "\n";
        echo "</pre>";
    } else {
        echo "<p class='warning'>⚠ No user logged in</p>";
        echo "<p><a href='admin/login.php'>Login as Admin</a></p>";
    }
});

// Test 4: Existing Riders
testSection("4. Existing Riders", function() use ($supabase) {
    $riders = $supabase->customQuery('riders', '*', 'order=created_at.desc');
    if ($riders && count($riders) > 0) {
        echo "<p class='success'>✓ Found " . count($riders) . " rider(s)</p>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Account ID</th><th>Name</th><th>Phone</th><th>Vehicle</th><th>Status</th><th>Deliveries</th></tr>";
        foreach ($riders as $rider) {
            echo "<tr>";
            echo "<td>{$rider['rider_id']}</td>";
            echo "<td>{$rider['account_id']}</td>";
            echo "<td>" . htmlspecialchars($rider['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($rider['phone_number']) . "</td>";
            echo "<td>" . htmlspecialchars($rider['vehicle_type'] ?? 'N/A') . "</td>";
            echo "<td>{$rider['status']}</td>";
            echo "<td>{$rider['total_deliveries']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠ No riders found</p>";
        echo "<p>Create a rider via <a href='admin/riders.php'>admin/riders.php</a></p>";
    }
});

// Test 5: Available Accounts for Conversion
testSection("5. Accounts Available for Rider Conversion", function() use ($supabase) {
    $accounts = $supabase->customQuery('accounts', 'account_id,username,first_name,last_name,email,is_rider', 
        'is_rider=eq.false&order=username.asc&limit=10');
    if ($accounts && count($accounts) > 0) {
        echo "<p class='success'>✓ Found " . count($accounts) . " account(s) that can be converted to riders</p>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Is Rider</th></tr>";
        foreach ($accounts as $acc) {
            echo "<tr>";
            echo "<td>{$acc['account_id']}</td>";
            echo "<td>" . htmlspecialchars($acc['username']) . "</td>";
            echo "<td>" . htmlspecialchars($acc['first_name'] . ' ' . $acc['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($acc['email']) . "</td>";
            echo "<td>" . ($acc['is_rider'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠ No accounts available for conversion</p>";
    }
});

// Test 6: API Endpoint Test
testSection("6. API Endpoint Check", function() {
    $api_file = 'api/admin-rider-action.php';
    if (file_exists($api_file)) {
        echo "<p class='success'>✓ API file exists: $api_file</p>";
        echo "<p>File size: " . filesize($api_file) . " bytes</p>";
    } else {
        echo "<p class='error'>✗ API file missing: $api_file</p>";
    }
});

// Test 7: Admin Page Check
testSection("7. Admin Pages Check", function() {
    $pages = [
        'admin/index.php' => 'Admin Dashboard',
        'admin/riders.php' => 'Rider Management',
        'admin/login.php' => 'Admin Login',
        'rider/dashboard.php' => 'Rider Dashboard'
    ];
    
    foreach ($pages as $file => $name) {
        if (file_exists($file)) {
            echo "<p class='success'>✓ $name exists: <a href='$file'>$file</a></p>";
        } else {
            echo "<p class='error'>✗ $name missing: $file</p>";
        }
    }
});

// Test 8: Upload Folders
testSection("8. Upload Folders Check", function() {
    $folders = [
        'uploads/delivery_proofs',
        'uploads/signatures'
    ];
    
    foreach ($folders as $folder) {
        if (is_dir($folder)) {
            $writable = is_writable($folder);
            if ($writable) {
                echo "<p class='success'>✓ Folder exists and writable: $folder</p>";
            } else {
                echo "<p class='warning'>⚠ Folder exists but not writable: $folder</p>";
            }
        } else {
            echo "<p class='error'>✗ Folder missing: $folder</p>";
            echo "<p>Create with: <code>mkdir -p $folder</code></p>";
        }
    }
});
?>

    <div class="section">
        <h2>9. Quick Actions</h2>
        <a href="admin/login.php"><button class="test-btn">Login as Admin</button></a>
        <a href="admin/riders.php"><button class="test-btn">Manage Riders</button></a>
        <a href="rider/dashboard.php"><button class="test-btn">Rider Dashboard</button></a>
        <a href="test_rider_page.php"><button class="test-btn">Simple Test</button></a>
        <a href="api/test-rider-creation.php"><button class="test-btn">Test Rider Creation</button></a>
    </div>

    <div class="section">
        <h2>10. Common Issues & Solutions</h2>
        <ul>
            <li><strong>Tables missing:</strong> Run <code>add_rider_system_tables.sql</code> in your Supabase SQL editor</li>
            <li><strong>is_rider column missing:</strong> The SQL file should add this column automatically</li>
            <li><strong>Can't create rider:</strong> Check browser console (F12) for JavaScript errors</li>
            <li><strong>API returns error:</strong> Check that you're logged in as admin</li>
            <li><strong>Upload folders missing:</strong> Create them manually or run the setup script</li>
        </ul>
    </div>

    <div class="section">
        <h2>11. Browser Console Test</h2>
        <p>Open browser console (F12) and run this test:</p>
        <pre>
// Test API endpoint
fetch('api/admin-rider-action.php', {
    method: 'POST',
    body: new FormData()
}).then(r => r.json()).then(console.log);
        </pre>
        <button class="test-btn" onclick="testAPI()">Run API Test</button>
        <div id="api-result"></div>
    </div>

    <script>
        function testAPI() {
            const resultDiv = document.getElementById('api-result');
            resultDiv.innerHTML = '<p>Testing API...</p>';
            
            fetch('api/admin-rider-action.php', {
                method: 'POST',
                body: new FormData()
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('API Response:', data);
                resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                console.error('API Error:', error);
                resultDiv.innerHTML = '<p class="error">Error: ' + error.message + '</p>';
            });
        }
    </script>
</body>
</html>
