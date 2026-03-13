<?php
// Ultra simple test file
echo "Admin folder is accessible!";
echo "<br>PHP is working!";
echo "<br>Current directory: " . __DIR__;
echo "<br>File exists check:";
echo "<br>- login.php: " . (file_exists(__DIR__ . '/login.php') ? 'YES' : 'NO');
echo "<br>- index.php: " . (file_exists(__DIR__ . '/index.php') ? 'YES' : 'NO');
echo "<br>- config.php: " . (file_exists(__DIR__ . '/../config.php') ? 'YES' : 'NO');
?>
