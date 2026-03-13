<?php
session_start();
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
        header("Location: messages.php?conversation_id=$conversation_id");
        exit;
    }
}

// Start new conversation if seller_id is provided
if (isset($_GET['seller_id']) && isset($_GET['listing_id'])) {
    $seller_id = (int)$_GET['seller_id'];
    $listing_id = (int)$_GET['listing_id'];
    
    // Check if conversation already exists (check both directions)
    $existing = $supabase->customQuery('conversations', 'conversation_id', 
        'listing_id=eq.' . $listing_id . '&or=(and(user1_id.eq.' . $user_id . ',user2_id.eq.' . $seller_id . '),and(user1_id.eq.' . $seller_id . ',user2_id.eq.' . $user_id . '))');
    
    if (!empty($existing)) {
        // Redirect to existing conversation
        header("Location: messages.php?conversation_id=" . $existing[0]['conversation_id']);
        exit;
    } else {
        // Create new conversation
        $new_conversation = $supabase->insert('conversations', [
            'user1_id' => $user_id,
            'user2_id' => $seller_id,
            'listing_id' => $listing_id
        ]);
        
        // Redirect to the new conversation
        if ($new_conversation && is_array($new_conversation) && !empty($new_conversation)) {
            $conv_id = isset($new_conversation[0]['conversation_id']) ? $new_conversation[0]['conversation_id'] : $new_conversation['conversation_id'];
            header("Location: messages.php?conversation_id=" . $conv_id);
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

if (isset($_GET['conversation_id'])) {
    $conversation_id = (int)$_GET['conversation_id'];
    $selected_conversation = $supabase->select('conversations', '*', ['conversation_id' => $conversation_id], true);
    
    if ($selected_conversation) {
        // Get messages
        $messages = $supabase->customQuery('messages', '*', 
            'conversation_id=eq.' . $conversation_id . '&order=sent_at.asc');
        
        // Mark messages as read (update all unread messages from other user)
        if (!empty($messages)) {
            foreach ($messages as $msg) {
                if ($msg['sender_id'] != $user_id && !$msg['is_read']) {
                    $supabase->update('messages', 
                        ['is_read' => true], 
                        ['message_id' => $msg['message_id']]);
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
            $listing_info = $supabase->select('listings', 'title,price', 
                ['id' => $selected_conversation['listing_id']], true);
        }
    }
} elseif (!empty($conversations)) {
    // Select first conversation by default
    header("Location: messages.php?conversation_id=" . $conversations[0]['conversation_id']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - MineTeh</title>
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
                <div class="conversations-header">Messages</div>
                
                <?php if (empty($conversations)): ?>
                    <div style="padding: 20px; text-align: center; color: #666;">
                        No conversations yet
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): 
                        $other_id = ($conv['user1_id'] == $user_id) ? $conv['user2_id'] : $conv['user1_id'];
                        $other = $supabase->select('accounts', 'username,first_name', ['account_id' => $other_id], true);
                        $listing = null;
                        if ($conv['listing_id']) {
                            $listing = $supabase->select('listings', 'title', ['id' => $conv['listing_id']], true);
                        }
                        $is_active = ($selected_conversation && $selected_conversation['conversation_id'] == $conv['conversation_id']);
                        
                        // Count unread messages in this conversation
                        $unread_msgs = $supabase->customQuery('messages', 'message_id', 
                            'conversation_id=eq.' . $conv['conversation_id'] . '&sender_id=neq.' . $user_id . '&is_read=eq.false');
                        $unread_count = count($unread_msgs);
                    ?>
                        <a href="messages.php?conversation_id=<?php echo $conv['conversation_id']; ?>" 
                           class="conversation-item <?php echo $is_active ? 'active' : ''; ?>" 
                           style="text-decoration: none; color: inherit; position: relative;">
                            <div class="conversation-avatar">
                                <?php echo strtoupper(substr($other['first_name'], 0, 1)); ?>
                            </div>
                            <div class="conversation-info">
                                <div class="conversation-name">
                                    <?php echo htmlspecialchars($other['first_name']); ?>
                                    <?php if ($unread_count > 0): ?>
                                        <span class="unread-badge"><?php echo $unread_count; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-preview">
                                    <?php echo $listing ? htmlspecialchars($listing['title']) : 'Chat'; ?>
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
                        <div class="chat-user-name">
                            <?php echo htmlspecialchars($other_user['first_name'] . ' ' . $other_user['last_name']); ?>
                        </div>
                        <?php if ($listing_info): ?>
                            <div class="chat-listing-info">
                                About: <?php echo htmlspecialchars($listing_info['title']); ?> 
                                (₱<?php echo number_format($listing_info['price'], 2); ?>)
                            </div>
                        <?php endif; ?>
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
                const response = await fetch(`messages.php?fetch_messages=1&conversation_id=${conversationId}`);
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
                
                console.log('Form submitted, message:', messageText);
                
                if (!messageText) {
                    console.log('Empty message, not sending');
                    return;
                }
                
                // Disable button during send
                sendBtn.disabled = true;
                sendBtn.textContent = 'Sending...';
                
                try {
                    // Create FormData and add send_message field
                    const formData = new FormData(messageForm);
                    formData.append('send_message', '1');
                    
                    console.log('Sending to server...');
                    console.log('FormData contents:');
                    for (let pair of formData.entries()) {
                        console.log(pair[0] + ': ' + pair[1]);
                    }
                    
                    const response = await fetch('messages.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    
                    console.log('Response status:', response.status);
                    const responseText = await response.text();
                    console.log('Response text:', responseText);
                    
                    const data = JSON.parse(responseText);
                    console.log('Parsed data:', data);
                    
                    if (data.success) {
                        console.log('Message sent successfully!');
                        messageInput.value = '';
                        await fetchMessages();
                        scrollToBottom();
                    } else {
                        console.error('Server returned success=false');
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    alert('Failed to send message. Check console for details.');
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
