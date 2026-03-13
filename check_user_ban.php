<?php
// This file checks if the logged-in user is banned and logs them out if so
// Include this at the top of any user page after session_start()

if (isset($_SESSION['user_id']) && isset($supabase)) {
    $user = $supabase->select('accounts', 'user_status, status_reason', ['account_id' => $_SESSION['user_id']], true);
    
    if ($user && is_array($user)) {
        $user_status = isset($user['user_status']) ? $user['user_status'] : 'active';
        
        if ($user_status === 'banned') {
            $reason = isset($user['status_reason']) ? $user['status_reason'] : 'No reason provided';
            session_destroy();
            header("Location: ../login.php?error=banned&reason=" . urlencode($reason));
            exit;
        }
    }
}
?>
