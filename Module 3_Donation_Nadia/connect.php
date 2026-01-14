<?php
// connect.php
// Database connection settings - UPDATED TO GROUP CREDENTIALS
$host = "10.175.254.3"; 
$port = 3307;
$user = "NadPnya";
$pass = ""; // Leave empty as per your group credentials
$db   = "zerowaste";

// Create connection
$conn = new mysqli($host, $user, $pass, $db, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Helper function to safely escape input
function escape($data) {
    global $conn;
    return htmlspecialchars($conn->real_escape_string($data));
}

// Simple query helper
function query($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    return $result;
}
?>