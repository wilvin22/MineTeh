<?php
session_start();
require_once 'database/supabase.php';

// Simple test to check if admin/riders.php components work
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Rider Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .test { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Admin Riders Page - Simple Test</h1>

    <div class="test">
        <h3>Test 1: Check Session</h3>
        <?php if (isset($_SESSION['user_id'])): ?>
            <p class="success">✓ Logged in as user_id: <?php echo $_SESSION['user_id']; ?></p>
            <?php
            $user = $supabase->select('accounts', '*', ['account_id' => $_SESSION['user_id']], true);
            if ($user && $user['is_admin']):
            ?>
                <p class="success">✓ User is admin</p>
            <?php else: ?>
                <p class="error">✗ User is NOT admin</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="error">✗ Not logged in - <a href="admin/login.php">Login here</a></p>
        <?php endif; ?>
    </div>

    <div class="test">
        <h3>Test 2: Check Riders Table</h3>
        <?php
        try {
            $riders = $supabase->customQuery('riders', '*', 'limit=5');
            echo "<p class='success'>✓ Riders table accessible</p>";
            echo "<p>Found " . count($riders) . " rider(s)</p>";
            if (count($riders) > 0) {
                echo "<pre>" . json_encode($riders, JSON_PRETTY_PRINT) . "</pre>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>

    <div class="test">
        <h3>Test 3: Check API File</h3>
        <?php
        $api_path = 'api/admin-rider-action.php';
        if (file_exists($api_path)):
        ?>
            <p class="success">✓ API file exists: <?php echo $api_path; ?></p>
            <button onclick="testAPI()">Test API Connection</button>
            <div id="api-result"></div>
        <?php else: ?>
            <p class="error">✗ API file missing: <?php echo $api_path; ?></p>
        <?php endif; ?>
    </div>

    <div class="test">
        <h3>Test 4: Test Modal JavaScript</h3>
        <button onclick="testModal()">Open Test Modal</button>
        <div id="testModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
            <div style="background:white; max-width:500px; margin:50px auto; padding:30px; border-radius:10px;">
                <h3>Test Modal</h3>
                <p>If you can see this, JavaScript modals work!</p>
                <button onclick="closeTestModal()">Close</button>
            </div>
        </div>
    </div>

    <div class="test">
        <h3>Test 5: Test Form Submission</h3>
        <form id="testForm" onsubmit="return testFormSubmit(event)">
            <input type="text" name="test_field" placeholder="Enter test data" required>
            <button type="submit">Submit Test Form</button>
        </form>
        <div id="form-result"></div>
    </div>

    <div class="test">
        <h3>Test 6: Check Available Accounts</h3>
        <?php
        try {
            $accounts = $supabase->customQuery('accounts', 'account_id,username,first_name,last_name,is_rider', 
                'is_rider=eq.false&limit=5');
            echo "<p class='success'>✓ Found " . count($accounts) . " accounts available for rider conversion</p>";
            if (count($accounts) > 0) {
                echo "<ul>";
                foreach ($accounts as $acc) {
                    echo "<li>{$acc['username']} - {$acc['first_name']} {$acc['last_name']}</li>";
                }
                echo "</ul>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>

    <div class="test">
        <h3>Quick Links</h3>
        <a href="admin/riders.php"><button>Go to Admin Riders Page</button></a>
        <a href="admin/index.php"><button>Go to Admin Dashboard</button></a>
        <a href="debug_rider_system.php"><button>Full Debug Tool</button></a>
    </div>

    <script>
        function testModal() {
            document.getElementById('testModal').style.display = 'block';
        }

        function closeTestModal() {
            document.getElementById('testModal').style.display = 'none';
        }

        function testAPI() {
            const resultDiv = document.getElementById('api-result');
            resultDiv.innerHTML = '<p>Testing API...</p>';
            
            console.log('Testing API endpoint: api/admin-rider-action.php');
            
            fetch('api/admin-rider-action.php', {
                method: 'POST',
                body: new FormData()
            })
            .then(response => {
                console.log('API Response Status:', response.status);
                console.log('API Response Headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('API Raw Response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('API Parsed JSON:', data);
                    resultDiv.innerHTML = '<p class="success">✓ API responded</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    resultDiv.innerHTML = '<p class="error">✗ API returned non-JSON response:</p><pre>' + text + '</pre>';
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                resultDiv.innerHTML = '<p class="error">✗ Fetch failed: ' + error.message + '</p>';
            });
        }

        function testFormSubmit(event) {
            event.preventDefault();
            const resultDiv = document.getElementById('form-result');
            const formData = new FormData(event.target);
            
            resultDiv.innerHTML = '<p>Form data captured:</p>';
            for (let pair of formData.entries()) {
                resultDiv.innerHTML += '<p>' + pair[0] + ': ' + pair[1] + '</p>';
            }
            resultDiv.innerHTML += '<p class="success">✓ Form submission works!</p>';
            
            return false;
        }

        // Log page load
        console.log('=== RIDER TEST PAGE LOADED ===');
        console.log('Current URL:', window.location.href);
        console.log('Document ready state:', document.readyState);
    </script>
</body>
</html>
