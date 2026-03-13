<?php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'API path is working!',
    'file' => __FILE__,
    'dir' => __DIR__
]);
