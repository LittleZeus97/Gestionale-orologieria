<?php
session_start();
echo "Session status: " . session_status() . "<br>";
echo "User ID: " . (isset(['user_id']) ? ['user_id'] : 'not set') . "<br>";
echo "User Name: " . (isset(['user_name']) ? ['user_name'] : 'not set') . "<br>";
?>
