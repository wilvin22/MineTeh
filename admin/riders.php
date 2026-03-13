<?php
session_start();
date_default_timezone_set('Asia/Manila');

require_once '../database/supabase.php';

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$admin = $supabase->select('accounts', '*', ['account_id' => $_SESSION['user_id']], true);
if (!$admin || !$admin['is_admin']) {
    die('Access denied. Admin only.');
}

// Get all riders
$riders = $supabase->customQuery('riders', '*', 'order=created_at.desc') ?? [];

// Get rider statistics
$total_riders = count($riders);
$active_riders = 0;
$total_deliveries_all = 0;

foreach ($riders as $rider) {
    if ($rider['status'] == 'active') {
        $active_riders++;
    }
    $total_deliveries_all += $rider['total_deliveries'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Management - MineTeh Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        .navbar {
            background: #2c3e50;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            font-size: 24px;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-header h2 {
            font-size: 22px;
            color: #2c3e50;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status-suspended {
            background: #fff3cd;
            color: #856404;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            font-size: 22px;
            color: #2c3e50;
        }

        .close {
            font-size: 28px;
            cursor: pointer;
            color: #7f8c8d;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 5px;
            color: #7f8c8d;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: #3498db;
        }

        .toggle-password svg {
            display: block;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .empty-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🏍️ Rider Management</h1>
        <div>
            <a href="index.php">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="riders.php">Riders</a>
            <a href="delivery-monitor.php">Delivery Monitor</a>
            <a href="listings.php">Listings</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_riders; ?></div>
                <div class="stat-label">Total Riders</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $active_riders; ?></div>
                <div class="stat-label">Active Riders</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_deliveries_all; ?></div>
                <div class="stat-label">Total Deliveries</div>
            </div>
        </div>

        <!-- Riders List -->
        <div class="card">
            <div class="card-header">
                <h2>All Riders</h2>
                <button class="btn btn-primary" onclick="openAddModal()">+ Add New Rider</button>
            </div>

            <?php if (empty($riders)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🏍️</div>
                    <h3>No Riders Yet</h3>
                    <p>Click "Add New Rider" to register your first rider.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Vehicle</th>
                            <th>License</th>
                            <th>Status</th>
                            <th>Rating</th>
                            <th>Deliveries</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riders as $rider): ?>
                        <tr>
                            <td>#<?php echo $rider['rider_id']; ?></td>
                            <td><?php echo htmlspecialchars($rider['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($rider['phone_number']); ?></td>
                            <td><?php echo htmlspecialchars($rider['vehicle_type'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($rider['license_number'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $rider['status']; ?>">
                                    <?php echo strtoupper($rider['status']); ?>
                                </span>
                            </td>
                            <td>⭐ <?php echo number_format($rider['rating'], 1); ?></td>
                            <td><?php echo $rider['total_deliveries']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editRider(<?php echo htmlspecialchars(json_encode($rider)); ?>)">Edit</button>
                                <?php if ($rider['status'] == 'active'): ?>
                                    <button class="btn btn-sm btn-warning" onclick="updateStatus(<?php echo $rider['rider_id']; ?>, 'inactive')">Deactivate</button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $rider['rider_id']; ?>, 'active')">Activate</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Rider Modal -->
    <div id="riderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Rider</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="riderForm">
                <input type="hidden" id="rider_id" name="rider_id">
                <input type="hidden" id="action" name="action" value="add">

                <div class="form-group">
                    <label>Select Existing Account (Optional)</label>
                    <select id="account_id" name="account_id">
                        <option value="">-- Create New Account --</option>
                        <?php
                        // Get accounts that are not riders yet
                        $accounts = $supabase->customQuery('accounts', 'account_id,username,first_name,last_name,email', 
                            'is_rider=eq.false&order=username.asc') ?? [];
                        foreach ($accounts as $account):
                        ?>
                            <option value="<?php echo $account['account_id']; ?>">
                                <?php echo htmlspecialchars($account['username'] . ' - ' . $account['first_name'] . ' ' . $account['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="newAccountFields">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label>Password *</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required>
                            <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password', this)">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>

                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>

                <input type="hidden" id="full_name" name="full_name">

                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="text" id="phone_number" name="phone_number" required placeholder="09XXXXXXXXX">
                </div>

                <div class="form-group">
                    <label>Vehicle Type</label>
                    <select id="vehicle_type" name="vehicle_type">
                        <option value="motorcycle">Motorcycle</option>
                        <option value="car">Car</option>
                        <option value="bicycle">Bicycle</option>
                        <option value="van">Van</option>
                        <option value="truck">Truck</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>License Number</label>
                    <input type="text" id="license_number" name="license_number" placeholder="ABC-123-456">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Save Rider</button>
            </form>
        </div>
    </div>

    <script>
        console.log('=== RIDER MANAGEMENT PAGE LOADED ===');
        
        // Password visibility toggle
        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const svg = button.querySelector('svg');
            
            if (input.type === 'password') {
                input.type = 'text';
                svg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                svg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }

        // Auto-generate full name from first and last name
        function updateFullName() {
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const fullName = (firstName + ' ' + lastName).trim();
            document.getElementById('full_name').value = fullName;
        }
        
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing...');
            
            // Auto-update full name when first or last name changes
            const firstNameInput = document.getElementById('first_name');
            const lastNameInput = document.getElementById('last_name');
            
            if (firstNameInput) {
                firstNameInput.addEventListener('input', updateFullName);
            }
            if (lastNameInput) {
                lastNameInput.addEventListener('input', updateFullName);
            }
            
            // Toggle new account fields based on selection
            const accountSelect = document.getElementById('account_id');
            if (accountSelect) {
                accountSelect.addEventListener('change', function() {
                    const newAccountFields = document.getElementById('newAccountFields');
                    const fields = newAccountFields.querySelectorAll('input');
                    
                    if (this.value) {
                        // Existing account selected
                        newAccountFields.style.display = 'none';
                        fields.forEach(field => field.removeAttribute('required'));
                    } else {
                        // Create new account
                        newAccountFields.style.display = 'block';
                        fields.forEach(field => field.setAttribute('required', 'required'));
                    }
                });
                console.log('✓ Account select listener attached');
            } else {
                console.error('✗ account_id element not found');
            }
        });

        function openAddModal() {
            console.log('openAddModal() called');
            try {
                document.getElementById('modalTitle').textContent = 'Add New Rider';
                document.getElementById('action').value = 'add';
                document.getElementById('riderForm').reset();
                document.getElementById('rider_id').value = '';
                document.getElementById('newAccountFields').style.display = 'block';
                document.getElementById('riderModal').style.display = 'block';
                console.log('✓ Modal opened successfully');
            } catch (error) {
                console.error('✗ Error opening modal:', error);
                alert('Error opening modal: ' + error.message);
            }
        }

        function editRider(rider) {
            console.log('editRider() called with:', rider);
            try {
                document.getElementById('modalTitle').textContent = 'Edit Rider';
                document.getElementById('action').value = 'edit';
                document.getElementById('rider_id').value = rider.rider_id;
                document.getElementById('full_name').value = rider.full_name;
                document.getElementById('phone_number').value = rider.phone_number;
                document.getElementById('vehicle_type').value = rider.vehicle_type || 'motorcycle';
                document.getElementById('license_number').value = rider.license_number || '';
                document.getElementById('status').value = rider.status;
                
                // Hide account creation fields when editing
                document.getElementById('newAccountFields').style.display = 'none';
                document.getElementById('account_id').parentElement.style.display = 'none';
                
                document.getElementById('riderModal').style.display = 'block';
                console.log('✓ Edit modal opened successfully');
            } catch (error) {
                console.error('✗ Error in editRider:', error);
                alert('Error opening edit modal: ' + error.message);
            }
        }

        function closeModal() {
            console.log('closeModal() called');
            try {
                document.getElementById('riderModal').style.display = 'none';
                document.getElementById('account_id').parentElement.style.display = 'block';
                console.log('✓ Modal closed successfully');
            } catch (error) {
                console.error('✗ Error closing modal:', error);
            }
        }

        // Form submission
        document.addEventListener('DOMContentLoaded', function() {
            const riderForm = document.getElementById('riderForm');
            if (riderForm) {
                riderForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('Form submitted');
                    
                    // Ensure full_name is populated
                    updateFullName();
                    
                    const formData = new FormData(this);
                    
                    // Debug: log form data
                    console.log('Submitting form with data:');
                    for (let pair of formData.entries()) {
                        console.log(pair[0] + ': ' + pair[1]);
                    }
                    
                    fetch('../actions/admin-rider-action.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error('HTTP error ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        alert('Failed to save rider. Error: ' + error.message + '\nCheck browser console for details.');
                    });
                });
                console.log('✓ Form submit listener attached');
            } else {
                console.error('✗ riderForm element not found');
            }
        });

        function updateStatus(riderId, newStatus) {
            console.log('updateStatus() called:', riderId, newStatus);
            
            if (!confirm('Are you sure you want to change this rider\'s status?')) {
                console.log('Status update cancelled by user');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('rider_id', riderId);
            formData.append('status', newStatus);

            console.log('Sending status update request...');
            
            fetch('../actions/admin-rider-action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Status update response:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Status update data:', data);
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update status. Error: ' + error.message);
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('riderModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
