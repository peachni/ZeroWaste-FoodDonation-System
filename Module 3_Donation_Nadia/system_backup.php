<?php
session_start();
require_once 'connect.php';

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
    'User' => check_module('10.175.254.163', 3000),
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
:root { --primary:#f39c12; --dark-orange:#a04000; --bg:#fdf6e3; --text-dark:#2c3e50; --shadow:rgba(0,0,0,0.08); }
body { margin:0; padding:0; font-family:'Inter',sans-serif; background-color:var(--bg); color:var(--text-dark); }

/* ===== HEADER ===== */
header { background:linear-gradient(90deg,#f39c12,#f1c40f); padding:20px 40px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 4px 10px var(--shadow); }
.header-left { display:flex; flex-direction:column; gap:4px; }
header h1 { font-family:'Segoe UI',sans-serif; color:white; margin:0; font-size:1.4rem; font-weight:800; line-height:1; }
.breadcrumb { font-size:12px; font-weight:600; display:flex; align-items:center; gap:8px; margin-top:4px; line-height:1; }
.breadcrumb .past { color:rgba(255,255,255,0.55); text-decoration:none; }
.breadcrumb .current { color:#fff; }
.breadcrumb i { font-size:9px; margin:0 8px; color:rgba(255,255,255,0.4); display:inline-flex; align-items:center; }
.header-right { display:flex; align-items:center; gap:20px; }
.module-health { display:flex; gap:12px; align-items:center; }
.health-item { background:rgba(0,0,0,0.7); color:white; padding:6px 12px; border-radius:20px; font-size:10px; font-weight:700; display:flex; align-items:center; gap:6px; }
.dot { height:7px; width:7px; border-radius:50%; }
.on { background:#2ecc71; box-shadow:0 0 8px #2ecc71; }
.off { background:#e74c3c; }
.btn-logout { background:white; color:#c0392b; padding:8px 16px; text-decoration:none; border-radius:6px; font-size:12px; font-weight:800; }

/* ===== LAYOUT ===== */
.main-wrapper { max-width:1150px; margin:20px auto; padding:0 20px; }

/* ===== WELCOME CARD ===== */
.welcome-card { background:white; padding:15px 25px; border-radius:12px; border-left:10px solid <?= $accent_color ?>; box-shadow:0 4px 15px var(--shadow); margin-bottom:15px; display:flex; justify-content:space-between; align-items:center; }
.welcome-card h2 { font-family:'Segoe UI',sans-serif; margin:0; font-size:18px; font-weight:700; }
.id-pill { background:#fff3e0; color:#d35400; padding:4px 15px; border-radius:50px; font-weight:800; font-size:12px; border:1.5px solid #ffe0b2; display:inline-block; margin-top:5px; }

/* ===== NAV ===== */
.nav-section { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.nav-tabs { display:flex; gap:8px; }
.nav-tabs a { background:white; color:var(--text-dark); padding:10px 18px; border-radius:8px; font-size:13px; font-weight:700; box-shadow:0 2px 5px var(--shadow); text-decoration:none; }
.nav-tabs a.active { background:var(--primary); color:white; }
.btn-menu { background:#2c3e50; color:white; padding:10px 20px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; }

/* ===== BACKUP STRIP ===== */
.backup-strip { background:white; padding:15px 25px; border-radius:12px; box-shadow:0 4px 15px var(--shadow); margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; }
.strip-title { font-weight:800; font-size:14px; display:flex; align-items:center; gap:8px; }
.strip-actions { display:flex; align-items:center; gap:12px; }
.btn-strip { padding:6px 12px; font-size:12px; font-weight:800; border-radius:6px; text-decoration:none; border:none; cursor:pointer; }
.primary { background:var(--primary); color:white; }
.danger { background:#2c3e50; color:white; }

/* ===== TABLE ===== */
.data-card { background: white; border-radius: 12px; box-shadow: 0 8px 30px var(--shadow); overflow: hidden; }
.table-label { font-family: 'Trebuchet MS', sans-serif; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; color: #2c3e50; padding: 25px 25px 5px; display: block; font-weight: 800; }
table { width: 100%; border-collapse: collapse; font-family: 'Trebuchet MS', sans-serif;}
thead tr { background: #fdf2e9;}
th { text-align: left; color: var(--dark-orange); font-size: 15px; font-weight: 800; text-transform: uppercase; padding: 15px; border-bottom: 3px solid #f4d03f;}
td { padding: 15px; border-bottom: 1px solid #f1f1f1;font-size: 15px;color: #333;}
tr:hover {background-color: #fffdfa;}

/* ===== TOAST NOTIFICATIONS ===== */
.toast-msg {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 16px 22px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.25);
    font-weight: 700;
    font-size: 14px;
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideDown 0.3s ease;
    opacity: 1;
    transition: opacity 0.5s ease, transform 0.5s ease;
}
.toast-msg.success { background: #27ae60; color: white; } 
.toast-msg.error   { background: #e74c3c; color: white; } 
.toast-msg .close-btn { font-size: 18px; background:transparent; border:none; color:white; cursor:pointer; line-height:1; }

/* Slide down animation for appearing */
@keyframes slideDown {
    from { opacity:0; transform: translateY(-20px); }
    to { opacity:1; transform: translateY(0); }
}

/* Slide up animation for disappearing */
.toast-hide {
    opacity: 0 !important;
    transform: translateY(-20px) !important;
}
</style>
</head>

<body>

<header>
    <div class="header-left">
        <h1>Zero Hunger Hub</h1>
        <div class="breadcrumb">
            <a href="http://10.175.254.163:3000/adminMenu.php" class="past">Menu</a>
            <i class="fas fa-chevron-right"></i>
            <span class="current">Backup & Recovery</span>
        </div>
    </div>
    <div class="header-right">
        <div class="module-health">
            <?php foreach($module_status as $n=>$s): ?>
                <div class="health-item"><div class="dot <?= $s?'on':'off' ?>"></div><?= $n ?></div>
            <?php endforeach; ?>
        </div>
        <a href="http://10.175.254.163:3000/userLogOut.php" class="btn-logout">LOGOUT</a>
    </div>
</header>

<div class="main-wrapper">

<div class="welcome-card">
    <div>
        <h2>Database Backup & Recovery </h2>
        <div class="id-pill"><?= htmlspecialchars($display_id) ?> &nbsp;&nbsp;&nbsp; ADMIN</div>
    </div>
</div>

<div class="nav-section">
    <div class="nav-tabs">
        <a href="donation.php"><i class="fas fa-receipt"></i> Records Hub</a>
        <a href="pickup.php"><i class="fas fa-truck"></i> Logistics</a>
        <a href="system_backup.php" class="active"><i class="fas fa-database"></i> Backup & Recovery</a>
    </div>
    <a href="http://10.175.254.163:3000/adminMenu.php" class="btn-menu">Back to Menu</a>
</div>

<div class="backup-strip">
    <div class="strip-title"><i class="fas fa-database"></i> Database Controls</div>
    <div class="strip-actions">
        <a href="process_backup.php" class="btn-strip primary" onclick="return confirm('Generate system backup?')">
            <i class="fas fa-download"></i> Backup
        </a>
        <form action="process_restore.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="backup_file" accept=".sql" required>
            <button class="btn-strip danger" onclick="return confirm('Overwrite database?')">
                <i class="fas fa-rotate-left"></i> Restore
            </button>
        </form>
    </div>
</div>

<div class="data-card">
    <span class="table-label">Backup History</span>
    <table>
        <thead>
            <tr>
                <th>File</th>
                <th>Date</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $backup_folder = "C:/xampp/htdocs/mariadb_test/Backups/";
        $files = glob($backup_folder . "*.sql");
        rsort($files); // newest first

        foreach ($files as $f):
            $basename = basename($f);

            // Determine if backup is Auto or Manual
            // Auto backups start with "ZeroHunger_" and contain a timestamp
            $type = preg_match('/^ZeroHunger_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}\.sql$/', $basename)
                    ? 'Auto'
                    : 'Manual';
        ?>
            <tr>
                <td><?= htmlspecialchars($basename) ?></td>
                <td><?= date("M d, Y H:i:s", filemtime($f)) ?></td>
                <td><?= $type ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</div>

<?php if (isset($_GET['msg'])): ?>
<div class="toast-msg <?= $_GET['msg']==='saved'?'success':'error' ?>" id="toast">
    <i class="fas <?= $_GET['msg']==='saved'?'fa-circle-check':'fa-triangle-exclamation' ?>"></i>
    <?php
        if ($_GET['msg'] === 'saved') echo "Backup created successfully.";
        elseif ($_GET['msg'] === 'backup_failed') echo "Backup failed: " . htmlspecialchars($_GET['reason'] ?? 'Unknown error');
        elseif ($_GET['msg'] === 'restored') echo "Database restored successfully.";
        else echo "Operation failed.";
    ?>
    <button class="close-btn" onclick="closeToast()">×</button>
</div>

<script>
// Close toast with smooth hide
function closeToast() {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.classList.add('toast-hide');
    setTimeout(() => toast.remove(), 500);
}
// Auto-hide after 8 seconds
setTimeout(closeToast, 8000);
</script>
<?php endif; ?>

</body>
</html>
