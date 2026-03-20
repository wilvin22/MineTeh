<?php
session_start();
require_once __DIR__ . '/../includes/block_admin_access.php';
date_default_timezone_set('Asia/Manila');
include '../config.php';
include '../database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle inline quick rating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_rate_notif'])) {
    $rating     = (int)$_POST['rating'];
    $seller_id  = (int)$_POST['seller_id'];
    $listing_id = (int)$_POST['listing_id'];
    $notif_id   = (int)$_POST['notif_id'];
    if ($rating >= 1 && $rating <= 5 && $seller_id && $listing_id) {
        $existing = $supabase->customQuery('reviews', 'review_id',
            'seller_id=eq.' . $seller_id . '&reviewer_id=eq.' . $user_id . '&listing_id=eq.' . $listing_id);
        if (empty($existing)) {
            $supabase->insert('reviews', [
                'seller_id'   => $seller_id,
                'reviewer_id' => $user_id,
                'listing_id'  => $listing_id,
                'rating'      => $rating,
                'comment'     => ''
            ]);
        }
    }
    $supabase->update('notifications', ['is_read' => true], ['id' => $notif_id, 'user_id' => $user_id]);
    header('Location: notifications.php');
    exit();
}

// Mark single as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $supabase->update('notifications', ['is_read' => true],
        ['id' => (int)$_GET['mark_read'], 'user_id' => $user_id]);
    header('Location: notifications.php');
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    // update() only supports AND equality filters — use customQuery workaround via PATCH
    $supabase->update('notifications', ['is_read' => true],
        ['user_id' => $user_id]);
    header('Location: notifications.php');
    exit();
}

// Fetch notifications (exclude new_message type)
$notifications = $supabase->customQuery('notifications', '*',
    'user_id=eq.' . $user_id . '&type=neq.new_message&order=created_at.desc');

$unread_count = 0;
if ($notifications) {
    foreach ($notifications as $n) {
        if (!$n['is_read']) $unread_count++;
    }
}

// Split into New (< 24h) and Earlier
$new_notifs      = [];
$earlier_notifs  = [];
$now = time();
foreach ($notifications ?: [] as $n) {
    $diff = $now - strtotime($n['created_at']);
    if ($diff < 86400) $new_notifs[]     = $n;
    else               $earlier_notifs[] = $n;
}

function time_ago($ts) {
    // Parse Supabase UTC timestamp, convert to Asia/Manila for display
    $dt  = new DateTime($ts, new DateTimeZone('UTC'));
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $diff = $now->getTimestamp() - $dt->getTimestamp();

    $dt->setTimezone(new DateTimeZone('Asia/Manila'));
    $full = $dt->format('M d, Y g:i A');

    if ($diff < 60)    return 'Just now · ' . $full;
    if ($diff < 3600)  return floor($diff / 60) . 'm ago · ' . $full;
    if ($diff < 86400) return floor($diff / 3600) . 'h ago · ' . $full;
    return $full;
}

function notif_icon($type) {
    $map = [
        'bid_received'   => '🔨',
        'outbid'         => '⚠️',
        'listing_sold'   => '✅',
        'review_request' => '⭐',
        'order_update'   => '📦',
    ];
    return $map[$type] ?? '🔔';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        body { background: #f5f7fa; margin: 0; font-family: Arial, sans-serif; display: flex; }

        .notif-main {
            margin-left: 280px;
            width: calc(100% - 280px);
            min-height: 100vh;
            padding: 30px;
            box-sizing: border-box;
        }

        .notif-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px;
        }
        .notif-title { font-size: 24px; font-weight: bold; color: #333; }
        .mark-all-btn {
            font-size: 13px; color: #945a9b; text-decoration: none; font-weight: 600;
        }
        .mark-all-btn:hover { text-decoration: underline; }

        .notif-group-label {
            font-size: 13px; font-weight: 700; color: #999;
            text-transform: uppercase; letter-spacing: 0.6px;
            margin: 20px 0 8px;
        }

        .notif-list { display: flex; flex-direction: column; gap: 2px; }

        .notif-item {
            display: flex; align-items: flex-start; gap: 14px;
            padding: 12px 14px; border-radius: 10px;
            background: white; transition: background 0.15s;
            position: relative;
        }
        .notif-item:hover { background: #f0e8f4; }
        .notif-item.unread { background: #f5eef8; }
        .notif-item.unread::before {
            content: '';
            position: absolute; left: 0; top: 0; bottom: 0;
            width: 3px; background: #945a9b; border-radius: 3px 0 0 3px;
        }

        .notif-icon-wrap {
            width: 46px; height: 46px; border-radius: 50%;
            background: #f0e6f2;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }

        .notif-body { flex: 1; min-width: 0; }
        .notif-text { font-size: 14px; color: #333; line-height: 1.4; margin-bottom: 4px; }
        .notif-text strong { color: #111; }
        .notif-meta { display: flex; align-items: center; gap: 6px; font-size: 12px; }
        .notif-dot { width: 8px; height: 8px; border-radius: 50%; background: #945a9b; flex-shrink: 0; }
        .notif-dot.read { background: #ccc; }
        .notif-time { color: #945a9b; font-weight: 600; }
        .notif-time.read { color: #999; }

        /* Rate form inline */
        .notif-rate-row {
            display: flex; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap;
        }
        .star-dropdown {
            padding: 4px 8px; border: 2px solid #945a9b; border-radius: 8px;
            font-size: 13px; cursor: pointer; background: white; font-family: inherit;
        }
        .star-dropdown:focus { outline: none; }
        .rate-submit-btn {
            padding: 4px 12px; background: #945a9b; color: white;
            border: none; border-radius: 8px; font-size: 13px; font-weight: bold;
            cursor: pointer; transition: background 0.2s;
        }
        .rate-submit-btn:hover { background: #6a406e; }
        .rated-chip {
            font-size: 12px; color: #28a745; font-weight: 600;
            padding: 3px 10px; background: #d1e7dd; border-radius: 8px;
        }

        /* Actions */
        .notif-actions { display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; align-self: center; }
        .notif-action-btn {
            padding: 5px 10px; border-radius: 6px; font-size: 12px;
            text-decoration: none; border: none; cursor: pointer;
            font-family: inherit; transition: background 0.15s;
        }
        .btn-view   { background: #945a9b; color: white; }
        .btn-view:hover { background: #6a406e; }

        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-icon  { font-size: 52px; margin-bottom: 14px; }

        @media (max-width: 768px) {
            .notif-main { margin-left: 220px; width: calc(100% - 220px); padding: 20px; }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>

    <div class="notif-main">
        <div class="notif-header">
            <div class="notif-title">
                🔔 Notifications
                <?php if ($unread_count > 0): ?>
                    <span style="background:#945a9b;color:white;padding:2px 10px;border-radius:12px;font-size:14px;margin-left:8px;"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </div>
            <?php if ($unread_count > 0): ?>
                <a href="?mark_all_read=1" class="mark-all-btn">Mark all as read</a>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <div class="empty-icon">🔕</div>
                <p>No notifications yet.</p>
            </div>
        <?php else: ?>

            <?php foreach ([['New', $new_notifs], ['Earlier', $earlier_notifs]] as [$label, $group]):
                if (empty($group)) continue; ?>
                <div class="notif-group-label"><?php echo $label; ?></div>
                <div class="notif-list">
                <?php foreach ($group as $notif):
                    // Parse seller/listing from link for review_request
                    $r_seller_id = $r_listing_id = 0;
                    $already = false;
                    if ($notif['type'] === 'review_request' && !empty($notif['link'])) {
                        parse_str(parse_url($notif['link'], PHP_URL_QUERY), $lp);
                        $r_seller_id  = (int)($lp['id'] ?? 0);
                        $r_listing_id = (int)($lp['listing_id'] ?? 0);
                        if ($r_seller_id && $r_listing_id) {
                            $rev = $supabase->customQuery('reviews', 'review_id',
                                'seller_id=eq.' . $r_seller_id . '&reviewer_id=eq.' . $user_id . '&listing_id=eq.' . $r_listing_id);
                            $already = !empty($rev);
                        }
                    }
                ?>
                    <div class="notif-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                        <div class="notif-icon-wrap"><?php echo notif_icon($notif['type']); ?></div>
                        <div class="notif-body">
                            <div class="notif-text">
                                <strong><?php echo htmlspecialchars($notif['title']); ?></strong><br>
                                <?php echo htmlspecialchars($notif['message']); ?>
                            </div>
                            <div class="notif-meta">
                                <span class="notif-dot <?php echo $notif['is_read'] ? 'read' : ''; ?>"></span>
                                <span class="notif-time <?php echo $notif['is_read'] ? 'read' : ''; ?>"
                                      data-ts="<?php echo htmlspecialchars($notif['created_at']); ?>">
                                    <?php echo time_ago($notif['created_at']); ?>
                                </span>
                            </div>
                            <?php if ($notif['type'] === 'review_request'): ?>
                                <div class="notif-rate-row">
                                    <?php if ($already): ?>
                                        <span class="rated-chip">✅ Thank you for your rating!</span>
                                    <?php else: ?>
                                        <form method="POST" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;" onsubmit="return confirmRate(this)">
                                            <input type="hidden" name="quick_rate_notif" value="1">
                                            <input type="hidden" name="seller_id"  value="<?php echo $r_seller_id; ?>">
                                            <input type="hidden" name="listing_id" value="<?php echo $r_listing_id; ?>">
                                            <input type="hidden" name="notif_id"   value="<?php echo $notif['id']; ?>">
                                            <label style="font-size:13px;font-weight:600;color:#555;">Rate Seller:</label>
                                            <select name="rating" class="star-dropdown" required>
                                                <option value="" disabled selected>⭐ Stars</option>
                                                <option value="5">⭐⭐⭐⭐⭐ 5</option>
                                                <option value="4">⭐⭐⭐⭐ 4</option>
                                                <option value="3">⭐⭐⭐ 3</option>
                                                <option value="2">⭐⭐ 2</option>
                                                <option value="1">⭐ 1</option>
                                            </select>
                                            <button type="submit" class="rate-submit-btn">Submit</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="notif-actions">
                            <?php if ($notif['type'] !== 'review_request' && $notif['link']): ?>
                                <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="notif-action-btn btn-view">View</a>
                            <?php endif; ?>
                            <?php if (!$notif['is_read']): ?>
                                <a href="?mark_read=<?php echo $notif['id']; ?>" class="notif-action-btn btn-view">Mark Read</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

    <script>
    function confirmRate(form) {
        const sel = form.querySelector('select[name="rating"]');
        if (!sel.value) { alert('Please select a star rating.'); return false; }
        return confirm('Submit ' + sel.value + '-star rating?');
    }

    function formatRelative(utcString) {
        // Normalize: replace space with T and append Z to force UTC parsing
        const normalized = utcString.replace(' ', 'T').replace(/(\+\d{2}:\d{2}|Z)?$/, '') + 'Z';
        const date = new Date(normalized);
        const diffMs = Date.now() - date.getTime();
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);

        // Full date/time in PH timezone
        const full = date.toLocaleString('en-PH', {
            timeZone: 'Asia/Manila',
            month: 'short', day: 'numeric', year: 'numeric',
            hour: 'numeric', minute: '2-digit', hour12: true
        });

        if (diffSec < 60)  return 'Just now · ' + full;
        if (diffMin < 60)  return diffMin + (diffMin === 1 ? ' min ago' : ' mins ago') + ' · ' + full;
        return full;
    }

    function updateTimes() {
        document.querySelectorAll('.notif-time[data-ts]').forEach(el => {
            el.textContent = formatRelative(el.dataset.ts);
        });
    }

    updateTimes();
    setInterval(updateTimes, 1000); // every second — keeps "X mins ago" accurate in real-time


    </script>
</body>
</html>
