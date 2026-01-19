<?php
$host = "localhost";
$db   = "mineteh";
$user = "root";
$pass = "";

$conn = mysqli_connect("localhost", "root", "", "mineteh");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
} 
?>