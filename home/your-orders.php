<?php
session_start();

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

include '../config.php';
include '../database/supabase.php';
include '../database/notifications_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    // Verify seller owns this order
    $order = $supabase->select('orders', '*', ['order_id' => $order_id, 'seller_id' => $user_id], true);
    
    if ($order) {
        // Update order status
        $result = $supabase->update('orders', 
            ['order_status' => $new_status, 'updated_at' => date('Y-m-d H:i:s')], 
            ['order_id' => $order_id]
        );
        
        if ($result) {
            // Get listing details for notification
            $listing = $supabase->select('listings', 'title', ['id' => $order['listing_id']], true);
            
            // Notify buyer of status change
            $notificationHelper = new NotificationsHelper();
            $notificationHelper->notifyOrderUpdate(
                $order['buyer_id'],
                $order_id,
                $new_status,
                $listing['title']
            );
            
            $success_message = "Order status updated successfully!";
        }
    }
}

// Determine view mode (buyer or seller)
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'buyer';

// Count pending orders for badges
$buyer_pending_count = 0;
$seller_new_count = 0;

// Get buyer pending orders count
$buyer_orders = $supabase->customQuery('orders', 'order_id,order_status', 'buyer_id=eq.' . $user_id);
if (!empty($buyer_orders)) {
    foreach ($buyer_orders as $order) {
        if ($order['order_status'] === 'processing' || $order['order_status'] === 'shipped') {
            $buyer_pending_count++;
        }
    }
}

// Get seller new orders count
$seller_orders = $supabase->customQuery('orders', 'order_id,order_status', 'seller_id=eq.' . $user_id);
if (!empty($seller_orders)) {
    foreach ($seller_orders as $order) {
        if ($order['order_status'] === 'pending' || $order['order_status'] === 'processing') {
            $seller_new_count++;
        }
    }
}

// Fetch orders based on view mode
if ($view_mode === 'seller') {
    $orders = $supabase->customQuery('orders', '*', 'seller_id=eq.' . $user_id . '&order=order_date.desc');
} else {
    $orders = $supabase->customQuery('orders', '*', 'buyer_id=eq.' . $user_id . '&order=order_date.desc');
}

// Process orders and get related data
$processed_orders = [];
if (!empty($orders)) {
    foreach ($orders as $order) {
        // Get listing details
        $listing = $supabase->select('listings', '*', ['id' => $order['listing_id']], true);
        
        if ($listing) {
            // Get listing image
            $images = $supabase->select('listing_images', 'image_path', ['listing_id' => $listing['id']]);
            $order['image'] = !empty($images) ? getImageUrl($images[0]['image_path']) : BASE_URL . '/assets/no-image.png';
            
            // Get other party info
            if ($view_mode === 'seller') {
                $buyer = $supabase->select('accounts', 'username,first_name,last_name', ['account_id' => $order['buyer_id']], true);
                $order['other_party'] = $buyer ? $buyer['username'] : 'Unknown';
            } else {
                $seller = $supabase->select('accounts', 'username,first_name,last_name', ['account_id' => $order['seller_id']], true);
                $order['other_party'] = $seller ? $seller['username'] : 'Unknown';
            }
            
            $order['listing_title'] = $listing['title'];
            $order['listing_location'] = $listing['location'];
            $processed_orders[] = $order;
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .page-subtitle {
            font-size: 16px;
            color: #666;
        }

        .view-toggle {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .toggle-btn {
            padding: 12px 30px;
            border: 2px solid #945a9b;
            background: white;
            color: #945a9b;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            position: relative;
        }

        .toggle-btn:hover {
            background: #f8f4f9;
            transform: translateY(-2px);
        }

        .toggle-btn.active {
            background: #945a9b;
            color: white;
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .order-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .order-card:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .order-id {
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }

        .order-date {
            color: #666;
            font-size: 14px;
        }

        .order-status {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .status-pending, .status-processing {
            background: #fff3cd;
            color: #856404;
        }

        .status-shipped {
            background: #cfe2ff;
            color: #084298;
        }

        .status-delivered, .status-completed {
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
            object-fit: cover;
            border-radius: 12px;
            flex-shrink: 0;
        }

        .order-details {
            flex: 1;
        }

        .order-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }

        .order-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-price {
            font-size: 24px;
            font-weight: bold;
            color: #945a9b;
            text-align: right;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            border: none;
            font-size: 14px;
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
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .empty-text {
            color: #666;
            margin-bottom: 20px;
        }

        .delivery-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .close-modal {
            font-size: 28px;
            cursor: pointer;
            color: #666;
            background: none;
            border: none;
        }

        .address-content {
            white-space: pre-line;
            line-height: 1.8;
            color: #333;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        @media (max-width: 768px) {
            .order-content {
                flex-direction: column;
            }

            .order-image {
                width: 100%;
                height: 200px;
            }

            .order-price {
                text-align: left;
                margin-top: 15px;
            }

            .order-actions {
                justify-content: flex-start;
            }

            .view-toggle {
                flex-direction: column;
            }

            .toggle-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="orders-container">
            <div class="page-header">
                <h1 class="page-title">📦 Your Orders</h1>
                <p class="page-subtitle">Track and manage your orders</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div style="background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
                    ✓ <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <div class="view-toggle">
                <a href="?view=buyer" class="toggle-btn <?php echo $view_mode === 'buyer' ? 'active' : ''; ?>">
                    🛒 My Purchases
                    <?php if ($buyer_pending_count > 0): ?>
                        <span class="badge"><?php echo $buyer_pending_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?view=seller" class="toggle-btn <?php echo $view_mode === 'seller' ? 'active' : ''; ?>">
                    💰 My Sales
                    <?php if ($seller_new_count > 0): ?>
                        <span class="badge"><?php echo $seller_new_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <?php if (empty($processed_orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <h2 class="empty-title">No Orders Yet</h2>
                    <p class="empty-text">
                        <?php if ($view_mode === 'buyer'): ?>
                            You haven't made any purchases yet. Start shopping now!
                        <?php else: ?>
                            You haven't received any orders yet. Keep your listings active!
                        <?php endif; ?>
                    </p>
                    <a href="homepage.php" class="btn btn-primary">Browse Listings</a>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($processed_orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div class="order-id">Order #<?php echo $order['order_id']; ?></div>
                                    <div class="order-date">
                                        <?php echo date('M d, Y g:i A', strtotime($order['order_date'])); ?>
                                    </div>
                                </div>
                                <span class="order-status status-<?php echo strtolower($order['order_status']); ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>

                            <div class="order-content">
                                <img src="<?php echo htmlspecialchars($order['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($order['listing_title']); ?>" 
                                     class="order-image">
                                
                                <div class="order-details">
                                    <h3 class="order-title"><?php echo htmlspecialchars($order['listing_title']); ?></h3>
                                    
                                    <div class="order-info">
                                        <div class="order-info-item">
                                            <span>👤</span>
                                            <span>
                                                <?php if ($view_mode === 'buyer'): ?>
                                                    Seller: <?php echo htmlspecialchars($order['other_party']); ?>
                                                <?php else: ?>
                                                    Buyer: <?php echo htmlspecialchars($order['other_party']); ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="order-info-item">
                                            <span>📍</span>
                                            <span><?php echo htmlspecialchars($order['listing_location']); ?></span>
                                        </div>
                                        <div class="order-info-item">
                                            <span>💳</span>
                                            <span>Payment: <?php echo ucfirst($order['payment_method']); ?> (<?php echo ucfirst($order['payment_status']); ?>)</span>
                                        </div>
                                        <div class="order-info-item">
                                            <span>🚚</span>
                                            <span>Delivery: <?php echo ucfirst($order['delivery_method']); ?></span>
                                        </div>
                                    </div>

                                    <div class="order-price">₱<?php echo number_format($order['order_amount'], 2); ?></div>

                                    <div class="order-actions">
                                        <a href="listing-details.php?id=<?php echo $order['listing_id']; ?>" class="btn btn-primary">
                                            View Item
                                        </a>
                                        <?php if ($view_mode === 'seller'): ?>
                                            <button class="btn btn-secondary" onclick="viewDeliveryAddress(<?php echo $order['order_id']; ?>, '<?php echo htmlspecialchars(addslashes($order['delivery_address'])); ?>')">
                                                View Address
                                            </button>
                                            <button class="btn btn-primary" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, '<?php echo $order['order_status']; ?>')">
                                                Update Status
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delivery Address Modal -->
    <div id="deliveryModal" class="delivery-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">📍 Delivery Address</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div id="addressContent" class="address-content"></div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="delivery-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">📦 Update Order Status</h2>
                <button class="close-modal" onclick="closeStatusModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="order_id" id="statusOrderId">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 10px;">Select New Status:</label>
                    <select name="new_status" id="newStatus" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px;">
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="update_status" class="btn btn-primary" style="flex: 1;">
                        Update Status
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewDeliveryAddress(orderId, address) {
            document.getElementById('addressContent').textContent = address;
            document.getElementById('deliveryModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('deliveryModal').style.display = 'none';
        }

        function updateOrderStatus(orderId, currentStatus) {
            document.getElementById('statusOrderId').value = orderId;
            document.getElementById('newStatus').value = currentStatus;
            document.getElementById('statusModal').style.display = 'flex';
        }

        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('deliveryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatusModal();
            }
        });
    </script>
</body>
</html>
