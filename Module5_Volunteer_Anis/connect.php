<?php
// Database Configuration
$host     = "localhost";
$user     = "root";      
$pass     = "";         
$database = "volunteer"; 

// 1. Establish Connection
$conn = mysqli_connect($host, $user, $pass, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

/**
 * Helper function to execute SQL queries
 * Returns the result object for SELECT, or TRUE/FALSE for others
 */
function query($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        die("Query Error: " . mysqli_error($conn) . " | SQL: " . $sql);
    }
    
    return $result;
}

/**
 * Helper function to clean data (SQL Injection protection)
 */
function escape($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}
?>