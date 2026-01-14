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

// --- 2. FETCH CURRENT LOG & RECIPIENT INFO (FIXED: Removed JOIN volunteer) ---
$result = query("SELECT p.*, d.donee_id FROM pickup p JOIN donation d ON p.donation_id = d.donation_id WHERE p.pickup_id='$id'");
$pickup = $result->fetch_assoc();
if (!$pickup) die("Logistics record not found.");

$current_vol_id = $pickup['volunteer_id'];
$current_vol_name = "Unknown Personnel";

// --- 3. FETCH VOLUNTEER DATA FROM ANIS (Module 5) ---
$volunteer_list = [];
$anis_online = false;
try {
    $conn_anis = new mysqli();
    $conn_anis->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
    $connected = @$conn_anis->real_connect("10.175.254.2", "nis", "1234", "volunteer");
    
    if ($connected && !$conn_anis->connect_error) {
        $anis_online = true;
        
        // A. Fetch current volunteer's name for the info box
        $res_curr = $conn_anis->query("SELECT name FROM volunteer WHERE volunteer_id = '$current_vol_id' LIMIT 1");
        if ($res_curr && $row_curr = $res_curr->fetch_assoc()) {
            $current_vol_name = $row_curr['name'];
        }

        // B. Fetch all available volunteers for the Searchable Datalist
        $res_v = $conn_anis->query("SELECT volunteer_id, name FROM volunteer WHERE availability_status = 'Available'");
        while($row = $res_v->fetch_assoc()) { $volunteer_list[] = $row; }
        
        $conn_anis->close();
    }
} catch (Exception $e) { $anis_online = false; }

// --- 4. PROCESS UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_status = escape($_POST['pickup_status']);

    // NEW: If Admin cancels the logistics log, trigger the reclamation engine
    if ($p_status === 'Cancelled') {
        $don_id = $pickup['donation_id']; // We need the donation reference for the rollback
        header("Location: process_cancel.php?id=$don_id");
        exit;
    }

    // Existing logic for Normal Updates (Swapping volunteers, changing times, etc.)
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
                // Free old volunteer
                $anis_upd->query("UPDATE volunteer SET availability_status = 'Available', donee_id = NULL WHERE volunteer_id = '$old_vol_id'");
                // Assign new volunteer (using Anis's INT requirement for donee_id)
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
<title>Update Dispatch | Zero Hunger</title>
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
.breadcrumb .current { color: #ffffff; }
.breadcrumb i { font-size: 9px; margin: 0 8px; color: rgba(255,255,255,0.4); display: inline-flex; align-items: center; }

.header-right { display: flex; align-items: center; gap: 20px; }
.module-health { display: flex; gap: 12px; align-items: center; }
.health-item { background: rgba(0,0,0,0.7); color: white; padding: 6px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; display: flex; align-items: center; gap: 6px; transition: 0.3s; }
.dot { height: 7px; width: 7px; border-radius: 50%; }
.on { background: #2ecc71; box-shadow: 0 0 8px #2ecc71; } .off { background: #e74c3c; }

.main-wrapper { max-width: 650px; margin: 30px auto; padding: 0 20px; }
.welcome-card { background: white; padding: 15px 25px; border-radius: 12px; border-left: 10px solid var(--dark-orange); box-shadow: 0 4px 15px var(--shadow); margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
.welcome-card h2 { font-family: 'Segoe UI', sans-serif; margin: 0; font-size: 18px; font-weight: 700; }
.id-pill { background: #fff3e0; color: #d35400; padding: 4px 15px; border-radius: 50px; font-weight: 800; font-size: 12px; border: 1.5px solid #ffe0b2; display: inline-block; margin-top: 5px; }

.form-card { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 10px 30px var(--shadow); border-top: 5px solid var(--dark-orange); }
label { display: block; font-family: 'Trebuchet MS', sans-serif; font-size: 12px; text-transform: uppercase; font-weight: 800; color: #7f8c8d; margin-bottom: 8px; }
input, select, textarea { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 8px; font-family: 'Trebuchet MS', sans-serif; font-size: 15px; margin-bottom: 20px; box-sizing: border-box; outline: none; transition: 0.3s; }
input[readonly] { background: #f9f9f9; color: #999; cursor: not-allowed; }
input:focus { border-color: var(--primary); }

.current-info { background: #fdf2e9; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; color: #a04000; font-weight: 600; border: 1px solid #fabd8d; }
.btn-save { width: 100%; background: var(--dark-orange); color: white; border: none; padding: 15px; border-radius: 8px; font-weight: 800; cursor: pointer; transition: 0.3s; font-size: 13px; letter-spacing: 1px; }
.btn-save:hover { background: #803300; transform: translateY(-2px); }

.fas { transition: 0.3s; } .fas:hover { opacity: 0.7; transform: scale(1.1); }
</style>
</head>
<body>

<header>
    <div class="header-left">
        <h1>Zero Hunger Hub</h1>
        <div class="breadcrumb">
            <a href="http://10.175.254.163:3000/adminMenu.php" class="past">Menu</a> <i class="fas fa-chevron-right"></i> 
            <a href="pickup.php" class="past">Logistics</a> <i class="fas fa-chevron-right"></i>
            <span class="current">Update Log</span>
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
            <h2>Edit Dispatch Record</h2>
            <div class="id-pill"><?= htmlspecialchars($display_id) ?> &nbsp;&nbsp;&nbsp; ADMIN</div>
        </div>
    </div>

    <div class="form-card">
        <div class="current-info">
            <i class="fas fa-user-check"></i> Assigned: 
            <strong><?= htmlspecialchars($current_vol_name) ?></strong> (ID: <?= $current_vol_id ?>)
        </div>

        <form method="POST">
            <label>Donation Reference (Locked)</label>
            <input type="text" name="donation_id" value="<?= htmlspecialchars($pickup['donation_id']) ?>" readonly>

            <label>Volunteer Personnel (Search by name)</label>
            <input list="vol_search" name="volunteer_id" value="<?= $pickup['volunteer_id'] ?>" placeholder="Type name or ID..." required>
            <datalist id="vol_search">
                <?php foreach ($volunteer_list as $v): ?>
                    <option value="<?= $v['volunteer_id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
                <?php endforeach; ?>
            </datalist>

            <div style="display:flex; gap:15px;">
                <div style="flex:1;"><label>Scheduled Date</label><input type="date" name="pickup_date" value="<?= $pickup['pickup_date'] ?>" required></div>
                <div style="flex:1;"><label>Scheduled Time</label><input type="time" name="pickup_time" value="<?= $pickup['pickup_time'] ?>" required></div>
            </div>

            <label>Logistics Lifecycle Status</label>
            <select name="pickup_status">
                <option value="Scheduled" <?= $pickup['pickup_status']=='Scheduled'?'selected':'' ?>>Scheduled</option>
                <option value="Collected" <?= $pickup['pickup_status']=='Collected'?'selected':'' ?>>Collected (In Transit)</option>
                <option value="Delivered" <?= $pickup['pickup_status']=='Delivered'?'selected':'' ?>>Delivered (Fulfillment)</option>
                <option value="Cancelled" <?= $pickup['pickup_status']=='Cancelled'?'selected':'' ?>>Cancelled</option>
            </select>

            <label>Dispatcher Notes</label>
            <textarea name="notes" rows="3" placeholder="Handling instructions..."><?= htmlspecialchars($pickup['notes']) ?></textarea>

            <button type="submit" class="btn-save">SAVE DISPATCH UPDATES</button>
        </form>
        <a href="pickup.php" style="display:block; text-align:center; margin-top:20px; color:#95a5a6; text-decoration:none; font-size:13px; font-weight:800;">Cancel and Return</a>
    </div>
</div>

</body>
</html>