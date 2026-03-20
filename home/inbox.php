<?php
session_start();

// Block admin access to user pages
require_once __DIR__ . '/../includes/block_admin_access.php';

include '../database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// AJAX HANDLERS - Must be first to exit before HTML rendering
// Fetch new messages via AJAX
if (isset($_GET['fetch_messages']) && isset($_GET['conversation_id'])) {
    $conversation_id = (int)$_GET['conversation_id'];
    $messages = $supabase->customQuery('messages', '*', 
        'conversation_id=eq.' . $conversation_id . '&order=sent_at.asc');
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'messages' => $messages, 'user_id' => $user_id]);
    exit;
}

// Send message via AJAX
if (isset($_POST['send_message']) && isset($_POST['conversation_id'])) {
    $conversation_id = (int)$_POST['conversation_id'];
    $message_text = trim($_POST['message_text']);
    
    if (!empty($message_text)) {
        $result = $supabase->insert('messages', [
            'conversation_id' => $conversation_id,
            'sender_id' => $user_id,
            'message_text' => $message_text
        ]);
        
        // Messages have their own unread system in the messages table
        // No need for separate notifications
        
        // Return JSON for AJAX requests
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $result]);
            exit;
        }
        
        // Fallback: redirect for non-AJAX
        header("Location: inbox.php?conversation_id=$conversation_id");
        exit;
    }
}

// Handle quick rate from inbox
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inbox_quick_rate'])) {
    $rating     = (int)$_POST['rating'];
    $seller_id  = (int)$_POST['seller_id'];
    $listing_id = (int)$_POST['listing_id'];
    $conv_id    = (int)$_POST['conversation_id'];
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
    header("Location: inbox.php?conversation_id=" . $conv_id . "&rated=1");
    exit;
}

// Start new conversation if seller_id is provided
if (isset($_GET['seller_id'])) {
    $seller_id  = (int)$_GET['seller_id'];
    $listing_id = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : null;

    // Build query to find existing conversation
    if ($listing_id) {
        $existing = $supabase->customQuery('conversations', 'conversation_id',
            'listing_id=eq.' . $listing_id . '&or=(and(user1_id.eq.' . $user_id . ',user2_id.eq.' . $seller_id . '),and(user1_id.eq.' . $seller_id . ',user2_id.eq.' . $user_id . '))');
    } else {
        $existing = $supabase->customQuery('conversations', 'conversation_id',
            'listing_id=is.null&or=(and(user1_id.eq.' . $user_id . ',user2_id.eq.' . $seller_id . '),and(user1_id.eq.' . $seller_id . ',user2_id.eq.' . $user_id . '))');
    }

    if (!empty($existing)) {
        header("Location: inbox.php?conversation_id=" . $existing[0]['conversation_id']);
        exit;
    } else {
        $conv_data = [
            'user1_id' => $user_id,
            'user2_id' => $seller_id,
        ];
        if ($listing_id) $conv_data['listing_id'] = $listing_id;

        $new_conversation = $supabase->insert('conversations', $conv_data);

        if ($new_conversation && is_array($new_conversation) && !empty($new_conversation)) {
            $conv_id = isset($new_conversation[0]['conversation_id']) ? $new_conversation[0]['conversation_id'] : $new_conversation['conversation_id'];
            header("Location: inbox.php?conversation_id=" . $conv_id);
            exit;
        }
    }
}

// Get all conversations for this user
$conversations = $supabase->customQuery('conversations', '*', 
    'or=(user1_id.eq.' . $user_id . ',user2_id.eq.' . $user_id . ')&order=updated_at.desc');

// Get selected conversation
$selected_conversation = null;
$messages = [];
$other_user = null;
$listing_info = null;
$listing_status = null;
$listing_seller_id = null;
$already_reviewed = false;

if (isset($_GET['conversation_id'])) {
    $conversation_id = (int)$_GET['conversation_id'];
    $selected_conversation = $supabase->select('conversations', '*', ['conversation_id' => $conversation_id], true);
    
    if ($selected_conversation) {
        // Get messages
        $messages = $supabase->customQuery('messages', '*', 
            'conversation_id=eq.' . $conversation_id . '&order=sent_at.asc');
        
        // Mark messages as read
        if (!empty($messages)) {
            foreach ($messages as $msg) {
                if ($msg['sender_id'] != $user_id && !$msg['is_read']) {
                    $supabase->update('messages', ['is_read' => true], ['message_id' => $msg['message_id']]);
                }
            }
        }
        
        // Get other user info
        $other_user_id = ($selected_conversation['user1_id'] == $user_id) ? 
            $selected_conversation['user2_id'] : $selected_conversation['user1_id'];
        $other_user = $supabase->select('accounts', 'username,first_name,last_name', 
            ['account_id' => $other_user_id], true);
        
        // Get listing info if exists
        if ($selected_conversation['listing_id']) {
            $listing_full = $supabase->select('listings', 'title,price,status,seller_id', 
                ['id' => $selected_conversation['listing_id']], true);
            if ($listing_full) {
                $listing_info      = $listing_full;
                $listing_status    = $listing_full['status'];
                $listing_seller_id = $listing_full['seller_id'];
                // Check if current user is buyer and listing is sold
                $is_buyer = ($listing_seller_id != $user_id);
                if ($is_buyer && $listing_status === 'sold') {
                    $conv_listing_id = $selected_conversation['listing_id'];
                    $rev = $supabase->customQuery('reviews', 'review_id',
                        'seller_id=eq.' . $listing_seller_id . '&reviewer_id=eq.' . $user_id . '&listing_id=eq.' . $conv_listing_id);
                    $already_reviewed = !empty($rev) || isset($_GET['rated']);
                }
            }
        }
    }
} elseif (!empty($conversations)) {
    header("Location: inbox.php?conversation_id=" . $conversations[0]['conversation_id']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        .messages-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            height: calc(100vh - 40px);
            margin: 20px;
            gap: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Conversations List */
        .conversations-list {
            border-right: 1px solid #ddd;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .conversations-header {
            padding: 20px;
            background: #945a9b;
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        .conversation-item {
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
            transition: background 0.2s ease;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .conversation-item:hover {
            background: #e9ecef;
        }

        .conversation-item.active {
            background: white;
            border-left: 4px solid #945a9b;
        }

        .conversation-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #945a9b;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }

        .conversation-info {
            flex: 1;
            min-width: 0;
        }

        .conversation-name {
            font-weight: bold;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .unread-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            background: #ff4444;
            color: white;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
        }

        .conversation-preview {
            font-size: 13px;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Chat Area */
        .chat-area {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            overflow: hidden;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            background: white;
        }

        .chat-user-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .chat-listing-info {
            font-size: 13px;
            color: #666;
        }

        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 0;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
        }

        .message.sent {
            flex-direction: row-reverse;
        }

        .message-bubble {
            max-width: 60%;
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
        }

        .message.received .message-bubble {
            background: white;
            border: 1px solid #ddd;
        }

        .message.sent .message-bubble {
            background: #945a9b;
            color: white;
        }

        .message-time {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
        }

        .message.sent .message-time {
            color: rgba(255,255,255,0.8);
        }

        .message-input-area {
            padding: 20px;
            border-top: 1px solid #ddd;
            background: white;
        }

        .message-form {
            display: flex;
            gap: 10px;
        }

        .message-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 24px;
            font-size: 14px;
            outline: none;
        }

        .message-input:focus {
            border-color: #945a9b;
        }

        .send-btn {
            padding: 12px 24px;
            background: #945a9b;
            color: white;
            border: none;
            border-radius: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .send-btn:hover {
            background: #6a406e;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        @media (max-width: 968px) {
            .messages-container {
                grid-template-columns: 1fr;
                margin: 10px;
                height: calc(100vh - 20px);
            }
            
            .conversations-list {
                display: none;
            }
            
            .conversations-list.show-mobile {
                display: block;
            }
            
            .chat-area.hide-mobile {
                display: none;
            }
            
            .message-bubble {
                max-width: 75%;
            }
            
            .chat-header {
                padding: 15px;
            }
            
            .messages-area {
                padding: 15px;
            }
            
            .message-input-area {
                padding: 15px;
            }
        }
        
        @media (max-width: 640px) {
            .messages-container {
                margin: 5px;
                height: calc(100vh - 10px);
                border-radius: 8px;
            }
            
            .message-bubble {
                max-width: 85%;
                padding: 10px 14px;
                font-size: 14px;
            }
            
            .chat-user-name {
                font-size: 16px;
            }
            
            .chat-listing-info {
                font-size: 12px;
            }
            
            .message-input {
                padding: 10px 14px;
                font-size: 14px;
            }
            
            .send-btn {
                padding: 10px 20px;
                font-size: 14px;
            }
            
            .conversation-item {
                padding: 12px 15px;
            }
            
            .conversation-avatar {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            
            .conversations-header {
                padding: 15px;
                font-size: 18px;
            }
        }
        
        /* Listing status chips in sidebar */
        .listing-status-chip {
            display: inline-block; padding: 1px 7px; border-radius: 8px;
            font-size: 10px; font-weight: bold; flex-shrink: 0;
        }
        .chip-active { background: #d1e7dd; color: #0f5132; }
        .chip-sold   { background: #f8d7da; color: #842029; }

        /* Buyer/Seller role badge */
        .role-badge {
            display: inline-block; padding: 2px 8px; border-radius: 10px;
            font-size: 11px; font-weight: 700; flex-shrink: 0;
        }
        .role-buyer  { background: #dbeafe; color: #1e40af; }
        .role-seller { background: #fef3c7; color: #92400e; }

        /* Rate seller banner */
        .rate-seller-banner {
            display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
            padding: 10px 14px; margin-bottom: 10px;
            background: #fff8e1; border: 1px solid #ffe082;
            border-radius: 10px; font-size: 13px;
        }
        .rate-banner-icon { font-size: 18px; flex-shrink: 0; }
        .rate-banner-text { color: #555; }
        .rate-banner-form { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .star-dropdown {
            padding: 5px 10px; border: 2px solid #945a9b; border-radius: 8px;
            font-size: 13px; cursor: pointer; background: white; font-family: inherit;
        }
        .star-dropdown:focus { outline: none; }
        .rate-submit-btn {
            padding: 5px 14px; background: #945a9b; color: white;
            border: none; border-radius: 8px; font-size: 13px; font-weight: bold;
            cursor: pointer; transition: background 0.2s;
        }
        .rate-submit-btn:hover { background: #6a406e; }
        .rated-chip {
            font-size: 12px; color: #28a745; font-weight: 600;
            padding: 4px 10px; background: #d1e7dd; border-radius: 8px;
        }

        /* Mobile navigation buttons */
        .mobile-nav-btn {
            display: none;
            padding: 8px 16px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        @media (max-width: 968px) {
            .mobile-nav-btn {
                display: inline-block;
            }
            
            .mobile-back-btn {
                margin-right: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <!-- Mobile Navigation -->
        <div style="padding: 10px; display: none;" class="mobile-nav-container">
            <button class="mobile-nav-btn mobile-back-btn" onclick="showConversations()">
                ← Back to Conversations
            </button>
        </div>
        
        <div class="messages-container" id="messagesContainer">
            <!-- Conversations List -->
            <div class="conversations-list">
                <div class="conversations-header">💬 Inbox</div>
                
                <?php if (empty($conversations)): ?>
                    <div style="padding: 20px; text-align: center; color: #666;">
                        No conversations yet
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): 
                        $other_id = ($conv['user1_id'] == $user_id) ? $conv['user2_id'] : $conv['user1_id'];
                        $other = $supabase->select('accounts', 'username,first_name', ['account_id' => $other_id], true);
                        $conv_listing = null;
                        $conv_listing_status = null;
                        $conv_other_role = null; // 'Buyer' or 'Seller' from current user's perspective
                        if ($conv['listing_id']) {
                            $conv_listing = $supabase->select('listings', 'title,status,seller_id', ['id' => $conv['listing_id']], true);
                            $conv_listing_status = $conv_listing['status'] ?? null;
                            if ($conv_listing) {
                                // If the other person owns the listing, they are the Seller (I am buying)
                                // If I own the listing, the other person is the Buyer
                                $conv_other_role = ($conv_listing['seller_id'] == $other_id) ? 'Seller' : 'Buyer';
                            }
                        }
                        $is_active = ($selected_conversation && $selected_conversation['conversation_id'] == $conv['conversation_id']);
                        
                        $unread_msgs = $supabase->customQuery('messages', 'message_id', 
                            'conversation_id=eq.' . $conv['conversation_id'] . '&sender_id=neq.' . $user_id . '&is_read=eq.false');
                        $unread_count = count($unread_msgs);
                    ?>
                        <a href="inbox.php?conversation_id=<?php echo $conv['conversation_id']; ?>" 
                           class="conversation-item <?php echo $is_active ? 'active' : ''; ?>" 
                           style="text-decoration: none; color: inherit; position: relative;">
                            <div class="conversation-avatar">
                                <?php echo strtoupper(substr($other['first_name'] ?? $other['username'] ?? '?', 0, 1)); ?>
                            </div>
                            <div class="conversation-info">
                                <div class="conversation-name">
                                    <?php echo htmlspecialchars($other['first_name'] ?? $other['username']); ?>
                                    <?php if ($conv_other_role): ?>
                                        <span class="role-badge <?php echo $conv_other_role === 'Seller' ? 'role-seller' : 'role-buyer'; ?>">
                                            <?php echo $conv_other_role === 'Seller' ? '🏷️ Seller' : '🛒 Buyer'; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($unread_count > 0): ?>
                                        <span class="unread-badge"><?php echo $unread_count; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-preview" style="display:flex; align-items:center; gap:6px;">
                                    <?php echo $conv_listing ? htmlspecialchars($conv_listing['title']) : 'Chat'; ?>
                                    <?php if ($conv_listing_status): ?>
                                        <span class="listing-status-chip <?php echo $conv_listing_status === 'sold' ? 'chip-sold' : 'chip-active'; ?>">
                                            <?php echo $conv_listing_status === 'sold' ? 'Sold' : ucfirst($conv_listing_status); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Chat Area -->
            <div class="chat-area">
                <?php if ($selected_conversation && $other_user): ?>
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                            <div>
                                <div class="chat-user-name">
                                    <?php echo htmlspecialchars($other_user['first_name'] . ' ' . $other_user['last_name']); ?>
                                    <?php if ($listing_seller_id): ?>
                                        <?php $other_role = ($listing_seller_id == $other_user_id) ? 'Seller' : 'Buyer'; ?>
                                        <span class="role-badge <?php echo $other_role === 'Seller' ? 'role-seller' : 'role-buyer'; ?>" style="font-size:12px;vertical-align:middle;">
                                            <?php echo $other_role === 'Seller' ? '🏷️ Seller' : '🛒 Buyer'; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($listing_info): ?>
                                    <div class="chat-listing-info">
                                        About: <?php echo htmlspecialchars($listing_info['title']); ?> 
                                        (₱<?php echo number_format($listing_info['price'], 2); ?>)
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($listing_info && $selected_conversation['listing_id']): ?>
                                <a href="listing-details.php?id=<?php echo $selected_conversation['listing_id']; ?>"
                                   target="_blank"
                                   style="flex-shrink:0; display:inline-flex; align-items:center; gap:6px;
                                          padding:7px 14px; background:#f8f4f9; color:#945a9b;
                                          border:1.5px solid #945a9b; border-radius:8px;
                                          font-size:13px; font-weight:600; text-decoration:none;
                                          transition:background 0.2s, color 0.2s;"
                                   onmouseover="this.style.background='#945a9b';this.style.color='white';"
                                   onmouseout="this.style.background='#f8f4f9';this.style.color='#945a9b';">
                                    🔍 See Item
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Messages Area -->
                    <div class="messages-area" id="messagesArea">
                        <?php if (empty($messages)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">💬</div>
                                <p>No messages yet. Start the conversation!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                                    <div class="message-bubble">
                                        <?php echo nl2br(htmlspecialchars($msg['message_text'])); ?>
                                        <div class="message-time">
                                            <?php echo date('M d, h:i A', strtotime($msg['sent_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Message Input -->
                    <div class="message-input-area">
                        <?php
                        $is_buyer_view = $listing_seller_id && $listing_seller_id != $user_id;
                        if ($listing_status === 'sold' && $is_buyer_view):
                        ?>
                            <?php if ($already_reviewed): ?>
                                <div class="rate-seller-banner" style="background:#d1e7dd; border-color:#a3cfbb;">
                                    <span class="rate-banner-icon">✅</span>
                                    <span style="color:#0f5132; font-weight:600;">Thank you for your rating!</span>
                                </div>
                            <?php else: ?>
                                <div class="rate-seller-banner">
                                    <span class="rate-banner-icon">🏷️</span>
                                    <span class="rate-banner-text">This item has been <strong>sold</strong>.</span>
                                    <form method="POST" class="rate-banner-form" onsubmit="return confirmRate(this)">
                                        <input type="hidden" name="inbox_quick_rate" value="1">
                                        <input type="hidden" name="seller_id"       value="<?php echo $listing_seller_id; ?>">
                                        <input type="hidden" name="listing_id"      value="<?php echo $selected_conversation['listing_id']; ?>">
                                        <input type="hidden" name="conversation_id" value="<?php echo $selected_conversation['conversation_id']; ?>">
                                        <label style="font-size:13px;font-weight:600;color:#555;">Rate Seller:</label>
                                        <select name="rating" class="star-dropdown" required>
                                            <option value="" disabled selected>⭐ Stars</option>
                                            <option value="5">⭐⭐⭐⭐⭐ 5 — Excellent</option>
                                            <option value="4">⭐⭐⭐⭐ 4 — Good</option>
                                            <option value="3">⭐⭐⭐ 3 — Okay</option>
                                            <option value="2">⭐⭐ 2 — Poor</option>
                                            <option value="1">⭐ 1 — Terrible</option>
                                        </select>
                                        <button type="submit" class="rate-submit-btn">Submit</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <form method="POST" class="message-form">
                            <input type="hidden" name="conversation_id" value="<?php echo $selected_conversation['conversation_id']; ?>">
                            <input type="text" 
                                   name="message_text" 
                                   class="message-input" 
                                   placeholder="Type a message..." 
                                   required 
                                   autocomplete="off">
                            <button type="submit" name="send_message" class="send-btn">Send</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <p>Select a conversation to start messaging</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const conversationId = <?php echo $selected_conversation ? $selected_conversation['conversation_id'] : 'null'; ?>;
        const currentUserId = <?php echo $user_id; ?>;

        function confirmRate(form) {
            const sel = form.querySelector('select[name="rating"]');
            if (!sel.value) { alert('Please select a star rating.'); return false; }
            return confirm('Submit ' + sel.value + '-star rating for this seller?');
        }
        
        // Mobile navigation functions
        function showConversations() {
            const container = document.getElementById('messagesContainer');
            const conversationsList = container.querySelector('.conversations-list');
            const chatArea = container.querySelector('.chat-area');
            const mobileNav = document.querySelector('.mobile-nav-container');
            
            if (conversationsList && chatArea) {
                conversationsList.classList.add('show-mobile');
                chatArea.classList.add('hide-mobile');
                if (mobileNav) mobileNav.style.display = 'none';
            }
        }
        
        function showChat() {
            const container = document.getElementById('messagesContainer');
            const conversationsList = container.querySelector('.conversations-list');
            const chatArea = container.querySelector('.chat-area');
            const mobileNav = document.querySelector('.mobile-nav-container');
            
            if (conversationsList && chatArea) {
                conversationsList.classList.remove('show-mobile');
                chatArea.classList.remove('hide-mobile');
                if (mobileNav && window.innerWidth <= 968) {
                    mobileNav.style.display = 'block';
                }
            }
        }
        
        // Show chat on mobile when conversation is selected
        if (conversationId && window.innerWidth <= 968) {
            showChat();
        }
        
        // Handle window resize
        window.addEventListener('resize', () => {
            const mobileNav = document.querySelector('.mobile-nav-container');
            if (window.innerWidth > 968) {
                if (mobileNav) mobileNav.style.display = 'none';
                const container = document.getElementById('messagesContainer');
                const conversationsList = container.querySelector('.conversations-list');
                const chatArea = container.querySelector('.chat-area');
                if (conversationsList) conversationsList.classList.remove('show-mobile');
                if (chatArea) chatArea.classList.remove('hide-mobile');
            } else if (conversationId) {
                if (mobileNav) mobileNav.style.display = 'block';
            }
        });
        
        // Auto-scroll to bottom of messages
        function scrollToBottom() {
            const messagesArea = document.getElementById('messagesArea');
            if (messagesArea) {
                messagesArea.scrollTop = messagesArea.scrollHeight;
            }
        }
        
        scrollToBottom();
        
        // Format message HTML
        function formatMessage(msg) {
            const isSent = msg.sender_id == currentUserId;
            const messageClass = isSent ? 'sent' : 'received';
            const date = new Date(msg.sent_at);
            const timeStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ', ' + 
                           date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            
            return `
                <div class="message ${messageClass}">
                    <div class="message-bubble">
                        ${msg.message_text.replace(/\n/g, '<br>')}
                        <div class="message-time">${timeStr}</div>
                    </div>
                </div>
            `;
        }
        
        // Fetch new messages without page reload
        async function fetchMessages() {
            if (!conversationId) return;
            
            try {
                const response = await fetch(`inbox.php?fetch_messages=1&conversation_id=${conversationId}`);
                const data = await response.json();
                
                if (data.success) {
                    const messagesArea = document.getElementById('messagesArea');
                    const currentScroll = messagesArea.scrollTop;
                    const isAtBottom = messagesArea.scrollHeight - messagesArea.scrollTop <= messagesArea.clientHeight + 50;
                    
                    // Update messages
                    const emptyState = messagesArea.querySelector('.empty-state');
                    if (data.messages.length > 0 && emptyState) {
                        messagesArea.innerHTML = data.messages.map(formatMessage).join('');
                        scrollToBottom();
                    } else if (data.messages.length > 0) {
                        messagesArea.innerHTML = data.messages.map(formatMessage).join('');
                        // Only auto-scroll if user was already at bottom
                        if (isAtBottom) {
                            scrollToBottom();
                        }
                    }
                }
            } catch (error) {
                console.error('Error fetching messages:', error);
            }
        }
        
        // Send message via AJAX
        const messageForm = document.querySelector('.message-form');
        if (messageForm) {
            messageForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const messageInput = messageForm.querySelector('.message-input');
                const sendBtn = messageForm.querySelector('.send-btn');
                const messageText = messageInput.value.trim();
                
                if (!messageText) return;
                
                // Disable button during send
                sendBtn.disabled = true;
                sendBtn.textContent = 'Sending...';
                
                try {
                    // Create FormData and add send_message field
                    const formData = new FormData(messageForm);
                    formData.append('send_message', '1');
                    
                    const response = await fetch('inbox.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        messageInput.value = '';
                        await fetchMessages();
                        scrollToBottom();
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    alert('Failed to send message. Please try again.');
                } finally {
                    sendBtn.disabled = false;
                    sendBtn.textContent = 'Send';
                    messageInput.focus();
                }
            });
        }
        
        // Auto-refresh messages every 3 seconds (only fetch new data, no page reload)
        if (conversationId) {
            setInterval(fetchMessages, 3000);
        }
    </script>
</body>
</html>

