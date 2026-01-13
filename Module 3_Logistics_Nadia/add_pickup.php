<?php
session_start();
require_once 'connect.php';
mysqli_report(MYSQLI_REPORT_OFF);

// 1. PRIVILEGE CHECK
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: pickup.php");
    exit();
}

$role = strtolower($_SESSION['role']);
$user_name = $_SESSION['user_name'];
$display_id = $_SESSION['admin_id']; // This is an INT now

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

// --- 2. SAFE FETCH FROM ANIS (Module 5) ---
$volunteer_options = [];
$anis_online = false;
try {
    $conn_anis = new mysqli();
    $conn_anis->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
    $connected = @$conn_anis->real_connect("10.175.254.2", "nis", "1234", "volunteer");

    if ($connected && !$conn_anis->connect_error) {
        $anis_online = true;
        // Fetching volunteers to show in dropdown
        $res_v = $conn_anis->query("SELECT volunteer_id, name FROM volunteer WHERE availability_status = 'Available'");
        if ($res_v) {
            while($row = $res_v->fetch_assoc()) { $volunteer_options[] = $row; }
        }
        $conn_anis->close();
    }
} catch (Exception $e) { $anis_online = false; }

// --- 3. FETCH PENDING ORDERS (Prevents Redundancy) ---
// Note: donee_id is now retrieved as an INT
$donation_list = query("SELECT donation_id, donee_id FROM donation WHERE status = 'Pending'");

// --- 4. PROCESS DISPATCH SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $don_id  = escape($_POST['donation_id']);
    $vol_id  = escape($_POST['volunteer_id']);
    $date    = escape($_POST['pickup_date']);
    $time    = escape($_POST['pickup_time']);
    $notes   = escape($_POST['notes']);

    // Retrieve the associated donee_id (INT) for Anis's update
    $get_donee = query("SELECT donee_id FROM donation WHERE donation_id = '$don_id' LIMIT 1");
    $donee_data = $get_donee->fetch_assoc();
    $recipient_donee_id = (int)$donee_data['donee_id'];

    // Start local transaction
    $conn->begin_transaction();

    try {
        // A. Insert into local Pickup table (Triggers PXXX ID)
        $sql_p = "INSERT INTO pickup (pickup_id, donation_id, volunteer_id, pickup_date, pickup_time, pickup_status, notes) 
                  VALUES ('', '$don_id', '$vol_id', '$date', '$time', 'Scheduled', '$notes')";
        $conn->query($sql_p);

        // B. Update local Donation status to 'Scheduled'
        $conn->query("UPDATE donation SET status = 'Scheduled' WHERE donation_id = '$don_id'");

        // C. Update Anis's Module (Remote)
        if ($anis_online) {
            $anis_upd = new mysqli("10.175.254.2", "nis", "1234", "volunteer");
            if (!$anis_upd->connect_error) {
                // Exact column names from Anis's SQL: donee_id and availability_status
                $anis_upd->query("UPDATE volunteer 
                                  SET donee_id = $recipient_donee_id, 
                                      availability_status = 'Busy' 
                                  WHERE volunteer_id = '$vol_id'");
                $anis_upd->close();
            }
        }

        $conn->commit();
        header("Location: pickup.php?msg=dispatched");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die("Critical System Error: Integration failed. Data rolled back.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>New Dispatch | Zero Hunger</title>
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
.breadcrumb .past:hover { color: #ffffff; opacity: 1; transform: scale(1.05); }
.breadcrumb .current { color: #ffffff; }
.breadcrumb i { font-size: 9px; margin: 0 8px; color: rgba(255,255,255,0.4); display: inline-flex; align-items: center; }

.header-right { display: flex; align-items: center; gap: 20px; }
.module-health { display: flex; gap: 12px; align-items: center; }
.health-item { background: rgba(0,0,0,0.7); color: white; padding: 6px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; display: flex; align-items: center; gap: 6px; transition: 0.3s; }
.dot { height: 7px; width: 7px; border-radius: 50%; }
.on { background: #2ecc71; box-shadow: 0 0 8px #2ecc71; } .off { background: #e74c3c; }
.btn-logout { background: white; color: #c0392b; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 800; transition: 0.3s; }

.main-wrapper { max-width: 650px; margin: 30px auto; padding: 0 20px; }

.welcome-card { background: white; padding: 15px 25px; border-radius: 12px; border-left: 10px solid var(--primary); box-shadow: 0 4px 15px var(--shadow); margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
.welcome-card h2 { font-family: 'Segoe UI', sans-serif; margin: 0; font-size: 18px; font-weight: 700; }
.id-pill { background: #fff3e0; color: #d35400; padding: 4px 15px; border-radius: 50px; font-weight: 800; font-size: 12px; border: 1.5px solid #ffe0b2; display: inline-block; margin-top: 5px; }

.form-card { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 10px 30px var(--shadow); border-top: 5px solid var(--primary); }
label { display: block; font-family: 'Trebuchet MS', sans-serif; font-size: 12px; text-transform: uppercase; font-weight: 800; color: #7f8c8d; margin-bottom: 8px; }
input, select, textarea { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 8px; font-family: 'Trebuchet MS', sans-serif; font-size: 15px; margin-bottom: 20px; box-sizing: border-box; outline: none; }

.btn-save { width: 100%; background: var(--primary); color: white; border: none; padding: 15px; border-radius: 8px; font-weight: 800; cursor: pointer; transition: 0.3s; font-size: 13px; letter-spacing: 1px; }
.btn-save:hover { background: var(--dark-orange); transform: translateY(-2px); }

.alert-off { background: #fff1f0; border: 1px solid #ffa39e; color: #cf1322; padding: 10px; border-radius: 8px; font-size: 12px; margin-bottom: 15px; font-weight: 600; }
.fas { transition: 0.3s; } .fas:hover { opacity: 0.7; transform: scale(1.1); }
</style>
</head>
<body>

<header>
    <div class="header-left">
        <h1>Zero Hunger Hub</h1>
        <div class="breadcrumb">
            <a href="http://10.175.254.163:3000/adminMenu.php" class="past">Menu</a> 
            <i class="fas fa-chevron-right"></i> 
            <a href="donation.php" class="past">Donation</a> 
            <i class="fas fa-chevron-right"></i> 
            <span class="current">New Dispatch</span>
        </div>
    </div>
    <div class="header-right">
        <div class="module-health">
            <?php foreach($module_status as $name => $status): ?>
                <div class="health-item"><div class="dot <?= $status?'on':'off' ?>"></div> <?= $name ?></div>
            <?php endforeach; ?>
        </div>
        <a href="http://10.175.254.163:3000/userLogOut.php" class="btn-logout">LOGOUT</a>
    </div>
</header>

<div class="main-wrapper">
    <div class="welcome-card">
        <div>
            <h2>New Dispatch</h2>
            <div class="id-pill"><?= htmlspecialchars($display_id) ?> &nbsp;&nbsp;&nbsp; ADMIN</div>
        </div>
    </div>

    <div class="form-card">
        <form method="POST">
            <label>Select Donation ID</label>
            <select name="donation_id" required>
                <option value="" disabled selected>-- Select Pending Request --</option>
                <?php while($don = $donation_list->fetch_assoc()): ?>
                    <option value="<?= $don['donation_id'] ?>">#<?= $don['donation_id'] ?> (Donee: <?= $don['donee_id'] ?>)</option>
                <?php endwhile; ?>
            </select>

            <label>Assigned Volunteer</label>
            <?php if ($anis_online): ?>
                <select name="volunteer_id" required>
                    <option value="" disabled selected>-- Select Dispatcher --</option>
                    <?php foreach ($volunteer_options as $v): ?>
                        <option value="<?= $v['volunteer_id'] ?>"><?= htmlspecialchars($v['name']) ?> (<?= $v['volunteer_id'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <div class="alert-off"><i class="fas fa-exclamation-triangle"></i> Volunteer Module Offline - Manual ID Required</div>
                <input type="text" name="volunteer_id" placeholder="Enter Volunteer ID (e.g. V001)" required>
            <?php endif; ?>

            <div style="display:flex; gap:15px;">
                <div style="flex:1;">
                    <label>Pickup Date</label>
                    <input type="date" name="pickup_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div style="flex:1;">
                    <label>Pickup Time</label>
                    <input type="time" name="pickup_time" required>
                </div>
            </div>

            <label>Dispatcher Instructions / Notes</label>
            <textarea name="notes" rows="3" placeholder="Handling instructions or gate info..."></textarea>

            <button type="submit" class="btn-save" onclick="return confirm('Assign volunteer and update status?')">CONFIRM DISPATCH</button>
        </form>
        <a href="pickup.php" style="display:block; text-align:center; margin-top:20px; color:#95a5a6; text-decoration:none; font-size:13px; font-weight:800;">Cancel</a>
    </div>
</div>

</body>
</html>