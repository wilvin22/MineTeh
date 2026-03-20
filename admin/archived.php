<?php
session_start();
date_default_timezone_set('Asia/Manila');

include '../config.php';
include '../database/supabase.php';

if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_is_admin']) || !$_SESSION['admin_is_admin']) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['restore_listing'])) {
    $listing_id = (int)$_POST['listing_id'];
    $supabase->update('listings', ['status' => 'inactive'], ['id' => $listing_id]);
    $success = "Listing restored successfully";
}

// Only archived listings
$listings = $supabase->customQuery('listings', '*', 'status=eq.archived&order=created_at.desc');

if (!empty($listings)) {
    foreach ($listings as &$listing) {
        $seller = $supabase->select('accounts', 'username', ['account_id' => $listing['seller_id']], true);
        $listing['seller_name'] = $seller ? $seller['username'] : 'Unknown';
    }
    unset($listing);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Listings - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 20px; position: fixed; height: 100vh; overflow-y: auto;
        }
        .logo { font-size: 24px; font-weight: bold; margin-bottom: 40px; text-align: center; }
        .nav-item {
            padding: 15px 20px; margin-bottom: 10px; border-radius: 10px;
            cursor: pointer; transition: all 0.3s ease; text-decoration: none;
            color: white; display: block;
        }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.2); }
        .main-content { margin-left: 260px; flex: 1; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-size: 32px; color: #333; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow-x: auto; }
        .success { background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .empty { text-align: center; padding: 60px 20px; color: #999; }
        .empty-icon { font-size: 48px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: bold; color: #333; }
        .badge-archived { background: #e9ecef; color: #495057; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .btn {
            padding: 7px 0; border: none; border-radius: 6px; cursor: pointer;
            font-size: 12px; font-weight: 600; white-space: nowrap;
            width: 110px; text-align: center; display: block;
        }
        .btn-restore { background: #28a745; color: white; }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="sidebar">
        <div class="logo">🛡️ Admin Panel</div>
        <a href="index.php" class="nav-item">📊 Dashboard</a>
        <a href="users.php" class="nav-item">👥 Users</a>
        <a href="listings.php" class="nav-item">📦 Listings</a>
        <a href="archived.php" class="nav-item active">🗄️ Archived</a>
        <a href="logout.php" class="nav-item" style="margin-top:20px;background:rgba(231,76,60,0.3);">🚪 Logout</a>
    </div>

    <div class="main-content">
        <div class="header"><h1>Archived Listings</h1></div>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <?php if (empty($listings)): ?>
                <div class="empty">
                    <div class="empty-icon">🗄️</div>
                    <p>No archived listings yet.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Title</th><th>Seller</th><th>Price</th>
                            <th>Type</th><th>Status</th><th>Created</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listings as $listing): ?>
                            <tr>
                                <td><?php echo $listing['id']; ?></td>
                                <td><?php echo htmlspecialchars($listing['title']); ?></td>
                                <td><?php echo htmlspecialchars($listing['seller_name']); ?></td>
                                <td>₱<?php echo number_format($listing['price'], 2); ?></td>
                                <td><?php echo $listing['listing_type']; ?></td>
                                <td><span class="badge-archived">Archived</span></td>
                                <td><?php echo date('M d, Y', strtotime($listing['created_at'])); ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                        <button type="submit" name="restore_listing" class="btn btn-restore">Restore</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
