<?php
session_start();
date_default_timezone_set('Asia/Manila');

include '../config.php';
include '../database/supabase.php';

if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_is_admin']) || !$_SESSION['admin_is_admin']) {
    header("Location: login.php");
    exit;
}

// Stats from correct tables
$total_users    = $supabase->count('accounts', []);
$total_listings = $supabase->count('listings', []);
$active_listings = $supabase->count('listings', ['status' => 'OPEN']);
$total_bids     = $supabase->count('bids', []);

// Recent users
$recent_users = $supabase->customQuery('accounts', '*', 'order=created_at.desc&limit=5');
if (!$recent_users) $recent_users = [];

// Recent listings
$recent_listings = $supabase->customQuery('listings', '*', 'order=created_at.desc&limit=5');
if (!$recent_listings) $recent_listings = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MineTeh</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

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

        .nav-item.logout {
            margin-top: auto;
            background: rgba(231,76,60,0.3);
        }

        .nav-item.logout:hover {
            background: rgba(231,76,60,0.6);
        }

        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #333;
        }

        .admin-name {
            font-size: 14px;
            color: #666;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .stat-icon { font-size: 36px; margin-bottom: 12px; }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 4px;
        }

        .stat-label {
            color: #888;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .section-title {
            font-size: 17px;
            font-weight: 600;
            margin-bottom: 18px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .list-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .list-item:last-child { border-bottom: none; }

        .item-title {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .item-meta {
            font-size: 12px;
            color: #999;
            margin-top: 3px;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-active   { background: #d1e7dd; color: #0f5132; }
        .badge-open     { background: #d1e7dd; color: #0f5132; }
        .badge-inactive { background: #f8d7da; color: #842029; }
        .badge-closed   { background: #f8d7da; color: #842029; }
        .badge-sold     { background: #cfe2ff; color: #084298; }
        .badge-banned   { background: #f8d7da; color: #842029; }
        .badge-restricted { background: #fff3cd; color: #856404; }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: #bbb;
            font-size: 14px;
        }

        @media (max-width: 900px) {
            .content-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="sidebar">
        <div class="logo">🛡️ Admin Panel</div>
        <a href="index.php" class="nav-item active">📊 Dashboard</a>
        <a href="users.php" class="nav-item">👥 Users</a>
        <a href="listings.php" class="nav-item">📦 Listings</a>
        <a href="archived.php" class="nav-item">🗄️ Archived</a>
        <a href="logout.php" class="nav-item logout">🚪 Logout</a>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>Dashboard</h1>
            <span class="admin-name">Logged in as <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo number_format($total_users); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?php echo number_format($total_listings); ?></div>
                <div class="stat-label">Total Listings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo number_format($active_listings); ?></div>
                <div class="stat-label">Active Listings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔨</div>
                <div class="stat-value"><?php echo number_format($total_bids); ?></div>
                <div class="stat-label">Total Bids</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="section">
                <div class="section-title">Recent Users</div>
                <?php if (empty($recent_users)): ?>
                    <div class="empty-state">No users yet</div>
                <?php else: ?>
                    <?php foreach ($recent_users as $u): ?>
                        <div class="list-item">
                            <div>
                                <div class="item-title"><?php echo htmlspecialchars($u['username']); ?></div>
                                <div class="item-meta"><?php echo htmlspecialchars($u['email']); ?> &bull; <?php echo date('M d, Y', strtotime($u['created_at'])); ?></div>
                            </div>
                            <?php $st = $u['user_status'] ?? 'active'; ?>
                            <span class="badge badge-<?php echo $st; ?>"><?php echo ucfirst($st); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="section">
                <div class="section-title">Recent Listings</div>
                <?php if (empty($recent_listings)): ?>
                    <div class="empty-state">No listings yet</div>
                <?php else: ?>
                    <?php foreach ($recent_listings as $l): ?>
                        <div class="list-item">
                            <div>
                                <div class="item-title"><?php echo htmlspecialchars($l['title']); ?></div>
                                <div class="item-meta">₱<?php echo number_format($l['price'], 2); ?> &bull; <?php echo $l['listing_type']; ?> &bull; <?php echo date('M d, Y', strtotime($l['created_at'])); ?></div>
                            </div>
                            <?php $st = strtolower($l['status']); ?>
                            <span class="badge badge-<?php echo $st; ?>"><?php echo ucfirst($st); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
