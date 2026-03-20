<?php
// This page is no longer used - redirect to messages
session_start();
if (isset($_GET['seller_id']) && isset($_GET['listing_id'])) {
    header("Location: messages.php?seller_id=" . (int)$_GET['seller_id'] . "&listing_id=" . (int)$_GET['listing_id']);
} else {
    header("Location: homepage.php");
}
exit;
