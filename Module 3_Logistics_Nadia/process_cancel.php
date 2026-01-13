<?php
session_start();
require_once 'connect.php';
mysqli_report(MYSQLI_REPORT_OFF);

// 1. SECURITY & DATA FETCH
if (!isset($_SESSION['role'])) { die("Access Denied."); }

$don_id = $_GET['id'] ?? die("Missing Reference ID.");
$role   = strtolower($_SESSION['role']);

// Fetch existing data before we cancel it so we know what to "undo"
$check_sql = "SELECT d.foodlist_id, d.quantity, d.status, p.volunteer_id 
              FROM donation d 
              LEFT JOIN pickup p ON d.donation_id = p.donation_id 
              WHERE d.donation_id = '$don_id' LIMIT 1";
$res_check = query($check_sql);
$data = $res_check->fetch_assoc();

if (!$data) {
    die("Record not found in database.");
}

// PROTECTION: Donees can only cancel if status is 'Pending'
if ($role === 'donee' && $data['status'] !== 'Pending') {
    die("<script>alert('Cancellation failed: Only Pending requests can be cancelled by users.'); window.location.href='donation.php';</script>");
}

// 2. THE UNDO CHAIN (Multi-Laptop Synchronization)

// --- A. RESTORE STOCK TO BALQIS (Laptop C) ---
$food_id = $data['foodlist_id'];
$qty     = (int)$data['quantity'];

$conn_b = new mysqli();
$conn_b->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2); // 2 second safety timeout
if (@$conn_b->real_connect("10.175.254.152", "balqis", "Balqis123", "workshop2")) {
    // Add back the quantity and ensure the item is marked as 'Available'
    $conn_b->query("UPDATE food_don_list SET Quantity = Quantity + $qty, Status = 'Available' WHERE FoodList_ID = '$food_id'");
    $conn_b->close();
}

// --- B. FREE THE VOLUNTEER ON ANIS'S SERVER (Laptop D) ---
$vol_id = $data['volunteer_id'];
if (!empty($vol_id)) {
    $conn_v = new mysqli();
    $conn_v->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
    if (@$conn_v->real_connect("10.175.254.2", "nis", "1234", "volunteer")) {
        // Set back to Available and remove the Donee link as per Anis's request
        $conn_v->query("UPDATE volunteer SET availability_status = 'Available', donee_id = NULL WHERE volunteer_id = '$vol_id'");
        $conn_v->close();
    }
}

// --- C. UPDATE LOCAL RECORDS (Nadia - Laptop B) ---
// We update the Pickup record first; your DB trigger 'trg_pickup_status_update' 
// will automatically set donation.status to 'Cancelled' as well.
$check_p = query("SELECT pickup_id FROM pickup WHERE donation_id = '$don_id'");
if ($check_p->num_rows > 0) {
    query("UPDATE pickup SET pickup_status = 'Cancelled' WHERE donation_id = '$don_id'");
} else {
    // If no pickup existed yet (still pending phase)
    query("UPDATE donation SET status = 'Cancelled' WHERE donation_id = '$don_id'");
}

// 3. SUCCESS REDIRECT
header("Location: donation.php?msg=cancelled");
exit;