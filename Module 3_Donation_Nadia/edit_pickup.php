<?php
session_start();
require_once 'connect.php';
mysqli_report(MYSQLI_REPORT_OFF);

// 1. PRIVILEGE CHECK
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: pickup.php");
    exit();
}

$id = $_GET['id'] ?? '';
if (!$id) header("Location: pickup.php");

$role = strtolower($_SESSION['role']);
$user_name = $_SESSION['user_name'];
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

// --- 2. FETCH CURRENT LOG & ADDRESS (JOINED) ---
$result = query("SELECT p.*, d.donee_id, d.delivery_address 
                 FROM pickup p 
                 JOIN donation d ON p.donation_id = d.donation_id 
                 WHERE p.pickup_id='$id'");
$pickup = $result->fetch_assoc();
if (!$pickup) die("Delivery record not found.");

$current_vol_id = $pickup['volunteer_id'];
$current_vol_name = "Unknown Personnel";

// --- 3. FETCH VOLUNTEER DATA FROM ANIS (Module 5) ---
$volunteer_options = [];
$anis_online = false;
try {
    $conn_anis = new mysqli();
    $conn_anis->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
    $connected = @$conn_anis->real_connect("10.175.254.2", "nis", "1234", "volunteer");
    
    if ($connected && !$conn_anis->connect_error) {
        $anis_online = true;
        
        // Fetch current volunteer's name for context
        $res_curr = $conn_anis->query("SELECT name FROM volunteer WHERE volunteer_id = '$current_vol_id' LIMIT 1");
        if ($res_curr && $row_curr = $res_curr->fetch_assoc()) {
            $current_vol_name = $row_curr['name'];
        }

        // Fetch all available volunteers including their area_assigned
        $res_v = $conn_anis->query("SELECT volunteer_id, name, area_assigned FROM volunteer WHERE availability_status = 'Available'");
        while($row = $res_v->fetch_assoc()) { $volunteer_options[] = $row; }
        
        $conn_anis->close();
    }
} catch (Exception $e) { $anis_online = false; }

// --- 4. PROCESS UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_status = escape($_POST['pickup_status']);

    if ($p_status === 'Cancelled') {
        $don_id = $pickup['donation_id'];
        header("Location: process_cancel.php?id=$don_id");
        exit;
    }

    $old_vol_id    = $pickup['volunteer_id'];
    $new_vol_id    = escape($_POST['volunteer_id']);
    $target_donee  = (int)$pickup['donee_id'];
    $p_date        = escape($_POST['pickup_date']);
    $p_time        = escape($_POST['pickup_time']);
    $notes         = escape($_POST['notes']);

    $update_sql = "UPDATE pickup SET 
                   volunteer_id='$new_vol_id', 
                   pickup_date='$p_date', 
                   pickup_time='$p_time', 
                   pickup_status='$p_status', 
                   notes='$notes' 
                   WHERE pickup_id='$id'";

    if (query($update_sql)) {
        if ($old_vol_id !== $new_vol_id && $anis_online) {
            $anis_upd = new mysqli("10.175.254.2", "nis", "1234", "volunteer");
            if (!$anis_upd->connect_error) {
                $anis_upd->query("UPDATE volunteer SET availability_status = 'Available', donee_id = NULL WHERE volunteer_id = '$old_vol_id'");
                $anis_upd->query("UPDATE volunteer SET availability_status = 'Busy', donee_id = $target_donee WHERE volunteer_id = '$new_vol_id'");
                $anis_upd->close();
            }
        }
        header("Location: pickup.php?msg=updated");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Delivery | Zero Hunger</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
:root { --primary: #f39c12; --dark-orange: #a04000; --bg: #fdf6e3; --text-dark: #2c3e50; --shadow: rgba(0,0,0,0.08); }
body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text-dark); }

header { background: linear-gradient(90deg, #f39c12, #f1c40f); padding: 20px 40px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 10px var(--shadow); }
.header-left { display: flex; flex-direction: column; gap: 4px; }
header h1 { font-family: 'Segoe UI', sans-serif; color: white; margin: 0; font-size: 1.4rem; font-weight: 800; line-height: 1; }

.breadcrumb { font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; margin-top: 4px; line-height: 1; }
.breadcrumb .past { color: rgba(255,255,255,0.55); text-decoration: none; transition: 0.3s; } 
.breadcrumb .past:hover { color: #ffffff; opacity: 1; }
.breadcrumb .current { color: #ffffff; }
.breadcrumb i { font-size: 9px; margin: 0 8px; color: rgba(255,255,255,0.4); display: inline-flex; align-items: center; }

.header-right { display: flex; align-items: center; gap: 20px; }

.main-wrapper { max-width: 650px; margin: 30px auto; padding: 0 20px; }
.welcome-card { background: white; padding: 15px 25px; border-radius: 12px; border-left: 10px solid var(--dark-orange); box-shadow: 0 4px 15px var(--shadow); margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
.welcome-card h2 { font-family: 'Segoe UI', sans-serif; margin: 0; font-size: 18px; font-weight: 700; }

.form-card { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 10px 30px var(--shadow); border-top: 5px solid var(--dark-orange); }

label { display: block; font-family: 'Trebuchet MS', sans-serif; font-size: 12px; text-transform: uppercase; font-weight: 800; color: #7f8c8d; margin-bottom: 8px; }
input, select, textarea { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 8px; font-family: 'Trebuchet MS', sans-serif; font-size: 15px; margin-bottom: 20px; box-sizing: border-box; outline: none; transition: 0.3s; }
input[readonly] { background: #f9f9f9; color: #999; cursor: not-allowed; }
input:focus, select:focus { border-color: var(--primary); }

.addr-preview { background: #f9f9f9; padding: 12px; border: 2px solid #f0f0f0; border-radius: 8px; font-family: 'Trebuchet MS', sans-serif; font-size: 14px; color: #444; margin-bottom: 20px; min-height: 40px; line-height: 1.4; }

.btn-save { width: 100%; background: var(--dark-orange); color: white; border: none; padding: 15px; border-radius: 8px; font-weight: 800; cursor: pointer; transition: 0.3s; font-size: 13px; letter-spacing: 1px; }
.btn-save:hover { background: #803300; transform: translateY(-2px); }

.fas { transition: 0.3s; } .fas:hover { opacity: 0.7; transform: scale(1.1); }
</style>
</head>
<body>

<header>
    <div class="header-left">
        <h1>Zero Hunger</h1>
        <div class="breadcrumb">
            <a href="http://10.175.254.163:3000/adminMenu.php" class="past">Menu</a> 
            <i class="fas fa-chevron-right"></i> 
            <a href="pickup.php" class="past">Delivery</a> <i class="fas fa-chevron-right"></i>
            <span class="current">Update Delivery</span>
        </div>
    </div>
    <div class="header-right"></div>
</header>

<div class="main-wrapper">
    <div class="welcome-card">
        <div>
            <h2>Edit Delivery Form</h2>
        </div>
    </div>

    <div class="form-card">
        <form method="POST">
            <label>Donation ID</label>
            <input type="text" name="donation_id" value="<?= htmlspecialchars($pickup['donation_id']) ?>" readonly>

            <!-- Greyed out delivery address box -->
            <label>Delivery Destination</label>
            <div class="addr-preview"><?= htmlspecialchars($pickup['delivery_address'] ?? 'No address found.') ?></div>

            <label>Volunteer Personnel</label>
            <?php if ($anis_online): ?>
                <select name="volunteer_id" required>
                    <!-- Show currently assigned volunteer first -->
                    <option value="<?= $pickup['volunteer_id'] ?>" selected>
                        Current: <?= htmlspecialchars($current_vol_name) ?> (<?= $pickup['volunteer_id'] ?>)
                    </option>
                    <optgroup label="Available for Swap">
                        <?php foreach ($volunteer_options as $v): ?>
                            <?php if($v['volunteer_id'] !== $pickup['volunteer_id']): ?>
                                <option value="<?= $v['volunteer_id'] ?>">
                                    <?= htmlspecialchars($v['name']) ?> (Area: <?= htmlspecialchars($v['area_assigned'] ?? 'General') ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            <?php else: ?>
                <input type="text" name="volunteer_id" value="<?= htmlspecialchars($pickup['volunteer_id']) ?>" required>
            <?php endif; ?>

            <div style="display:flex; gap:15px;">
                <div style="flex:1;"><label>Scheduled Date</label><input type="date" name="pickup_date" value="<?= $pickup['pickup_date'] ?>" required></div>
                <div style="flex:1;"><label>Scheduled Time</label><input type="time" name="pickup_time" value="<?= $pickup['pickup_time'] ?>" required></div>
            </div>

            <label>Global Delivery Status</label>
            <select name="pickup_status">
                <option value="Scheduled" <?= $pickup['pickup_status']=='Scheduled'?'selected':'' ?>>Scheduled</option>
                <option value="Collected" <?= $pickup['pickup_status']=='Collected'?'selected':'' ?>>Collected (In Transit)</option>
                <option value="Delivered" <?= $pickup['pickup_status']=='Delivered'?'selected':'' ?>>Delivered (Fulfillment)</option>
                <option value="Cancelled" <?= $pickup['pickup_status']=='Cancelled'?'selected':'' ?>>Cancelled</option>
            </select>

            <label>Delivery Notes</label>
            <textarea name="notes" rows="3" placeholder="Handling instructions..."><?= htmlspecialchars($pickup['notes']) ?></textarea>

            <button type="submit" class="btn-save">SAVE DELIVERY UPDATES</button>
        </form>
        <a href="pickup.php" style="display:block; text-align:center; margin-top:20px; color:#95a5a6; text-decoration:none; font-size:13px; font-weight:800;">Cancel</a>
    </div>
</div>

</body>
</html>