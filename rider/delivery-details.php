<?php
session_start();
date_default_timezone_set('Asia/Manila');

require_once '../database/supabase.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$delivery_id = (int)$_GET['id'];

// Get rider info
$rider = $supabase->select('riders', '*', ['account_id' => $_SESSION['user_id']], true);
if (!$rider) {
    die('Access denied');
}

// Get delivery details
$delivery = $supabase->select('deliveries', '*', ['delivery_id' => $delivery_id], true);
if (!$delivery || $delivery['rider_id'] != $rider['rider_id']) {
    die('Delivery not found or access denied');
}

// Get order details
$order = $supabase->select('orders', '*', ['order_id' => $delivery['order_id']], true);

// Get tracking history
$tracking = $supabase->customQuery('delivery_tracking', '*', 
    "delivery_id=eq.$delivery_id&order=created_at.desc");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Details - MineTeh</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 8px;
        }

        .back-link:hover {
            background: rgba(255,255,255,0.3);
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-title {
            font-size: 22px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: bold;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-assigned { background: #cfe2ff; color: #084298; }
        .status-picked_up { background: #d1ecf1; color: #0c5460; }
        .status-in_transit { background: #d1e7dd; color: #0f5132; }
        .status-delivered { background: #d1e7dd; color: #0f5132; }
        .status-failed { background: #f8d7da; color: #842029; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            font-size: 13px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        .timeline {
            position: relative;
            padding-left: 40px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 30px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -32px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #667eea;
        }

        .timeline-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .timeline-status {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .timeline-time {
            font-size: 13px;
            color: #666;
        }

        .timeline-notes {
            font-size: 14px;
            color: #555;
            margin-top: 8px;
        }

        .proof-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .proof-item img {
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .proof-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: block;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .proof-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <!-- Delivery Info -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">📦 Delivery #<?php echo $delivery_id; ?></h2>
                <span class="status-badge status-<?php echo $delivery['delivery_status']; ?>">
                    <?php echo strtoupper(str_replace('_', ' ', $delivery['delivery_status'])); ?>
                </span>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">📍 Pickup Address</span>
                    <span class="info-value"><?php echo htmlspecialchars($delivery['pickup_address']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">🏠 Delivery Address</span>
                    <span class="info-value"><?php echo htmlspecialchars($delivery['delivery_address']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">👤 Recipient</span>
                    <span class="info-value"><?php echo htmlspecialchars($delivery['recipient_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">📞 Contact</span>
                    <span class="info-value"><?php echo htmlspecialchars($delivery['recipient_phone'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">💰 Delivery Fee</span>
                    <span class="info-value">₱<?php echo number_format($delivery['delivery_fee'], 2); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">📏 Distance</span>
                    <span class="info-value"><?php echo $delivery['distance_km'] ? $delivery['distance_km'] . ' km' : 'N/A'; ?></span>
                </div>
            </div>

            <?php if ($delivery['delivery_notes']): ?>
            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px;">
                <strong>📝 Notes:</strong> <?php echo htmlspecialchars($delivery['delivery_notes']); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Proof of Delivery -->
        <?php if ($delivery['delivery_status'] == 'delivered'): ?>
        <div class="card">
            <h2 class="card-title">📸 Proof of Delivery</h2>
            <div class="proof-section">
                <?php if ($delivery['proof_of_delivery_photo']): ?>
                <div class="proof-item">
                    <span class="proof-label">Delivery Photo</span>
                    <img src="../<?php echo htmlspecialchars($delivery['proof_of_delivery_photo']); ?>" alt="Delivery Photo">
                </div>
                <?php endif; ?>

                <?php if ($delivery['recipient_signature']): ?>
                <div class="proof-item">
                    <span class="proof-label">Recipient Signature</span>
                    <img src="../<?php echo htmlspecialchars($delivery['recipient_signature']); ?>" alt="Signature">
                </div>
                <?php endif; ?>
            </div>

            <?php if ($delivery['delivered_at']): ?>
            <div style="margin-top: 20px; padding: 15px; background: #d1e7dd; border-radius: 8px; color: #0f5132;">
                <strong>✅ Delivered:</strong> <?php echo date('F j, Y g:i A', strtotime($delivery['delivered_at'])); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Tracking History -->
        <div class="card">
            <h2 class="card-title">📍 Tracking History</h2>
            <div class="timeline">
                <?php if ($tracking && !empty($tracking)): ?>
                    <?php foreach ($tracking as $track): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-status">
                                <?php echo strtoupper(str_replace('_', ' ', $track['status'])); ?>
                            </div>
                            <div class="timeline-time">
                                <?php echo date('F j, Y g:i A', strtotime($track['created_at'])); ?>
                            </div>
                            <?php if ($track['notes']): ?>
                            <div class="timeline-notes">
                                <?php echo htmlspecialchars($track['notes']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #999;">No tracking history available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
