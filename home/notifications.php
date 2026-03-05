<?php
session_start();
date_default_timezone_set('Asia/Manila');

// Check both possible session variable names
if (!isset($_SESSION['user_id']) && !isset($_SESSION['account_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../database/supabase.php';
$supabase = new SupabaseClient();

// Use whichever session variable is set
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['account_id'];

// Mark notification as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = intval($_GET['mark_read']);
    $supabase->update('notifications', 
        ['is_read' => true],
        'id=eq.' . $notification_id . '&user_id=eq.' . $user_id
    );
    header('Location: notifications.php');
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $supabase->update('notifications', 
        ['is_read' => true],
        'user_id=eq.' . $user_id . '&is_read=eq.false'
    );
    header('Location: notifications.php');
    exit();
}

// Delete notification
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notification_id = intval($_GET['delete']);
    $supabase->delete('notifications', 'id=eq.' . $notification_id . '&user_id=eq.' . $user_id);
    header('Location: notifications.php');
    exit();
}

// Get all notifications EXCEPT message notifications
$notifications = $supabase->customQuery('notifications', '*', 'user_id=eq.' . $user_id . '&type=neq.new_message&order=created_at.desc');

// Get unread count
$unread_count = 0;
if ($notifications) {
    foreach ($notifications as $notif) {
        if (!$notif['is_read']) {
            $unread_count++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - MineTeh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #764ba2;
            font-size: 28px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(118, 75, 162, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .notifications-list {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .notification-item {
            padding: 20px 30px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            gap: 20px;
            align-items: start;
            transition: background 0.3s;
            position: relative;
        }

        .notification-item:hover {
            background: #f9f9f9;
        }

        .notification-item.unread {
            background: #f0f4ff;
        }

        .notification-item.unread::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #764ba2;
        }

        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .icon-bid { background: #e3f2fd; color: #2196F3; }
        .icon-outbid { background: #fff3e0; color: #ff9800; }
        .icon-sold { background: #e8f5e9; color: #4caf50; }
        .icon-message { background: #f3e5f5; color: #9c27b0; }
        .icon-order { background: #e0f2f1; color: #009688; }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .notification-message {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .notification-time {
            font-size: 12px;
            color: #999;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .action-btn-view {
            background: #764ba2;
            color: white;
        }

        .action-btn-view:hover {
            background: #5a3780;
        }

        .action-btn-delete {
            background: #f44336;
            color: white;
        }

        .action-btn-delete:hover {
            background: #d32f2f;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ddd;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .back-link:hover {
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="homepage.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <div class="header">
            <h1>
                <i class="fas fa-bell"></i> Notifications
                <?php if ($unread_count > 0): ?>
                    <span style="background: #f44336; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px; margin-left: 10px;">
                        <?php echo $unread_count; ?>
                    </span>
                <?php endif; ?>
            </h1>
            <div class="header-actions">
                <?php if ($unread_count > 0): ?>
                    <a href="?mark_all_read=1" class="btn btn-secondary">
                        <i class="fas fa-check-double"></i> Mark All Read
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="notifications-list">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No notifications yet</h3>
                    <p>You'll see system notifications here for bids, orders, and favorites</p>
                    <p style="margin-top: 10px; color: #999; font-size: 14px;">💬 Check Messages for your conversations</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <?php
                    $icon_class = 'icon-message';
                    $icon = 'fa-bell';
                    
                    switch ($notif['type']) {
                        case 'bid_received':
                            $icon_class = 'icon-bid';
                            $icon = 'fa-gavel';
                            break;
                        case 'outbid':
                            $icon_class = 'icon-outbid';
                            $icon = 'fa-exclamation-triangle';
                            break;
                        case 'listing_sold':
                            $icon_class = 'icon-sold';
                            $icon = 'fa-check-circle';
                            break;
                        case 'order_update':
                            $icon_class = 'icon-order';
                            $icon = 'fa-shopping-bag';
                            break;
                        default:
                            $icon_class = 'icon-message';
                            $icon = 'fa-bell';
                            break;
                    }
                    
                    $time_ago = '';
                    $created = strtotime($notif['created_at']);
                    $now = time();
                    $diff = $now - $created;
                    
                    if ($diff < 60) {
                        $time_ago = 'Just now';
                    } elseif ($diff < 3600) {
                        $time_ago = floor($diff / 60) . ' minutes ago';
                    } elseif ($diff < 86400) {
                        $time_ago = floor($diff / 3600) . ' hours ago';
                    } else {
                        $time_ago = floor($diff / 86400) . ' days ago';
                    }
                    ?>
                    <div class="notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                        <div class="notification-icon <?php echo $icon_class; ?>">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                            <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                            <div class="notification-time"><?php echo $time_ago; ?></div>
                        </div>
                        <div class="notification-actions">
                            <?php if ($notif['link']): ?>
                                <a href="<?php echo htmlspecialchars($notif['link']); ?>?mark_read=<?php echo $notif['id']; ?>" class="action-btn action-btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            <?php elseif (!$notif['is_read']): ?>
                                <a href="?mark_read=<?php echo $notif['id']; ?>" class="action-btn action-btn-view">
                                    <i class="fas fa-check"></i> Mark Read
                                </a>
                            <?php endif; ?>
                            <a href="?delete=<?php echo $notif['id']; ?>" class="action-btn action-btn-delete" onclick="return confirm('Delete this notification?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
