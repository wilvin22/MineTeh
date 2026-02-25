<?php
session_start();
include '../database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all listings for this user
$listings = $supabase->select('listings', '*', ['seller_id' => $user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Listings - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            background: #f5f5f5;
        }

        #main-content {
            flex-grow: 1;
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .page-subtitle {
            font-size: 16px;
            color: #666;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #945a9b;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
        }

        .listings-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .table-header {
            background: linear-gradient(135deg, #945a9b, #6a406e);
            color: white;
            padding: 20px;
            font-size: 18px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #666;
            font-size: 14px;
            border-bottom: 2px solid #e9ecef;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .listing-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .listing-title-cell {
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-active {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-closed {
            background: #f8d7da;
            color: #842029;
        }

        .status-inactive {
            background: #e9ecef;
            color: #666;
        }

        .type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .type-bid {
            background: #fff3cd;
            color: #856404;
        }

        .type-fixed {
            background: #cfe2ff;
            color: #084298;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view {
            background: #945a9b;
            color: white;
        }

        .btn-view:hover {
            background: #6a406e;
        }

        .btn-edit {
            background: #0d6efd;
            color: white;
        }

        .btn-edit:hover {
            background: #0b5ed7;
        }

        .btn-disable {
            background: #dc3545;
            color: white;
        }

        .btn-disable:hover {
            background: #bb2d3b;
        }

        .btn-enable {
            background: #198754;
            color: white;
        }

        .btn-enable:hover {
            background: #157347;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .create-listing-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #945a9b;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .create-listing-btn:hover {
            background: #6a406e;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div id="main-content" class="main-wrapper">
        <div class="page-header">
            <div class="page-title">📦 Your Listings</div>
            <div class="page-subtitle">Manage all your items in one place</div>
        </div>

        <?php
        // Calculate stats
        $total_listings = count($listings);
        $active_listings = count(array_filter($listings, fn($l) => $l['status'] === 'active' || $l['status'] === 'OPEN'));
        $closed_listings = count(array_filter($listings, fn($l) => $l['status'] === 'CLOSED'));
        $inactive_listings = count(array_filter($listings, fn($l) => $l['status'] === 'inactive'));
        ?>

        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_listings; ?></div>
                <div class="stat-label">Total Listings</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $active_listings; ?></div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $closed_listings; ?></div>
                <div class="stat-label">Closed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $inactive_listings; ?></div>
                <div class="stat-label">Inactive</div>
            </div>
        </div>

        <div class="listings-table">
            <div class="table-header">All Your Listings</div>
            
            <?php if (empty($listings)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <h2>No listings yet</h2>
                    <p>Start selling by creating your first listing</p>
                    <a href="create-listing.php" class="create-listing-btn">➕ Create Listing</a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Price</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listings as $listing): 
                            // Get first image
                            $images = $supabase->select('listing_images', 'image_path', ['listing_id' => $listing['id']]);
                            $image_path = !empty($images) ? $images[0]['image_path'] : '../assets/no-image.png';
                            
                            $status_class = 'status-' . strtolower($listing['status']);
                            $type_class = $listing['listing_type'] === 'BID' ? 'type-bid' : 'type-fixed';
                        ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                         alt="Listing" 
                                         class="listing-thumb">
                                </td>
                                <td class="listing-title-cell">
                                    <?php echo htmlspecialchars($listing['title']); ?>
                                </td>
                                <td>₱<?php echo number_format($listing['price'], 2); ?></td>
                                <td>
                                    <span class="type-badge <?php echo $type_class; ?>">
                                        <?php echo $listing['listing_type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo strtoupper($listing['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($listing['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="listing-details.php?id=<?php echo $listing['id']; ?>" 
                                           class="btn-small btn-view">
                                            👁️ View
                                        </a>
                                        <?php if ($listing['status'] === 'active' || $listing['status'] === 'OPEN'): ?>
                                            <button onclick="disableListing(<?php echo $listing['id']; ?>)" 
                                                    class="btn-small btn-disable">
                                                🚫 Disable
                                            </button>
                                        <?php else: ?>
                                            <button onclick="enableListing(<?php echo $listing['id']; ?>)" 
                                                    class="btn-small btn-enable">
                                                ✅ Enable
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function disableListing(listingId) {
            if (!confirm('Disable this listing? It will be hidden from buyers.')) return;

            fetch('../api/manage-listing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    listing_id: listingId,
                    action: 'disable'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Listing disabled successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to disable listing'));
                }
            });
        }

        function enableListing(listingId) {
            if (!confirm('Enable this listing? It will be visible to buyers again.')) return;

            fetch('../api/manage-listing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    listing_id: listingId,
                    action: 'enable'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Listing enabled successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to enable listing'));
                }
            });
        }
    </script>
</body>
</html>
