<?php
session_start();
require_once 'connect.php';
mysqli_report(MYSQLI_REPORT_OFF);

// 1. PRIVILEGE CHECK
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: donation.php");
    exit();
}

$id = $_GET['id'] ?? '';
if (!$id) header("Location: donation.php");

$display_id = $_SESSION['admin_id'];

// --- GLOBAL MODULE HEALTH CHECK ---
function check_module($ip, $port, $timeout = 0.3) {
    $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    if ($fp) { fclose($fp); return true; }
    return false;
}

$module_status = [
    'User'      => check_module('10.175.254.163', 5432), 
    'Food'      => check_module('10.175.254.152', 3306), 
    'Logistics' => true,   
    'Feedback'  => check_module('10.175.254.1', 3306)    
];

// --- FETCH FOOD (BALQIS) for display names ---
$food_map = [];
if ($module_status['Food']) {
    $conn_b = @new mysqli("10.175.254.152", "balqis", "Balqis123", "workshop2");
    if (!$conn_b->connect_error) {
        $res_f = $conn_b->query("SELECT FoodList_ID, Food_Name FROM food_don_list");
        while($f = $res_f->fetch_assoc()) { $food_map[$f['FoodList_ID']] = $f['Food_Name']; }
        $conn_b->close();
    }
}

// --- FETCH CURRENT DONATION ---
$res = query("SELECT d.*, (SELECT COUNT(*) FROM pickup WHERE donation_id = d.donation_id) as has_delivery FROM donation d WHERE d.donation_id='$id'");
$don = $res->fetch_assoc();
$is_delivery = ($don['has_delivery'] > 0 || ($don['delivery_address'] !== 'Self-Pickup at Source' && !empty($don['delivery_address'])));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = escape($_POST['status']);
    
    // NEW: If Admin cancels, trigger the reclamation engine (stock/volunteer reset)
    if ($status === 'Cancelled') {
        header("Location: process_cancel.php?id=$id");
        exit;
    }

    // Normal status update (Pending, Scheduled, Collected, Delivered)
    query("UPDATE donation SET status='$status' WHERE donation_id='$id'");
    
    header("Location: donation.php?msg=updated");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Modify Transaction | Zero Hunger</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
:root { --primary: #f39c12; --dark-orange: #a04000; --bg: #fdf6e3; --text-dark: #2c3e50; --shadow: rgba(0,0,0,0.08); }
body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text-dark); }

/* HEADER - Standardized and Vertically Centered */
header { background: linear-gradient(90deg, #f39c12, #f1c40f); padding: 20px 40px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 10px var(--shadow); }
.header-left { display: flex; flex-direction: column; gap: 4px; }
header h1 { font-family: 'Segoe UI', sans-serif; color: white; margin: 0; font-size: 1.4rem; font-weight: 800; line-height: 1; }
.breadcrumb { font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; margin-top: 4px; line-height: 1; }
.breadcrumb .past { color: rgba(255,255,255,0.55); text-decoration: none; } 
.breadcrumb .past:hover { color: #ffffff; opacity: 1; }
.breadcrumb .current { color: #ffffff; }
.breadcrumb i { font-size: 9px; margin: 0 8px; color: rgba(255,255,255,0.4); display: inline-flex; align-items: center; }

.header-right { display: flex; align-items: center; gap: 20px; }
.module-health { display: flex; gap: 12px; align-items: center; }
.health-item { background: rgba(0,0,0,0.7); color: white; padding: 6px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; display: flex; align-items: center; gap: 6px; transition: 0.3s; }
.dot { height: 7px; width: 7px; border-radius: 50%; }
.on { background: #2ecc71; } .off { background: #e74c3c; }

/* MAIN CONTAINER */
.main-wrapper { max-width: 650px; margin: 30px auto; padding: 0 20px; }

/* WELCOME CARD */
.welcome-card { background: white; padding: 15px 25px; border-radius: 12px; border-left: 10px solid var(--dark-orange); box-shadow: 0 4px 15px var(--shadow); margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
.welcome-card h2 { font-family: 'Segoe UI', sans-serif; margin: 0; font-size: 18px; font-weight: 700; }
.id-pill { background: #fff3e0; color: #d35400; padding: 4px 15px; border-radius: 50px; font-weight: 800; font-size: 12px; border: 1.5px solid #ffe0b2; display: inline-block; margin-top: 5px; }

/* FORM CARD */
.form-card { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 10px 30px var(--shadow); border-top: 5px solid var(--dark-orange); }

label { display: block; font-family: 'Trebuchet MS', sans-serif; font-size: 12px; text-transform: uppercase; font-weight: 800; color: #7f8c8d; margin-bottom: 8px; }
input, select, textarea { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 8px; font-family: 'Trebuchet MS', sans-serif; font-size: 15px; margin-bottom: 20px; box-sizing: border-box; outline: none; transition: 0.3s; }

/* Greyed out fields */
input[readonly], textarea[readonly], .disabled-view { background: #f9f9f9; color: #999; cursor: not-allowed; }

input:focus, select:focus { border-color: var(--primary); }

.btn-save { width: 100%; background: var(--dark-orange); color: white; border: none; padding: 15px; border-radius: 8px; font-weight: 800; cursor: pointer; transition: 0.3s; font-size: 13px; letter-spacing: 1px; }
.btn-save:hover { background: #803300; transform: translateY(-2px); }

.mode-badge { padding: 5px 12px; border-radius: 5px; font-size: 11px; font-weight: 800; text-transform: uppercase; display: inline-block; margin-bottom: 20px; }
.mode-delivery { background: #e3f2fd; color: #1976d2; }
.mode-pickup { background: #f3e5f5; color: #7b1fa2; }
</style>
</head>
<body>

<header>
    <div class="header-left">
        <h1>Zero Hunger Hub</h1>
        <div class="breadcrumb">
            <a href="donation.php" class="past">Donation</a> <i class="fas fa-chevron-right"></i> 
            <span class="current">Modify Transaction</span>
        </div>
    </div>
    <div class="header-right">
        <div class="module-health">
            <?php foreach($module_status as $name => $status): ?>
                <div class="health-item"><div class="dot <?= $status?'on':'off' ?>"></div> <?= $name ?></div>
            <?php endforeach; ?>
        </div>
        <a href="http://10.175.254.163:3000/userLogOut.php" style="background: white; color: #c0392b; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 800; border: 1px solid #c0392b; margin-left:15px;">LOGOUT</a>
    </div>
</header>

<div class="main-wrapper">
    <div class="welcome-card">
        <div>
            <h2>Transaction Audit #<?= htmlspecialchars($id) ?></h2>
            <div class="id-pill"><?= htmlspecialchars($display_id) ?> &nbsp;&nbsp;&nbsp; ADMIN</div>
        </div>
    </div>

    <div class="form-card">
        <!-- Display Fulfillment Mode (Locked) -->
        <div class="mode-badge <?= $is_delivery ? 'mode-delivery' : 'mode-pickup' ?>">
            <i class="fas <?= $is_delivery ? 'fa-truck' : 'fa-hand-holding' ?>"></i> 
            Fulfillment: <?= $is_delivery ? 'Delivery Required' : 'Self-Pickup' ?>
        </div>

        <form method="POST">
            <label>Recipient ID (Locked)</label>
            <input type="text" value="<?= htmlspecialchars($don['donee_id']) ?>" readonly class="disabled-view">

            <label>Item Description (Locked)</label>
            <input type="text" value="<?= $food_map[$don['foodlist_id']] ?? $don['foodlist_id'] ?>" readonly class="disabled-view">

            <div style="display:flex; gap:15px;">
                <div style="flex:1;">
                    <label>Quantity (Locked)</label>
                    <input type="text" value="<?= $don['quantity'] ?> Units" readonly class="disabled-view">
                </div>
                <div style="flex:1;">
                    <label>Order Date (Locked)</label>
                    <input type="text" value="<?= date('M d, Y', strtotime($don['donation_date'])) ?>" readonly class="disabled-view">
                </div>
            </div>

            <?php if ($is_delivery): ?>
                <label>Delivery Destination (Locked)</label>
                <textarea rows="2" readonly class="disabled-view"><?= htmlspecialchars($don['delivery_address']) ?></textarea>
                <p style="font-size: 11px; color: #7f8c8d; margin-top: -15px; margin-bottom: 20px;">
                    * To assign/change a volunteer for this delivery, please use the <strong>Logistics</strong> module.
                </p>
            <?php endif; ?>

            <label>Administrative Status Update</label>
            <select name="status">
                <option value="Pending" <?= $don['status']=='Pending'?'selected':'' ?>>Pending</option>
                <option value="Scheduled" <?= $don['status']=='Scheduled'?'selected':'' ?>>Scheduled</option>
                <option value="Collected" <?= $don['status']=='Collected'?'selected':'' ?>>Collected (Picked Up)</option>
                <option value="Delivered" <?= $don['status']=='Delivered'?'selected':'' ?>>Delivered (Fulfillment Complete)</option>
                <option value="Cancelled" <?= $don['status']=='Cancelled'?'selected':'' ?>>Cancelled</option>
            </select>

            <button type="submit" class="btn-save">CONFIRM SYSTEM UPDATE</button>
        </form>
        <a href="donation.php" style="display:block; text-align:center; margin-top:20px; color:#95a5a6; text-decoration:none; font-size:13px; font-weight:800;">Cancel and Return</a>
    </div>
</div>

</body>
</html>