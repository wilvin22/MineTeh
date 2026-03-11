<?php
include "../database/supabase.php";

$listing_id = $_GET['listing_id'];
$upload_folder = "../uploads/";

if (isset($_POST['upload_images'])) {
    foreach ($_FILES['images']['tmp_name'] as $index => $tmp_name) {
        $file_name = uniqid() . "_" . $_FILES['images']['name'][$index];
        $target_path = $upload_folder . $file_name;

        if (move_uploaded_file($tmp_name, $target_path)) {
            $supabase->insert('listing_images', [
                'listing_id' => $listing_id,
                'image_path' => $target_path
            ]);
        }
    }

    header("Location: ../home/create-listing.php?id=$listing_id");
    exit;
}
?>
