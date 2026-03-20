<?php
session_start();

// Block admin access to user pages
require_once __DIR__ . '/../includes/block_admin_access.php';

include '../config.php';
include '../database/supabase.php';
include '../database/notifications_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['listing_id'])) {
    header("Location: homepage.php");
    exit;
}

$listing_id = (int)$_GET['listing_id'];
$user_id = $_SESSION['user_id'];

// Get listing details
$listing = $supabase->select('listings', '*', ['id' => $listing_id], true);

if (!$listing) {
    die("Listing not found");
}

// Prevent buyer from contacting themselves
if ($listing['seller_id'] == $user_id) {
    header("Location: listing-details.php?id=$listing_id");
    exit;
}

// Get seller info
$seller = $supabase->select('accounts', 'username,first_name,last_name', ['account_id' => $listing['seller_id']], true);

// Get buyer info
$buyer = $supabase->select('accounts', 'username,first_name,last_name', ['account_id' => $user_id], true);

// Process interest submission
if (isset($_POST['send_interest'])) {
    $message_text = trim($_POST['message']);
    if (empty($message_text)) {
        $message_text = "Hi! I'm interested in your listing: " . $listing['title'];
    }

    // Check if conversation already exists (check both user1/user2 directions)
    $existing = $supabase->customQuery(
        'conversations',
        'conversation_id',
        'listing_id=eq.' . $listing_id . '&or=(and(user1_id.eq.' . $user_id . ',user2_id.eq.' . $listing['seller_id'] . '),and(user1_id.eq.' . $listing['seller_id'] . ',user2_id.eq.' . $user_id . '))'
    );

    if (!empty($existing)) {
        $conversation_id = $existing[0]['conversation_id'];
    } else {
        $conv_result = $supabase->insert('conversations', [
            'user1_id'   => $user_id,
            'user2_id'   => $listing['seller_id'],
            'listing_id' => $listing_id
        ]);
        $conversation_id = !empty($conv_result) ? $conv_result[0]['conversation_id'] : null;
    }

    if ($conversation_id) {
        $supabase->insert('messages', [
            'conversation_id' => $conversation_id,
            'sender_id'       => $user_id,
            'message_text'    => $message_text
        ]);

        // Notify seller
        $notificationHelper = new NotificationsHelper();
        $notificationHelper->createNotification(
            $listing['seller_id'],
            'new_message',
            'New Inquiry on Your Listing',
            $buyer['username'] . ' is interested in "' . $listing['title'] . '"',
            'inbox.php?conversation_id=' . $conversation_id
        );

        header("Location: inbox.php?conversation_id=" . $conversation_id);
        exit;
    } else {
        $error = "Could not send message. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Seller - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        .contact-container {
            max-width: 700px;
            margin: 40px auto;
            padding: 30px;
        }

        .contact-card {
            background: white;
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 25px;
            padding-bottom: 12px;
            border-bottom: 2px solid #945a9b;
            color: #333;
        }

        .item-preview {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .item-image {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .item-details .item-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .item-details .item-price {
            color: #945a9b;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 4px;
        }

        .item-details .item-seller {
            font-size: 13px;
            color: #666;
        }

        .info-box {
            background: #f0f8ff;
            border-left: 4px solid #945a9b;
            padding: 15px 18px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #444;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
            box-sizing: border-box;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #945a9b;
        }

        .send-btn {
            width: 100%;
            padding: 14px;
            background: #945a9b;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .send-btn:hover {
            background: #6a406e;
            transform: translateY(-2px);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #945a9b;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover { text-decoration: underline; }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>

    <div class="main-wrapper">
        <div class="contact-container">
            <a href="listing-details.php?id=<?php echo $listing_id; ?>" class="back-link">← Back to Listing</a>

            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="contact-card">
                <h2 class="section-title">Contact Seller</h2>

                <!-- Item Preview -->
                <?php
                $image = $supabase->select('listing_images', 'image_path', ['listing_id' => $listing_id]);
                $image_path = !empty($image) ? getImageUrl($image[0]['image_path']) : BASE_URL . '/assets/no-image.png';
                ?>
                <div class="item-preview">
                    <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Item" class="item-image">
                    <div class="item-details">
                        <div class="item-title"><?php echo htmlspecialchars($listing['title']); ?></div>
                        <div class="item-price">₱<?php echo number_format($listing['price'], 2); ?></div>
                        <div class="item-seller">Seller: <?php echo htmlspecialchars($seller['username']); ?></div>
                    </div>
                </div>

                <div class="info-box">
                    💡 <strong>How it works:</strong> MineTeh connects buyers and sellers directly.
                    Send a message to the seller to ask questions, arrange a meetup, or negotiate the price.
                    Payment and delivery are handled between you and the seller.
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Your Message to the Seller</label>
                        <textarea name="message" placeholder="Hi! I'm interested in your listing. Is it still available?"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                    <button type="submit" name="send_interest" class="send-btn">
                        💬 Send Message to Seller
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
