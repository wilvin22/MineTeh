<?php
session_start();
date_default_timezone_set('Asia/Manila');

include '../config.php';
include '../database/supabase.php';

// Check if user is admin using admin-specific session variables
if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_is_admin']) || !$_SESSION['admin_is_admin']) {
    header("Location: login.php");
    exit;
}

// Handle category actions
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    $slug = strtolower(str_replace(' ', '-', $name));
    $icon = trim($_POST['category_icon']);
    
    $supabase->insert('categories', [
        'category_name' => $name,
        'category_slug' => $slug,
        'category_icon' => $icon,
        'is_active' => true
    ]);
    $success = "Category added successfully";
}

if (isset($_POST['delete_category'])) {
    $category_id = (int)$_POST['category_id'];
    $supabase->delete('categories', ['category_id' => $category_id]);
    $success = "Category deleted successfully";
}

if (isset($_POST['toggle_active'])) {
    $category_id = (int)$_POST['category_id'];
    $is_active = $_POST['is_active'] === 'true' ? false : true;
    $supabase->update('categories', ['is_active' => $is_active], ['category_id' => $category_id]);
    $success = "Category status updated successfully";
}

// Get all categories
$categories = $supabase->select('categories', '*', []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Admin</title>
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
            margin-bottom: 20px;
        }

        .success {
            background: #d1e7dd;
            color: #0f5132;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
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

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
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

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="logo">🛡️ Admin Panel</div>
            <a href="index.php" class="nav-item">📊 Dashboard</a>
            <a href="users.php" class="nav-item">👥 Users</a>
            <a href="listings.php" class="nav-item">📦 Listings</a>
            <a href="orders.php" class="nav-item">📈 Monitor</a>
            <a href="categories.php" class="nav-item active">🏷️ Categories</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Categories Management</h1>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>

            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card">
                <h2 style="margin-bottom: 20px;">Add New Category</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Category Name</label>
                        <input type="text" name="category_name" required placeholder="e.g., Electronics">
                    </div>
                    <div class="form-group">
                        <label>Category Icon (Emoji)</label>
                        <input type="text" name="category_icon" required placeholder="e.g., 📱">
                    </div>
                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                </form>
            </div>

            <div class="card">
                <h2 style="margin-bottom: 20px;">All Categories</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['category_id']; ?></td>
                                    <td><?php echo $category['category_icon']; ?></td>
                                    <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($category['category_slug']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $category['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $category['is_active'] ? 'true' : 'false'; ?>">
                                            <button type="submit" name="toggle_active" class="btn btn-primary btn-small">
                                                <?php echo $category['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                            <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                            <button type="submit" name="delete_category" class="btn btn-danger btn-small">Delete</button>
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
