<?php
session_start();
require_once 'connect.php';
mysqli_report(MYSQLI_REPORT_OFF);

/* =========================
   1. SESSION MANAGEMENT
========================= */
if (isset($_GET['role'])) {
    $_SESSION['role']      = $_GET['role'];
    $_SESSION['user_name'] = $_GET['user_name'] ?? "User";
    $_SESSION['donee_id']  = $_GET['donee_id'] ?? null;
    $_SESSION['admin_id']  = $_GET['admin_id'] ?? null;
}

if (!isset($_SESSION['role'])) {
    header("Location: http://10.175.254.163:3000/userLogin.php");
    exit();
}

$role       = strtolower($_SESSION['role']);
$user_name  = $_SESSION['user_name'];
$display_id = ($role === 'admin') ? $_SESSION['admin_id'] : $_SESSION['donee_id'];
$table_title = ($role === 'admin') ? "Global Logistics Audit" : "Fulfillment Tracking";

/* =========================
   2. SEARCH & FILTER
========================= */
$status_filter = $_GET['status'] ?? 'All';
$search = escape($_GET['search'] ?? '');
$where_clauses = [];
if ($role !== 'admin') {
    $id_to_check = $_SESSION['donee_id'] ?? '';
    $where_clauses[] = !empty($id_to_check) ? "d.donee_id = '$id_to_check'" : "1=0";
}
if ($status_filter !== 'All') { $where_clauses[] = "p.pickup_status = '$status_filter'"; }
if ($search !== '') { $where_clauses[] = "(p.pickup_id LIKE '%$search%' OR p.donation_id LIKE '%$search%' OR p.volunteer_id LIKE '%$search%')"; }
$where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

/* =========================
   3. SUMMARY STATS
========================= */
$stats = array_fill_keys(['All','Pending','Scheduled','Collected','Delivered','Cancelled'], 0);
$count_sql = "SELECT p.pickup_status, COUNT(*) as count 
              FROM pickup p 
              LEFT JOIN donation d ON p.donation_id = d.donation_id 
              $where_sql 
              GROUP BY p.pickup_status";
$count_res = query($count_sql);
while($row = $count_res->fetch_assoc()){
    if(isset($stats[$row['pickup_status']])) $stats[$row['pickup_status']] = $row['count'];
    $stats['All'] += $row['count'];
}

/* =========================
   4. MODULE HEALTH
========================= */
function check_module($ip, $port, $timeout = 0.3) {
    $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    if ($fp) { fclose($fp); return true; }
    return false;
}
$module_status = [
    'User'      => check_module('10.175.254.163', 5432), 
    'Food'      => check_module('10.175.254.152', 3306), 
    'Logistics' => check_module('10.175.254.3', 3307),   
    'Feedback'  => check_module('10.175.254.1', 3306)    
];

/* =========================
   5. EXTERNAL DATA MAPS
========================= */
$food_map = [];
if ($module_status['Food']) {
    $conn_b = @new mysqli("10.175.254.152", "balqis", "Balqis123", "workshop2");
    if (!$conn_b->connect_error) {
        $res_b = $conn_b->query("SELECT FoodList_id, Food_name FROM food_don_list");
        while($f = @$res_b->fetch_assoc()) { $food_map[$f['FoodList_id']] = $f['Food_name']; }
        $conn_b->close();
    }
}

$volunteer_map = [];
$conn_v = @new mysqli("10.175.254.2", "nis", "1234", "volunteer");
if (!$conn_v->connect_error) {
    $qv = $conn_v->query("SELECT volunteer_id, name FROM volunteer");
    while ($v = $qv->fetch_assoc()) { $volunteer_map[$v['volunteer_id']] = $v['name']; }
    $conn_v->close();
}

/* =========================
   6. MAIN QUERY
========================= */
$sql = "SELECT p.*, d.donee_id, d.foodlist_id 
        FROM pickup p 
        LEFT JOIN donation d ON p.donation_id = d.donation_id 
        $where_sql 
        ORDER BY p.pickup_id DESC";
$result = query($sql);

$accent_color = ($role === 'admin') ? "#d35400" : "#f39c12";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Logistics | Zero Hunger</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
:root { --primary: #f39c12; --dark-orange: #a04000; --bg: #fdf6e3; --text-dark: #2c3e50; --shadow: rgba(0,0,0,0.08); }
body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text-dark); }

/* HEADER - Vertically Centered All Contents */
header { background: linear-gradient(90deg, #f39c12, #f1c40f); padding: 20px 40px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 10px var(--shadow); }
.header-left { display: flex; flex-direction: column; gap: 4px; }
header h1 { font-family: 'Segoe UI', sans-serif; color: white; margin: 0; font-size: 1.4rem; font-weight: 800; line-height: 1; }

.breadcrumb { font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; margin-top: 4px; line-height: 1; }
.breadcrumb .past { color: rgba(255,255,255,0.55); text-decoration: none; transition: 0.3s; display: inline-block; } 
.breadcrumb .past:hover { color: #ffffff; opacity: 1; transform: scale(1.05); }
.breadcrumb .current { color: #ffffff; }
.breadcrumb i { font-size: 9px; margin: 0 8px; color: rgba(255,255,255,0.4); display: inline-flex; align-items: center; }

.header-right { display: flex; align-items: center; gap: 20px; }
.module-health { display: flex; gap: 12px; align-items: center; }
.health-item { background: rgba(0,0,0,0.7); color: white; padding: 6px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; display: flex; align-items: center; gap: 6px; transition: 0.3s; cursor: help; }
.health-item:hover { background: rgba(0,0,0,0.9); transform: translateY(-1px); }
.dot { height: 7px; width: 7px; border-radius: 50%; }
.on { background: #2ecc71; box-shadow: 0 0 8px #2ecc71; } .off { background: #e74c3c; }
.btn-logout { background: white; color: #c0392b; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 800; transition: 0.3s; }
.btn-logout:hover { background: #c0392b; color: white; transform: translateY(-2px); }

.main-wrapper { max-width: 1150px; margin: 20px auto; padding: 0 20px; }

/* WELCOME CARD - COMPRESSED */
.welcome-card { background: white; padding: 15px 25px; border-radius: 12px; border-left: 10px solid <?= $accent_color ?>; box-shadow: 0 4px 15px var(--shadow); margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
.welcome-card h2 { font-family: 'Segoe UI', sans-serif; margin: 0; font-size: 18px; font-weight: 700; }
.id-pill { background: #fff3e0; color: #d35400; padding: 4px 15px; border-radius: 50px; font-weight: 800; font-size: 12px; border: 1.5px solid #ffe0b2; display: inline-block; margin-top: 5px; }

/* SUMMARY STATS BAR */
.stats-bar { display: flex; gap: 10px; margin-bottom: 20px; }
.stat-box { background: white; padding: 10px; border-radius: 10px; border-bottom: 4px solid #ddd; flex: 1; text-align: center; box-shadow: 0 2px 8px var(--shadow); transition: 0.3s; }
.stat-box.active-stat { border-bottom-color: var(--primary); }
.stat-label { font-size: 9px; font-weight: 800; color: #95a5a6; text-transform: uppercase; }
.stat-value { font-size: 16px; font-weight: 800; color: var(--text-dark); display: block; }

/* NAVIGATION */
.nav-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.nav-tabs { display: flex; gap: 8px; }
.nav-tabs a { background: white; color: var(--text-dark); padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; box-shadow: 0 2px 5px var(--shadow); text-decoration: none; transition: 0.3s; }
.nav-tabs a:hover { transform: translateY(-2px); filter: brightness(1.1); color: var(--primary); }
.nav-tabs a.active { background: var(--primary); color: white; }
.btn-menu { background: #2c3e50; color: white; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: 0.3s; }
.btn-menu:hover { background: #1a252f; transform: translateY(-2px); }

/* DATA CARD */
.data-card { background: white; border-radius: 12px; box-shadow: 0 8px 30px var(--shadow); overflow: hidden; }
.table-label { font-family: 'Trebuchet MS', sans-serif; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; color: #2c3e50; padding: 25px 25px 5px; display: block; font-weight: 800; }
.table-controls { display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 25px; border-bottom: 1px solid #eee; }
.filter-links a { margin-right: 15px; text-decoration: none; font-size: 11px; font-weight: 800; color: #bdc3c7; transition: 0.3s; }
.filter-links a:hover { color: var(--primary); }
.filter-links a.active-filter { color: var(--primary); border-bottom: 2px solid var(--primary); padding-bottom: 3px; }
.btn-search { background: var(--text-dark); color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: 0.3s; }
.btn-search:hover { background: var(--primary); transform: scale(1.1); }

/* TABLE */
.table-responsive { width: 100%; overflow-x: auto; }
table { width: 100%; min-width: 1000px; border-collapse: collapse; font-family: 'Trebuchet MS', sans-serif; table-layout: fixed; }
thead tr { background: #fdf2e9; } 
th { text-align: left; color: var(--dark-orange); font-size: 15px; font-weight: 800; text-transform: uppercase; padding: 15px; border-bottom: 3px solid #f4d03f; }

/* Fixed column widths */
th:nth-child(1) { width: 100px; } /* Pickup ID */
th:nth-child(2) { width: 110px; } /* Donation ID */
th:nth-child(3) { width: 130px; } /* Volunteer ID */
th:nth-child(4) { width: 170px; } /* Date & Time */
th:nth-child(5) { width: 150px; } /* Status */
th:nth-child(6) { width: 140px; } /* Operation */

td { padding: 15px; border-bottom: 1px solid #f1f1f1; font-size: 15px; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: left; }
tr:hover { background-color: #fffdfa; }

.id-link { color: var(--dark-orange); text-decoration: none; cursor: pointer; border-bottom: 1px dashed var(--dark-orange); transition: 0.2s; }
.id-link:hover { color: var(--primary); }
.status-text { font-weight: 800; color: var(--primary); font-size: 13px; text-transform: uppercase; }

.btn-update { 
    color: var(--dark-orange); text-decoration: none; font-weight: 800; font-size: 11px; 
    border: 2px solid var(--dark-orange); padding: 6px 14px; border-radius: 5px; 
    transition: 0.3s; background: white; display: inline-block; 
}
.btn-update:hover { background: var(--dark-orange); color: white; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(160, 64, 0, 0.2); }

/* MODAL */
.modal-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); display:none; justify-content:center; align-items:center; z-index: 1000; }
.modal-box { background: white; width: 500px; border-radius: 15px; overflow: hidden; animation: slideIn 0.3s ease; }
.modal-header { background: var(--primary); color: white; padding: 20px; font-weight: 800; display: flex; justify-content: space-between; }
.modal-body { padding: 25px; }
.modal-row { display: flex; border-bottom: 1px solid #eee; padding: 10px 0; font-size: 14px; }
.modal-label { width: 140px; font-weight: 800; color: #7f8c8d; text-transform: uppercase; font-size: 11px; }

@keyframes slideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.fas, a, button { transition: 0.3s ease; } .fas:hover { opacity: 0.7; transform: scale(1.1); }
</style>
</head>
<body>

<header>
    <div class="header-left">
        <h1>Zero Hunger Hub</h1>
        <div class="breadcrumb">
            <a href="http://10.175.254.163:3000/<?= $role ?>Menu.php" class="past">Menu</a> <i class="fas fa-chevron-right"></i> <span class="current">Donation</span>
        </div>
    </div>
    <div class="header-right">
        <div class="module-health">
            <?php foreach($module_status as $name => $status): ?>
                <div class="health-item" title="Connection to <?= $name ?> Module">
                    <div class="dot <?= $status?'on':'off' ?>"></div> <?= $name ?>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="http://10.175.254.163:3000/userLogOut.php" class="btn-logout">LOGOUT</a>
    </div>
</header>

<div class="main-wrapper">
    <div class="welcome-card">
        <div>
            <h2>Welcome, <?= htmlspecialchars($user_name) ?></h2>
            <div class="id-pill"><?= htmlspecialchars($display_id) ?> &nbsp;&nbsp;&nbsp; <?= strtoupper($role) ?></div>
        </div>
        <?php if ($role === 'admin'): ?>
            <a href="add_pickup.php" class="btn-menu" style="background:var(--primary); box-shadow: 0 4px 12px rgba(0,0,0,0.1);"><i class="fas fa-calendar-plus"></i> Schedule Pickup</a>
        <?php endif; ?>
    </div>

    <div class="stats-bar">
        <div class="stat-box <?= $status_filter=='All'?'active-stat':'' ?>"><span class="stat-label">Total Dispatch</span><span class="stat-value"><?= $stats['All'] ?></span></div>
        <div class="stat-box <?= $status_filter=='Pending'?'active-stat':'' ?>"><span class="stat-label">Pending</span><span class="stat-value"><?= $stats['Pending'] ?></span></div>
        <div class="stat-box <?= $status_filter=='Scheduled'?'active-stat':'' ?>"><span class="stat-label">Scheduled</span><span class="stat-value"><?= $stats['Scheduled'] ?></span></div>
        <div class="stat-box <?= $status_filter=='Collected'?'active-stat':'' ?>"><span class="stat-label">In Transit</span><span class="stat-value"><?= $stats['Collected'] ?></span></div>
        <div class="stat-box <?= $status_filter=='Delivered'?'active-stat':'' ?>"><span class="stat-label">Delivered</span><span class="stat-value"><?= $stats['Delivered'] ?></span></div>
        <div class="stat-box <?= $status_filter=='Cancelled'?'active-stat':'' ?>"><span class="stat-label">Cancelled</span><span class="stat-value"><?= $stats['Cancelled'] ?></span></div>
    </div>

    <div class="nav-section">
        <div class="nav-tabs">
            <a href="donation.php"><i class="fas fa-receipt"></i> Hub</a>
            <a href="pickup.php" class="active"><i class="fas fa-truck"></i> Logistics</a>
            <?php if ($role === 'admin'): ?><a href="system_backup.php"><i class="fas fa-database"></i> Backup & Recovery</a><?php endif; ?>
        </div>
        <a href="http://10.175.254.163:3000/<?= $role ?>Menu.php" class="btn-menu">Back to Menu</a>
    </div>

    <div class="data-card">
        <span class="table-label"><?= $table_title ?></span>
        <div class="table-controls">
            <div class="filter-links">
                <?php foreach(['All','Pending','Scheduled','Collected','Delivered','Cancelled'] as $f): ?>
                    <a href="?status=<?= $f ?>" class="<?= $status_filter==$f?'active-filter':'' ?>"><?= $f == 'Collected' ? 'IN TRANSIT' : strtoupper($f) ?></a>
                <?php endforeach; ?>
            </div>
            <form class="search-box"><input type="text" name="search" placeholder="Ref or Volunteer..." value="<?= htmlspecialchars($search) ?>"><button type="submit" class="btn-search"><i class="fas fa-search"></i></button></form>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Pickup ID</th><th>Donation ID</th><th>Volunteer ID</th><th>Date & Time</th><th>Status</th><th>Operation</th></tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <!-- Formatted time for the Modal (H:i) -->
                                <a class="id-link" onclick="viewLogistics({
                                    id:'<?= $row['pickup_id'] ?>', 
                                    don:'<?= $row['donation_id'] ?>', 
                                    vol_id:'<?= $row['volunteer_id'] ?? '-' ?>', 
                                    vol_name:'<?= addslashes($volunteer_map[$row['volunteer_id']] ?? 'Unassigned') ?>', 
                                    food:'<?= addslashes($food_map[$row['foodlist_id']] ?? 'Item '.$row['foodlist_id']) ?>', 
                                    date:'<?= date('M d, Y', strtotime($row['pickup_date'])) ?>', 
                                    time:'<?= date('H:i', strtotime($row['pickup_time'])) ?>', 
                                    status:'<?= $row['pickup_status'] ?>'
                                })"><strong>#<?= $row['pickup_id'] ?></strong></a>
                            </td>
                            <td>#<?= $row['donation_id'] ?></td>
                            <td><?= $row['volunteer_id'] ?? 'Unassigned' ?></td>
                            <td>
                                <?= date('M d, Y', strtotime($row['pickup_date'])) ?><br>
                                <!-- Formatted time for the table (H:i) -->
                                <small style="color:#777;"><?= date('H:i', strtotime($row['pickup_time'])) ?></small>
                            </td>
                            <td class="status-text"><?= $row['pickup_status'] ?></td>
                            <td>
                                <?php if ($role === 'admin'): ?>
                                    <a href="edit_pickup.php?id=<?= $row['pickup_id'] ?>" class="btn-update"><i class="fas fa-pen-to-square"></i> UPDATE</a>
                                <?php else: ?>
                                    <i class="fas fa-lock" style="color:#bdc3c7;"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:50px; color:#95a5a6; font-weight:800; font-size: 15px;">No logistics records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="logModal" class="modal-overlay" onclick="this.style.display='none'">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="modal-header"><span>LOGISTICS INFO</span><i class="fas fa-times" style="cursor:pointer" onclick="document.getElementById('logModal').style.display='none'"></i></div>
        <div class="modal-body">
            <div class="modal-row"><div class="modal-label">Pickup ID</div><div id="l-id"></div></div>
            <div class="modal-row"><div class="modal-label">Donation Ref</div><div id="l-don"></div></div>
            <div class="modal-row"><div class="modal-label">Food Item</div><div id="l-food" style="font-weight:bold;"></div></div>
            <div class="modal-row"><div class="modal-label">Volunteer</div><div id="l-vol"></div></div>
            <div class="modal-row"><div class="modal-label">Date/Time</div><div id="l-dt"></div></div>
            <div class="modal-row" style="border:none;"><div class="modal-label">Status</div><div id="l-status" style="font-weight:800; color:var(--primary);"></div></div>
        </div>
    </div>
</div>

<script>
function viewLogistics(d) {
    document.getElementById('l-id').innerText = "#" + d.id;
    document.getElementById('l-don').innerText = "#" + d.don;
    document.getElementById('l-food').innerText = d.food;
    document.getElementById('l-vol').innerText = d.vol_name + " (" + d.vol_id + ")";
    document.getElementById('l-dt').innerText = d.date + " at " + d.time;
    document.getElementById('l-status').innerText = d.status;
    document.getElementById('logModal').style.display = 'flex';
}
</script>
</body>
</html>