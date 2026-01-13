<?php
session_start();
include 'dbconnect.php';

// Ensure only admin can delete users
if (!isset($_SESSION['admin_username'])) {
    header("Location: adminLogin.php");
    exit();
}

// Check if data is provided
if (!isset($_GET['type']) || !isset($_GET['id'])) {
    header("Location: adminMenu.php");
    exit();
}

$type = $_GET['type'];       // donor or donee
$id   = intval($_GET['id']); // user ID

// Validate type (very important)
if ($type !== "donor" && $type !== "donee") {
    header("Location: adminMenu.php");
    exit();
}

// Build delete query
if ($type === "donor") {
    $query = "DELETE FROM donor WHERE donor_id = $1";
} else {
    $query = "DELETE FROM donee WHERE donee_id = $1";
}

// Execute delete
$result = pg_query_params($conn, $query, array($id));

// Redirect back after delete
if ($result) {
    echo "<script>alert('User deleted successfully.'); window.location='adminMenu.php';</script>";
} else {
    echo "<script>alert('Delete failed: " . pg_last_error($conn) . "'); window.location='adminMenu.php';</script>";
}
exit();
?>
