<?php
session_start();
date_default_timezone_set('Asia/Manila');

include '../config.php';
include '../database/supabase.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

// Handle listing actions
if (isset($_POST['delete_listing'])) {
    $listing_id = (int)$_POST['listing_id'];
    $supabase->delete('listings', ['id' => $listing_id]);
    $success = "Listing deleted successfully";
}

if (isset($_POST['toggle_status'])) {
    $listing_id = (int)$_POST['listing_id'];
    $current_status = $_POST['current_status'];
    $new_status = $current_status === 'active' ? 'inactive' : 'active';
    $supabase->update('listings', ['status' => $new_status], ['id' => $listing_id]);
    $success = "Listing status updated successfully";
}

// Get all listings with seller info
$listings = $supabase->customQuery('listings', '*', 'order=created_at.desc');

// Get seller names
if (!empty($listings)) {
    foreach ($listings as &$listing) {
        $seller = $supabase->select('accounts', 'username', ['account_id' => $listing['seller_id']], true);
        $listing['seller_name'] = $seller ? $seller['username'] : 'Unknown';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listings Management - Admin</title>
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

        .badge-active {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #842029;
        }

        .badge-sold {
            background: #cfe2ff;
            color: #084298;
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
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="logo">🛡️ Admin Panel</div>
            <a href="index.php" class="nav-item">📊 Dashboard</a>
            <a href="users.php" class="nav-item">👥 Users</a>
            <a href="listings.php" class="nav-item active">📦 Listings</a>
            <a href="orders.php" class="nav-item">🛒 Orders</a>
            <a href="categories.php" class="nav-item">🏷️ Categories</a>
            <a href="../home/homepage.php" class="nav-item">🏠 View Site</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Listings Management</h1>
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
                            <th>Title</th>
                            <th>Seller</th>
                            <th>Price</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($listings)): ?>
                            <?php foreach ($listings as $listing): ?>
                                <tr>
                                    <td><?php echo $listing['id']; ?></td>
                                    <td><?php echo htmlspecialchars($listing['title']); ?></td>
                                    <td><?php echo htmlspecialchars($listing['seller_name']); ?></td>
                                    <td>₱<?php echo number_format($listing['price'], 2); ?></td>
                                    <td><?php echo $listing['listing_type']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($listing['status']); ?>">
                                            <?php echo ucfirst($listing['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($listing['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $listing['status']; ?>">
                                            <button type="submit" name="toggle_status" class="btn btn-primary">
                                                <?php echo $listing['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                            <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                            <button type="submit" name="delete_listing" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
