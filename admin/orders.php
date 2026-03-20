<?php
session_start();
date_default_timezone_set('Asia/Manila');

include '../config.php';
include '../database/supabase.php';

if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_is_admin']) || !$_SESSION['admin_is_admin']) {
    header("Location: login.php");
    exit;
}

// Filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_type   = isset($_GET['type'])   ? $_GET['type']   : 'all';

// Build query
$query = 'order=created_at.desc';
if ($filter_status !== 'all') $query .= '&status=eq.' . $filter_status;
if ($filter_type   !== 'all') $query .= '&listing_type=eq.' . strtoupper($filter_type);

$listings = $supabase->customQuery('listings', '*', $query);

// Enrich with seller info
if (!empty($listings)) {
    foreach ($listings as &$listing) {
        $seller = $supabase->select('accounts', 'username', ['account_id' => $listing['seller_id']], true);
        $listing['seller_name'] = $seller ? $seller['username'] : 'Unknown';
    }
}

// Stats
$all_listings = $supabase->customQuery('listings', 'id,status,listing_type', 'order=created_at.desc');
$total   = is_array($all_listings) ? count($all_listings) : 0;
$active  = is_array($all_listings) ? count(array_filter($all_listings, fn($l) => $l['status'] === 'active'))   : 0;
$sold    = is_array($all_listings) ? count(array_filter($all_listings, fn($l) => $l['status'] === 'sold'))     : 0;
$inactive = is_array($all_listings) ? count(array_filter($all_listings, fn($l) => $l['status'] === 'inactive')) : 0;
$bid_type = is_array($all_listings) ? count(array_filter($all_listings, fn($l) => $l['listing_type'] === 'BID')) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listings Monitor - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; position: fixed; height: 100vh; overflow-y: auto; }
        .logo { font-size: 24px; font-weight: bold; margin-bottom: 40px; text-align: center; }
        .nav-item { padding: 15px 20px; margin-bottom: 10px; border-radius: 10px; cursor: pointer; transition: all 0.3s ease; text-decoration: none; color: white; display: block; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.2); }
        .main-content { margin-left: 260px; flex: 1; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-size: 32px; color: #333; }
        .logout-btn { padding: 10px 20px; background: #e74c3c; color: white; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }

        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; border-top: 4px solid #667eea; }
        .stat-card.green  { border-top-color: #28a745; }
        .stat-card.blue   { border-top-color: #084298; }
        .stat-card.red    { border-top-color: #dc3545; }
        .stat-card.yellow { border-top-color: #ffc107; }
        .stat-number { font-size: 32px; font-weight: bold; color: #333; }
        .stat-label  { font-size: 13px; color: #666; margin-top: 4px; }

        .filters { background: white; padding: 18px 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .filters label { font-weight: bold; font-size: 14px; color: #333; }
        .filters select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        .filters a { padding: 8px 16px; background: #667eea; color: white; border-radius: 8px; text-decoration: none; font-size: 14px; }
        .filters a:hover { background: #5a6fd6; }
        .filters a.reset { background: #6c757d; }

        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f8f9fa; font-weight: bold; color: #333; }
        tr:hover td { background: #fafafa; }

        .badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-active   { background: #d1e7dd; color: #0f5132; }
        .badge-inactive { background: #f8d7da; color: #842029; }
        .badge-sold     { background: #cfe2ff; color: #084298; }
        .badge-bid      { background: #fff3cd; color: #856404; }
        .badge-fixed    { background: #e2e3e5; color: #41464b; }

        .empty { text-align: center; padding: 40px; color: #999; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="logo">🛡️ Admin Panel</div>
            <a href="index.php" class="nav-item">📊 Dashboard</a>
            <a href="users.php" class="nav-item">👥 Users</a>
            <a href="listings.php" class="nav-item">📦 Listings</a>
            <a href="orders.php" class="nav-item active">📈 Monitor</a>
            <a href="categories.php" class="nav-item">🏷️ Categories</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>📈 Listings Monitor</h1>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>

            <!-- Stats -->
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total; ?></div>
                    <div class="stat-label">Total Listings</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-number"><?php echo $active; ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-number"><?php echo $sold; ?></div>
                    <div class="stat-label">Sold</div>
                </div>
                <div class="stat-card red">
                    <div class="stat-number"><?php echo $inactive; ?></div>
                    <div class="stat-label">Inactive</div>
                </div>
                <div class="stat-card yellow">
                    <div class="stat-number"><?php echo $bid_type; ?></div>
                    <div class="stat-label">Auction Listings</div>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" action="">
                <div class="filters">
                    <label>Status:</label>
                    <select name="status">
                        <option value="all"      <?php echo $filter_status === 'all'      ? 'selected' : ''; ?>>All</option>
                        <option value="active"   <?php echo $filter_status === 'active'   ? 'selected' : ''; ?>>Active</option>
                        <option value="sold"     <?php echo $filter_status === 'sold'     ? 'selected' : ''; ?>>Sold</option>
                        <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>

                    <label>Type:</label>
                    <select name="type">
                        <option value="all"   <?php echo $filter_type === 'all'   ? 'selected' : ''; ?>>All</option>
                        <option value="fixed" <?php echo $filter_type === 'fixed' ? 'selected' : ''; ?>>Fixed Price</option>
                        <option value="bid"   <?php echo $filter_type === 'bid'   ? 'selected' : ''; ?>>Auction/Bid</option>
                    </select>

                    <button type="submit" style="padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px;">Filter</button>
                    <a href="orders.php" class="reset">Reset</a>
                </div>
            </form>

            <!-- Table -->
            <div class="card">
                <?php if (!empty($listings)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Seller</th>
                            <th>Price</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Location</th>
                            <th>Posted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listings as $listing): ?>
                            <tr>
                                <td><?php echo $listing['id']; ?></td>
                                <td><?php echo htmlspecialchars($listing['title']); ?></td>
                                <td><?php echo htmlspecialchars($listing['seller_name']); ?></td>
                                <td>₱<?php echo number_format($listing['price'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($listing['listing_type']); ?>">
                                        <?php echo $listing['listing_type'] === 'BID' ? 'Auction' : 'Fixed'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($listing['status']); ?>">
                                        <?php echo ucfirst($listing['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($listing['location'] ?? '—'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($listing['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty">No listings found for the selected filters.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
