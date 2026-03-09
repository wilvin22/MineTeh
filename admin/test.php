<?php
echo "Admin folder is working!";
echo "<br>Current directory: " . __DIR__;
echo "<br>Files in this directory:";
echo "<pre>";
print_r(scandir(__DIR__));
echo "</pre>";
?>
