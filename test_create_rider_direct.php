<!DOCTYPE html>
<html>
<head>
    <title>Direct Rider Creation Test</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 800px; margin: 0 auto; }
        .form-group { margin: 15px 0; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #2980b9; }
        .result { margin-top: 20px; padding: 15px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 Direct Rider Creation Test</h1>
    <p>This page tests the exact same functionality as admin/riders.php</p>

    <h2>Option 1: Create New Account + Rider</h2>
    <form id="newRiderForm">
        <input type="hidden" name="action" value="add">
        
        <div class="form-group">
            <label>Username *</label>
            <input type="text" name="username" required placeholder="rider123">
        </div>

        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" required placeholder="rider@example.com">
        </div>

        <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" required placeholder="Password123!">
        </div>

        <div class="form-group">
            <label>First Name *</label>
            <input type="text" name="first_name" required placeholder="John">
        </div>

        <div class="form-group">
            <label>Last Name *</label>
            <input type="text" name="last_name" required placeholder="Doe">
        </div>

        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="full_name" required placeholder="John Doe">
        </div>

        <div class="form-group">
            <label>Phone Number *</label>
            <input type="text" name="phone_number" required placeholder="09123456789">
        </div>

        <div class="form-group">
            <label>Vehicle Type</label>
            <select name="vehicle_type">
                <option value="motorcycle">Motorcycle</option>
                <option value="car">Car</option>
                <option value="bicycle">Bicycle</option>
                <option value="van">Van</option>
                <option value="truck">Truck</option>
            </select>
        </div>

        <div class="form-group">
            <label>License Number</label>
            <input type="text" name="license_number" placeholder="ABC-123-456">
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>

        <button type="submit">Create Rider</button>
    </form>

    <div id="result"></div>

    <hr style="margin: 40px 0;">

    <h2>Option 2: Convert Existing Account</h2>
    <form id="convertRiderForm">
        <input type="hidden" name="action" value="add">
        
        <div class="form-group">
            <label>Select Account *</label>
            <select name="account_id" id="account_select" required>
                <option value="">-- Loading accounts --</option>
            </select>
        </div>

        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="full_name" required placeholder="John Doe">
        </div>

        <div class="form-group">
            <label>Phone Number *</label>
            <input type="text" name="phone_number" required placeholder="09123456789">
        </div>

        <div class="form-group">
            <label>Vehicle Type</label>
            <select name="vehicle_type">
                <option value="motorcycle">Motorcycle</option>
                <option value="car">Car</option>
                <option value="bicycle">Bicycle</option>
            </select>
        </div>

        <div class="form-group">
            <label>License Number</label>
            <input type="text" name="license_number" placeholder="ABC-123-456">
        </div>

        <button type="submit">Convert to Rider</button>
    </form>

    <div id="result2"></div>

    <hr style="margin: 40px 0;">

    <h2>Debug Information</h2>
    <button onclick="checkSession()">Check Session</button>
    <button onclick="checkAPI()">Check API</button>
    <button onclick="loadAccounts()">Load Available Accounts</button>
    <div id="debug"></div>

    <script>
        console.log('=== DIRECT RIDER TEST PAGE ===');

        // Load available accounts on page load
        window.addEventListener('load', loadAccounts);

        function loadAccounts() {
            console.log('Loading available accounts...');
            const select = document.getElementById('account_select');
            const debug = document.getElementById('debug');
            
            fetch('api/get-available-accounts.php')
                .then(r => r.json())
                .then(data => {
                    console.log('Available accounts:', data);
                    if (data.success && data.accounts) {
                        select.innerHTML = '<option value="">-- Select an account --</option>';
                        data.accounts.forEach(acc => {
                            select.innerHTML += `<option value="${acc.account_id}">${acc.username} - ${acc.first_name} ${acc.last_name}</option>`;
                        });
                        debug.innerHTML = '<div class="result success">✓ Loaded ' + data.accounts.length + ' available accounts</div>';
                    } else {
                        select.innerHTML = '<option value="">No accounts available</option>';
                        debug.innerHTML = '<div class="result error">No accounts available for conversion</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading accounts:', error);
                    select.innerHTML = '<option value="">Error loading accounts</option>';
                    debug.innerHTML = '<div class="result error">Error: ' + error.message + '</div>';
                });
        }

        function checkSession() {
            const debug = document.getElementById('debug');
            debug.innerHTML = '<p>Checking session...</p>';
            
            fetch('api/check-session.php')
                .then(r => r.json())
                .then(data => {
                    console.log('Session data:', data);
                    debug.innerHTML = '<div class="result success"><pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
                })
                .catch(error => {
                    console.error('Session check error:', error);
                    debug.innerHTML = '<div class="result error">Error: ' + error.message + '</div>';
                });
        }

        function checkAPI() {
            const debug = document.getElementById('debug');
            debug.innerHTML = '<p>Checking API...</p>';
            
            fetch('api/admin-rider-action.php', {
                method: 'POST',
                body: new FormData()
            })
            .then(r => r.json())
            .then(data => {
                console.log('API response:', data);
                debug.innerHTML = '<div class="result ' + (data.success ? 'success' : 'error') + '"><pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
            })
            .catch(error => {
                console.error('API check error:', error);
                debug.innerHTML = '<div class="result error">Error: ' + error.message + '</div>';
            });
        }

        // Form 1: Create new account + rider
        document.getElementById('newRiderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const resultDiv = document.getElementById('result');
            const formData = new FormData(this);
            
            resultDiv.innerHTML = '<p>Creating rider...</p>';
            console.log('Submitting new rider form...');
            
            // Log form data
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            fetch('api/admin-rider-action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    resultDiv.innerHTML = '<div class="result success">✓ ' + data.message + '<br><a href="admin/riders.php">View Riders</a></div>';
                    this.reset();
                } else {
                    resultDiv.innerHTML = '<div class="result error">✗ ' + data.message + '</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultDiv.innerHTML = '<div class="result error">✗ Error: ' + error.message + '</div>';
            });
        });

        // Form 2: Convert existing account
        document.getElementById('convertRiderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const resultDiv = document.getElementById('result2');
            const formData = new FormData(this);
            
            resultDiv.innerHTML = '<p>Converting account to rider...</p>';
            console.log('Submitting convert rider form...');
            
            // Log form data
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            fetch('api/admin-rider-action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    resultDiv.innerHTML = '<div class="result success">✓ ' + data.message + '<br><a href="admin/riders.php">View Riders</a></div>';
                    this.reset();
                    loadAccounts(); // Reload available accounts
                } else {
                    resultDiv.innerHTML = '<div class="result error">✗ ' + data.message + '</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultDiv.innerHTML = '<div class="result error">✗ Error: ' + error.message + '</div>';
            });
        });
    </script>
</body>
</html>
