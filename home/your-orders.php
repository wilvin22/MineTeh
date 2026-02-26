<?php
session_start();
include '../database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all orders for this user
$orders = $supabase->customQuery('orders', '*', 'buyer_id=eq.' . $user_id . '&order=created_at.desc');

// Organize orders by status
$pending_orders = [];
$completed_orders = [];
$cancelled_orders = [];

if (!empty($orders)) {
    foreach ($orders as $order) {
        // Get listing details
        $listing = $supabase->select('listings', '*', ['id' => $order['listing_id']], true);
        
        if ($listing) {
            // Get first image
            $images = $supabase->select('listing_images', 'image_path', ['listing_id' => $listing['id']]);
            $listing['image'] = !empty($images) ? $images[0]['image_path'] : '../assets/no-image.png';
            
            $order['listing'] = $listing;
            
            // Categorize by status
            if ($order['status'] === 'pending') {
                $pending_orders[] = $order;
            } elseif ($order['status'] === 'completed') {
                $completed_orders[] = $order;
            } else {
                $cancelled_orders[] = $order;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Orders - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        body {
            background: #f5f5f5;
        }

        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .page-subtitle {
            font-size: 16px;
            color: #666;
        }

        .tabs-container {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0;
        }

        .tab-button {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            bottom: -2px;
        }

        .tab-button:hover {
            color: #945a9b;
        }

        .tab-button.active {
            color: #945a9b;
            border-bottom-color: #945a9b;
        }

        .tab-badge {
            display: inline-block;
            background: #e9ecef;
            color: #666;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 6px;
        }

        .tab-button.active .tab-badge {
            background: #945a9b;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .order-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 15px;
        }

        .order-id {
            font-size: 14px;
            color: #666;
        }

        .order-date {
            font-size: 14px;
            color: #999;
        }

        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .order-content {
            display: flex;
            gap: 20px;
        }

        .order-image {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .order-details {
            flex-grow: 1;
        }

        .order-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 14px;
            color: #666;
        }

        .order-price {
            font-size: 20px;
            font-weight: bold;
            color: #945a9b;
            margin-top: 10px;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #945a9b;
            color: white;
        }

        .btn-primary:hover {
            background: #6a406e;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
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

        .empty-state h3 {
            font-size: 24px;
            color: #666;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 16px;
            color: #999;
        }

        .browse-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #945a9b;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .browse-btn:hover {
            background: #6a406e;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .order-content {
                flex-direction: column;
            }

            .order-image {
                width: 100%;
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="orders-container">
            <div class="page-header">
                <div class="page-title">📦 Your Orders</div>
                <div class="page-subtitle">Track and manage all your purchases</div>
            </div>

            <div class="tabs-container">
                <button class="tab-button active" onclick="showTab('pending')">
                    ⏳ Pending
                    <span class="tab-badge"><?php echo count($pending_orders); ?></span>
                </button>
                <button class="tab-button" onclick="showTab('completed')">
                    ✅ Completed
                    <span class="tab-badge"><?php echo count($completed_orders); ?></span>
                </button>
                <button class="tab-button" onclick="showTab('cancelled')">
                    ❌ Cancelled
                    <span class="tab-badge"><?php echo count($cancelled_orders); ?></span>
                </button>
            </div>

            <!-- Pending Orders Tab -->
            <div id="pending-tab" class="tab-content active">
                <?php if (empty($pending_orders)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">⏳</div>
                        <h3>No Pending Orders</h3>
                        <p>You don't have any pending orders</p>
                        <a href="homepage.php" class="browse-btn">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($pending_orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div>
                                        <span class="order-id">Order #<?php echo $order['order_id']; ?></span>
                                        <span class="order-status status-pending">Pending</span>
                                    </div>
                                    <div class="order-date">
                                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="order-content">
                                    <img src="<?php echo htmlspecialchars($order['listing']['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($order['listing']['title']); ?>" 
                                         class="order-image">
                                    <div class="order-details">
                                        <div class="order-title"><?php echo htmlspecialchars($order['listing']['title']); ?></div>
                                        <div class="order-info">
                                            <div>📍 <?php echo htmlspecialchars($order['listing']['location']); ?></div>
                                            <div>📅 Ordered: <?php echo date('M d, Y g:i A', strtotime($order['created_at'])); ?></div>
                                        </div>
                                        <div class="order-price">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                                        <div class="order-actions">
                                            <a href="listing-details.php?id=<?php echo $order['listing_id']; ?>" class="btn btn-primary">View Item</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Completed Orders Tab -->
            <div id="completed-tab" class="tab-content">
                <?php if (empty($completed_orders)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">✅</div>
                        <h3>No Completed Orders</h3>
                        <p>You haven't completed any orders yet</p>
                        <a href="homepage.php" class="browse-btn">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($completed_orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div>
                                        <span class="order-id">Order #<?php echo $order['order_id']; ?></span>
                                        <span class="order-status status-completed">Completed</span>
                                    </div>
                                    <div class="order-date">
                                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="order-content">
                                    <img src="<?php echo htmlspecialchars($order['listing']['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($order['listing']['title']); ?>" 
                                         class="order-image">
                                    <div class="order-details">
                                        <div class="order-title"><?php echo htmlspecialchars($order['listing']['title']); ?></div>
                                        <div class="order-info">
                                            <div>📍 <?php echo htmlspecialchars($order['listing']['location']); ?></div>
                                            <div>📅 Completed: <?php echo date('M d, Y g:i A', strtotime($order['updated_at'])); ?></div>
                                        </div>
                                        <div class="order-price">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                                        <div class="order-actions">
                                            <a href="listing-details.php?id=<?php echo $order['listing_id']; ?>" class="btn btn-secondary">View Item</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Cancelled Orders Tab -->
            <div id="cancelled-tab" class="tab-content">
                <?php if (empty($cancelled_orders)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">❌</div>
                        <h3>No Cancelled Orders</h3>
                        <p>You haven't cancelled any orders</p>
                        <a href="homepage.php" class="browse-btn">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($cancelled_orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div>
                                        <span class="order-id">Order #<?php echo $order['order_id']; ?></span>
                                        <span class="order-status status-cancelled">Cancelled</span>
                                    </div>
                                    <div class="order-date">
                                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="order-content">
                                    <img src="<?php echo htmlspecialchars($order['listing']['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($order['listing']['title']); ?>" 
                                         class="order-image">
                                    <div class="order-details">
                                        <div class="order-title"><?php echo htmlspecialchars($order['listing']['title']); ?></div>
                                        <div class="order-info">
                                            <div>📍 <?php echo htmlspecialchars($order['listing']['location']); ?></div>
                                            <div>📅 Cancelled: <?php echo date('M d, Y g:i A', strtotime($order['updated_at'])); ?></div>
                                        </div>
                                        <div class="order-price">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                                        <div class="order-actions">
                                            <a href="listing-details.php?id=<?php echo $order['listing_id']; ?>" class="btn btn-secondary">View Item</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
