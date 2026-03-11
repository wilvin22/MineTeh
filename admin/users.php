<?php
session_start();
date_default_timezone_set('Asia/Manila');

include '../config.php';
include '../database/supabase.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

// Handle user actions
if (isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    $supabase->delete('accounts', ['account_id' => $user_id]);
    header("Location: users.php?success=" . urlencode("User deleted successfully"));
    exit;
}

if (isset($_POST['toggle_admin'])) {
    $user_id = (int)$_POST['user_id'];
    $is_admin = $_POST['is_admin'] === 'true' ? false : true;
    $supabase->update('accounts', ['is_admin' => $is_admin], ['account_id' => $user_id]);
    header("Location: users.php?success=" . urlencode("User role updated successfully"));
    exit;
}

// Check for success message from redirect
$success = isset($_GET['success']) ? $_GET['success'] : null;

if (isset($_POST['change_status'])) {
    $user_id = (int)$_POST['user_id'];
    $new_status = $_POST['new_status'];
    $reason = isset($_POST['status_reason']) ? trim($_POST['status_reason']) : '';
    $restriction_until = null;
    
    // Handle restriction duration
    if ($new_status === 'restricted' && !empty($_POST['restriction_duration'])) {
        $duration = $_POST['restriction_duration'];
        if ($duration !== 'permanent') {
            $restriction_until = date('Y-m-d H:i:s', strtotime('+' . $duration));
        }
    }
    
    $update_data = [
        'user_status' => $new_status,
        'status_reason' => $reason,
        'restriction_until' => $restriction_until
    ];
    
    $supabase->update('accounts', $update_data, ['account_id' => $user_id]);
    
    // Redirect to prevent form resubmission
    header("Location: users.php?success=" . urlencode("User status updated to " . ucfirst($new_status)));
    exit;
}

// Check for expired restrictions and auto-reactivate
$restricted_users = $supabase->select('accounts', '*', ['user_status' => 'restricted']);
if (!empty($restricted_users)) {
    foreach ($restricted_users as $user) {
        if (!empty($user['restriction_until'])) {
            $restriction_time = strtotime($user['restriction_until']);
            if ($restriction_time <= time()) {
                // Restriction expired, reactivate user
                $supabase->update('accounts', [
                    'user_status' => 'active',
                    'restriction_until' => null,
                    'status_reason' => null
                ], ['account_id' => $user['account_id']]);
            }
        }
    }
}

// Get all users
$users = $supabase->customQuery('accounts', '*', 'order=created_at.desc');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 40px;
            text-align: center;
        }

        .nav-item {
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            display: block;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.2);
        }

        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            color: #333;
        }

        .logout-btn {
            padding: 10px 20px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .success {
            background: #d1e7dd;
            color: #0f5132;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-admin {
            background: #667eea;
            color: white;
        }

        .badge-user {
            background: #e9ecef;
            color: #495057;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-active {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-banned {
            background: #f8d7da;
            color: #721c24;
        }

        .status-restricted {
            background: #fff3cd;
            color: #856404;
        }

        .status-archived {
            background: #e9ecef;
            color: #495057;
        }

        .status-dropdown {
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideDown 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-modal {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-cancel {
            background: #e9ecef;
            color: #495057;
        }

        .btn-submit {
            background: #667eea;
            color: white;
        }

        .btn-submit:hover {
            background: #5568d3;
        }

        .status-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .restriction-duration {
            display: none;
            margin-top: 10px;
        }

        .restriction-duration.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="logo">🛡️ Admin Panel</div>
            <a href="index.php" class="nav-item">📊 Dashboard</a>
            <a href="users.php" class="nav-item active">👥 Users</a>
            <a href="riders.php" class="nav-item">🏍️ Riders</a>
            <a href="delivery-monitor.php" class="nav-item">📊 Delivery Monitor</a>
            <a href="listings.php" class="nav-item">📦 Listings</a>
            <a href="orders.php" class="nav-item">🛒 Orders</a>
            <a href="categories.php" class="nav-item">🏷️ Categories</a>
            <a href="../home/homepage.php" class="nav-item">🏠 View Site</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Users Management</h1>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>

            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <?php 
                                    $status = isset($user['user_status']) ? $user['user_status'] : 'active';
                                    $status_class = 'status-' . $status;
                                    $restriction_until = isset($user['restriction_until']) ? $user['restriction_until'] : null;
                                    $status_reason = isset($user['status_reason']) ? $user['status_reason'] : '';
                                ?>
                                <tr>
                                    <td><?php echo $user['account_id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['is_admin'] ? 'admin' : 'user'; ?>">
                                            <?php echo $user['is_admin'] ? 'Admin' : 'User'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>" 
                                              title="<?php echo $status_reason ? 'Reason: ' . htmlspecialchars($status_reason) : ''; ?>">
                                            <?php 
                                                echo ucfirst($status);
                                                if ($status === 'restricted' && $restriction_until) {
                                                    $until_time = strtotime($restriction_until);
                                                    if ($until_time > time()) {
                                                        echo ' (until ' . date('M d, Y', $until_time) . ')';
                                                    }
                                                }
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['account_id'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-warning" 
                                                    onclick="openStatusModal(<?php echo $user['account_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo $status; ?>')">
                                                Change Status
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['account_id']; ?>">
                                                <input type="hidden" name="is_admin" value="<?php echo $user['is_admin'] ? 'true' : 'false'; ?>">
                                                <button type="submit" name="toggle_admin" class="btn btn-primary">
                                                    <?php echo $user['is_admin'] ? 'Remove Admin' : 'Make Admin'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                                <input type="hidden" name="user_id" value="<?php echo $user['account_id']; ?>">
                                                <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Change User Status</div>
            <form method="POST" id="statusForm">
                <input type="hidden" name="user_id" id="modal_user_id">
                <input type="hidden" name="change_status" value="1">
                
                <div class="modal-body">
                    <p style="margin-bottom: 15px; color: #666;">
                        Changing status for: <strong id="modal_username"></strong>
                    </p>
                    
                    <div class="form-group">
                        <label>New Status</label>
                        <select name="new_status" id="modal_status" required onchange="toggleRestrictionDuration()">
                            <option value="">-- Select Status --</option>
                            <option value="active">✓ Active - Full access</option>
                            <option value="restricted">⚠️ Restricted - Cannot create listings or bid</option>
                            <option value="banned">🚫 Banned - Cannot login</option>
                        </select>
                    </div>
                    
                    <div class="form-group restriction-duration" id="restriction_duration_group">
                        <label>Restriction Duration</label>
                        <select name="restriction_duration" id="restriction_duration">
                            <option value="1 day">1 Day</option>
                            <option value="3 days">3 Days</option>
                            <option value="7 days">1 Week</option>
                            <option value="14 days">2 Weeks</option>
                            <option value="30 days">1 Month</option>
                            <option value="permanent">Permanent</option>
                        </select>
                        <div class="status-info">User will be automatically reactivated after this period (except permanent)</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Reason / Remarks</label>
                        <textarea name="status_reason" id="modal_reason" placeholder="Explain why this action is being taken..." required></textarea>
                        <div class="status-info">This will be visible to other admins</div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeStatusModal()">Cancel</button>
                    <button type="submit" class="btn-modal btn-submit">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStatusModal(userId, username, currentStatus) {
            document.getElementById('modal_user_id').value = userId;
            document.getElementById('modal_username').textContent = username;
            document.getElementById('modal_status').value = currentStatus;
            document.getElementById('modal_reason').value = '';
            toggleRestrictionDuration();
            document.getElementById('statusModal').style.display = 'block';
        }

        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        function toggleRestrictionDuration() {
            const status = document.getElementById('modal_status').value;
            const durationGroup = document.getElementById('restriction_duration_group');
            const reasonField = document.getElementById('modal_reason');
            
            if (status === 'restricted') {
                durationGroup.classList.add('show');
                reasonField.required = true;
            } else {
                durationGroup.classList.remove('show');
                reasonField.required = status !== 'active';
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target == modal) {
                closeStatusModal();
            }
        }
    </script>
</body>
</html>
