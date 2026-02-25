<?php
session_start();
include "../database/supabase.php";

$listing_id = $_GET['id'];
$seller_id = $_SESSION['user_id'];

$supabase->update('listings', 
    ['status' => 'CLOSED'],
    ['id' => $listing_id, 'seller_id' => $seller_id]
);

header("Location: listing.php?id=$listing_id");
exit;
?>
