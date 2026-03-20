<?php
// Redirect all messages.php links to inbox.php
$query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: inbox.php" . $query);
exit;
