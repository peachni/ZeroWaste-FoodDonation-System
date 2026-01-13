<?php
// db_connect.php

// FIX: Use the correct path to reach the other folder
include __DIR__ . '/../DB_INTEGRATION/db_config.php';

// Use the local variables defined in db_config.php
$conn = new mysqli($local_host, $local_user, $local_pass, $local_db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>