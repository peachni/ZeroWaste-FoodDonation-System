<?php
session_start();
require_once 'connect.php';
mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'donee') {
    die("Access Denied.");
}

$donation_id = $_GET['id'] ?? die("Missing Reference.");

// 1. Identify Volunteer
$res = query("SELECT volunteer_id FROM pickup WHERE donation_id = '$donation_id'");
$pickup = $res->fetch_assoc();
$volunteer_id = $pickup['volunteer_id'] ?? null;

// 2. Update Pickup Status
// The Database Trigger 'trg_pickup_status_update' will automatically 
// set donation.status to 'Delivered' when this query runs.
query("UPDATE pickup SET pickup_status = 'Delivered' WHERE donation_id = '$donation_id'");

// 3. Notify Volunteer Module
if ($volunteer_id) {
    try {
        $anis_conn = new mysqli();
        $anis_conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
        $anis_conn->real_connect("10.175.254.2", "nis", "1234", "volunteer");
        if (!$anis_conn->connect_error) {
            $anis_conn->query("UPDATE volunteer SET availability_status = 'Available' WHERE volunteer_id = '$volunteer_id'");
            $anis_conn->close();
        }
    } catch (Exception $e) {}
}

header("Location: donation.php?msg=received");
exit;