<?php
session_start();
include '../database/supabase.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: homepage.php");
    exit;
}

$order_id = (int)$_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Get order details
$order = $supabase->select('orders', '*', ['order_id' => $order_id], true);

if (!$order || $order['buyer_id'] != $user_id) {
    die("Order not found");
}

// Get listing details
$listing = $supabase->select('listings', '*', ['id' => $order['listing_id']], true);

// Get seller info
$seller = $supabase->select('accounts', 'username,first_name,last_name', ['account_id' => $order['seller_id']], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        .confirmation-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 30px;
            text-align: center;
        }

        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .confirmation-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .order-number {
            font-size: 24px;
            font-weight: bold;
            color: #945a9b;
            margin: 20px 0;
        }

        .order-details {
            text-align: left;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #945a9b;
            color: white;
        }

        .btn-primary:hover {
            background: #6a406e;
        }

        .btn-secondary {
            background: white;
            color: #945a9b;
            border: 2px solid #945a9b;
        }

        .btn-secondary:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="confirmation-container">
            <div class="confirmation-card">
                <div class="success-icon">✅</div>
                <h1>Order Placed Successfully!</h1>
                <p>Thank you for your purchase. Your order has been received and is being processed.</p>
                
                <div class="order-number">
                    Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?>
                </div>

                <div class="order-details">
                    <div class="detail-row">
                        <strong>Item:</strong>
                        <span><?php echo htmlspecialchars($listing['title']); ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Seller:</strong>
                        <span><?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Amount:</strong>
                        <span>₱<?php echo number_format($order['order_amount'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Payment Method:</strong>
                        <span><?php echo strtoupper($order['payment_method']); ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Delivery Method:</strong>
                        <span><?php echo ucfirst($order['delivery_method']); ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Status:</strong>
                        <span style="color: #945a9b; font-weight: bold;">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="homepage.php" class="btn btn-secondary">Continue Shopping</a>
                    <a href="messages.php?seller_id=<?php echo $order['seller_id']; ?>&listing_id=<?php echo $order['listing_id']; ?>" class="btn btn-primary">
                        Message Seller
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
