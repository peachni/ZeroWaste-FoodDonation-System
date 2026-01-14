<?php
session_start();
require_once 'connect.php';
mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: donation.php");
    exit();
}

$role = strtolower($_SESSION['role']);
$user_name = $_SESSION['user_name'];
$display_id = $_SESSION['admin_id'];

function check_module($ip, $port, $timeout = 0.3) {
    $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    if ($fp) { fclose($fp); return true; }
    return false;
}

$module_status = [
    'User' => check_module('10.175.254.163', 5432),
    'Food' => check_module('10.175.254.152', 3306),
    'Donation' => true,
    'Volunteer' => check_module('10.175.254.2', 3306)
];

$accent_color = "#d35400";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Backup Logs | Zero Hunger</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
:root { --primary:#f39c12; --dark-orange:#a04000; --bg:#fdf6e3; --text-dark:#2c3e50; --shadow:rgba(0,0,0,0.08); }
body { margin:0; padding:0; font-family:'Inter',sans-serif; background-color:var(--bg); color:var(--text-dark); }

/* HEADER */
header { background:linear-gradient(90deg,#f39c12,#f1c40f); padding:20px 40px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 4px 10px var(--shadow); }
.header-left { display:flex; flex-direction:column; gap:4px; }
header h1 { font-family:'Segoe UI',sans-serif; color:white; margin:0; font-size:1.4rem; font-weight:800; line-height:1; }
.breadcrumb { font-size:12px; font-weight:600; display:flex; align-items:center; gap:8px; margin-top:4px; line-height:1; }
.breadcrumb .past { color:rgba(255,255,255,0.55); text-decoration:none; }
.breadcrumb .current { color:#fff; }
.breadcrumb i { font-size:9px; margin:0 8px; color:rgba(255,255,255,0.4); display:inline-flex; align-items:center; }

/* LAYOUT */
.main-wrapper { max-width:1150px; margin:20px auto; padding:0 20px; }

/* WELCOME CARD */
.welcome-card { background:white; padding:15px 25px; border-radius:12px; border-left:10px solid <?= $accent_color ?>; box-shadow:0 4px 15px var(--shadow); margin-bottom:15px; display:flex; justify-content:space-between; align-items:center; }
.welcome-card h2 { font-family:'Segoe UI',sans-serif; margin:0; font-size:18px; font-weight:700; }

/* NAV */
.nav-section { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.nav-tabs { display:flex; gap:8px; }
.nav-tabs a { background:white; color:var(--text-dark); padding:10px 18px; border-radius:8px; font-size:13px; font-weight:700; box-shadow:0 2px 5px var(--shadow); text-decoration:none; transition:0.3s; }
.nav-tabs a:hover { transform: translateY(-2px); color: var(--primary); }
.nav-tabs a.active { background:var(--primary); color:white; }
.btn-menu { background:#2c3e50; color:white; padding:10px 20px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:0.3s; }
.btn-menu:hover { background: #1a252f; transform: translateY(-2px); }

/* TABLE */
.data-card { background: white; border-radius: 12px; box-shadow: 0 8px 30px var(--shadow); overflow: hidden; }
.table-label { font-family: 'Trebuchet MS', sans-serif; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; color: #2c3e50; padding: 25px 25px 5px; display: block; font-weight: 800; }
table { width: 100%; border-collapse: collapse; font-family: 'Trebuchet MS', sans-serif;}
thead tr { background: #fdf2e9;}
th { text-align: left; color: var(--dark-orange); font-size: 15px; font-weight: 800; text-transform: uppercase; padding: 15px; border-bottom: 3px solid #f4d03f;}
td { padding: 15px; border-bottom: 1px solid #f1f1f1;font-size: 15px;color: #333;}
tr:hover {background-color: #fffdfa;}

/* TOAST NOTIFICATIONS */
.toast-msg { position: fixed; top: 20px; right: 20px; padding: 16px 22px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.25); font-weight: 700; font-size: 14px; z-index: 9999; display: flex; align-items: center; gap: 12px; animation: slideDown 0.3s ease; }
.toast-msg.success { background: #27ae60; color: white; } 
.toast-msg.error   { background: #e74c3c; color: white; } 
.toast-msg .close-btn { font-size: 18px; background:transparent; border:none; color:white; cursor:pointer; }

@keyframes slideDown { from { opacity:0; transform: translateY(-20px); } to { opacity:1; transform: translateY(0); } }
.toast-hide { opacity: 0 !important; transform: translateY(-20px) !important; transition: 0.5s; }
</style>
</head>

<body>

<header>
    <div class="header-left">
        <h1>Zero Hunger Hub</h1>
        <div class="breadcrumb">
            <a href="http://10.175.254.163:3000/adminMenu.php" class="past">Menu</a>
            <i class="fas fa-chevron-right"></i>
            <span class="current">Backup Logs</span>
        </div>
    </div>
    <div class="header-right"></div>
</header>

<div class="main-wrapper">

    <div class="welcome-card">
        <div>
            <h2>Database Maintenance Logs</h2>
        </div>
    </div>

    <div class="nav-section">
        <div class="nav-tabs">
            <a href="donation.php"><i class="fas fa-receipt"></i> Donation</a>
            <a href="pickup.php"><i class="fas fa-truck"></i> Delivery</a>
            <a href="system_backup.php" class="active"><i class="fas fa-database"></i> Backup Logs</a>
        </div>
        <a href="http://10.175.254.163:3000/adminMenu.php" class="btn-menu">Back to Menu</a>
    </div>

    <div class="data-card">
        <span class="table-label">Archived System Snapshots</span>
        <table>
            <thead>
                <tr>
                    <th>Backup Filename</th>
                    <th>Timestamp</th>
                    <th>Storage Type</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $backup_folder = "C:/xampp/htdocs/mariadb_test/Backups/";
            if (is_dir($backup_folder)) {
                $files = glob($backup_folder . "*.sql");
                rsort($files); 

                if (count($files) > 0) {
                    foreach ($files as $f):
                        $basename = basename($f);
                        $type = preg_match('/^ZeroHunger_\d{4}/', $basename) ? 'Automated' : 'Manual Audit';
            ?>
                <tr>
                    <td><i class="far fa-file-code" style="color:var(--primary); margin-right:8px;"></i> <?= htmlspecialchars($basename) ?></td>
                    <td><?= date("M d, Y - H:i", filemtime($f)) ?></td>
                    <td><span style="font-weight:800; font-size:12px; color:#7f8c8d;"><?= $type ?></span></td>
                </tr>
            <?php 
                    endforeach;
                } else {
                    echo '<tr><td colspan="3" style="text-align:center; padding:40px; color:#999;">No backup files detected in the repository.</td></tr>';
                }
            } else {
                echo '<tr><td colspan="3" style="text-align:center; padding:40px; color:#e74c3c;">Backup directory not found on local server.</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>

</div>

<?php if (isset($_GET['msg'])): ?>
<div class="toast-msg <?= $_GET['msg']==='restored' || $_GET['msg']==='saved' ?'success':'error' ?>" id="toast">
    <i class="fas <?= $_GET['msg']==='restored' || $_GET['msg']==='saved' ?'fa-circle-check':'fa-triangle-exclamation' ?>"></i>
    <?php
        if ($_GET['msg'] === 'saved') echo "System snapshot archived successfully.";
        elseif ($_GET['msg'] === 'restored') echo "Database state restored successfully.";
        else echo "System operation error.";
    ?>
    <button class="close-btn" onclick="closeToast()">×</button>
</div>

<script>
function closeToast() {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.classList.add('toast-hide');
    setTimeout(() => toast.remove(), 500);
}
setTimeout(closeToast, 8000);
</script>
<?php endif; ?>

</body>
</html>