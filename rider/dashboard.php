<?php
session_start();
date_default_timezone_set('Asia/Manila');

require_once '../database/supabase.php';

// Check if user is logged in and is a rider
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get rider information
$rider = $supabase->select('riders', '*', ['account_id' => $_SESSION['user_id']], true);

if (!$rider) {
    die('Access denied. You are not registered as a rider.');
}

$rider_id = $rider['rider_id'];

// Get rider statistics
$total_deliveries = $supabase->count('deliveries', ['rider_id' => $rider_id]);
$pending_deliveries = $supabase->count('deliveries', ['rider_id' => $rider_id, 'delivery_status' => 'assigned']);
$completed_today = $supabase->customQuery('deliveries', 'delivery_id', 
    "rider_id=eq.$rider_id&delivery_status=eq.delivered&delivered_at=gte." . date('Y-m-d') . "T00:00:00");
$completed_today_count = $completed_today ? count($completed_today) : 0;

// Get active deliveries
$active_deliveries = $supabase->customQuery('deliveries', '*', 
    "rider_id=eq.$rider_id&delivery_status=in.(assigned,picked_up,in_transit)&order=created_at.desc");

// Get recent completed deliveries
$recent_completed = $supabase->customQuery('deliveries', '*', 
    "rider_id=eq.$rider_id&delivery_status=eq.delivered&order=delivered_at.desc&limit=10");

// Calculate today's earnings
$today_earnings = $supabase->customQuery('rider_earnings', 'amount', 
    "rider_id=eq.$rider_id&created_at=gte." . date('Y-m-d') . "T00:00:00");
$today_total = 0;
if ($today_earnings) {
    foreach ($today_earnings as $earning) {
        $today_total += floatval($earning['amount']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Dashboard - MineTeh</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-left h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 5px;
        }

        .header-left p {
            color: #666;
            font-size: 14px;
        }

        .header-right {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .status-badge {
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 14px;
        }

        .status-active {
            background: #d1f2eb;
            color: #0f5132;
        }

        .logout-btn {
            padding: 10px 25px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .deliveries-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 22px;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .delivery-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }

        .delivery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .delivery-id {
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }

        .delivery-status {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-assigned {
            background: #fff3cd;
            color: #856404;
        }

        .status-picked_up {
            background: #cfe2ff;
            color: #084298;
        }

        .status-in_transit {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-delivered {
            background: #d1e7dd;
            color: #0f5132;
        }

        .delivery-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            font-size: 12px;
            color: #666;
            font-weight: 600;
        }

        .info-value {
            font-size: 14px;
            color: #333;
        }

        .delivery-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .delivery-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>🏍️ Rider Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($rider['full_name']); ?>!</p>
            </div>
            <div class="header-right">
                <span class="status-badge status-active">● Active</span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?php echo $pending_deliveries; ?></div>
                <div class="stat-label">Pending Deliveries</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $completed_today_count; ?></div>
                <div class="stat-label">Completed Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value">₱<?php echo number_format($today_total, 2); ?></div>
                <div class="stat-label">Today's Earnings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-value"><?php echo number_format($rider['rating'], 1); ?></div>
                <div class="stat-label">Rating</div>
            </div>
        </div>

        <!-- Active Deliveries -->
        <div class="deliveries-section">
            <h2 class="section-title">🚚 Active Deliveries</h2>
            <?php if (empty($active_deliveries)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <h3>No Active Deliveries</h3>
                    <p>You don't have any active deliveries at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($active_deliveries as $delivery): ?>
                    <div class="delivery-card">
                        <div class="delivery-header">
                            <span class="delivery-id">Delivery #<?php echo $delivery['delivery_id']; ?></span>
                            <span class="delivery-status status-<?php echo $delivery['delivery_status']; ?>">
                                <?php echo strtoupper(str_replace('_', ' ', $delivery['delivery_status'])); ?>
                            </span>
                        </div>
                        <div class="delivery-info">
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
                        </div>
                        <div class="delivery-actions">
                            <?php if ($delivery['delivery_status'] == 'assigned'): ?>
                                <button class="btn btn-primary" onclick="updateStatus(<?php echo $delivery['delivery_id']; ?>, 'picked_up')">
                                    📦 Mark as Picked Up
                                </button>
                            <?php elseif ($delivery['delivery_status'] == 'picked_up'): ?>
                                <button class="btn btn-primary" onclick="updateStatus(<?php echo $delivery['delivery_id']; ?>, 'in_transit')">
                                    🚚 Start Delivery
                                </button>
                            <?php elseif ($delivery['delivery_status'] == 'in_transit'): ?>
                                <button class="btn btn-success" onclick="showProofOfDelivery(<?php echo $delivery['delivery_id']; ?>)">
                                    ✅ Complete Delivery
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-info" onclick="viewDetails(<?php echo $delivery['delivery_id']; ?>)">
                                📋 View Details
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Recent Completed -->
        <div class="deliveries-section">
            <h2 class="section-title">✅ Recent Completed Deliveries</h2>
            <?php if (empty($recent_completed)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <p>No completed deliveries yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_completed as $delivery): ?>
                    <div class="delivery-card">
                        <div class="delivery-header">
                            <span class="delivery-id">Delivery #<?php echo $delivery['delivery_id']; ?></span>
                            <span class="delivery-status status-delivered">DELIVERED</span>
                        </div>
                        <div class="delivery-info">
                            <div class="info-item">
                                <span class="info-label">🏠 Delivery Address</span>
                                <span class="info-value"><?php echo htmlspecialchars($delivery['delivery_address']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">⏰ Delivered At</span>
                                <span class="info-value"><?php echo date('M d, Y h:i A', strtotime($delivery['delivered_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">💰 Earnings</span>
                                <span class="info-value">₱<?php echo number_format($delivery['delivery_fee'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateStatus(deliveryId, newStatus) {
            if (!confirm('Are you sure you want to update the delivery status?')) {
                return;
            }

            fetch('../actions/rider-update-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    delivery_id: deliveryId,
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Status updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update status'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update status');
            });
        }

        function showProofOfDelivery(deliveryId) {
            window.location.href = 'proof-of-delivery.php?id=' + deliveryId;
        }

        function viewDetails(deliveryId) {
            window.location.href = 'delivery-details.php?id=' + deliveryId;
        }
    </script>
</body>
</html>
