<?php
session_start();
include 'dbconnect.php';

/* ===============================
   SESSION FALLBACK
================================ */
if (!isset($_SESSION['admin_username'])) {
    if (
        isset($_GET['role'], $_GET['admin_id'], $_GET['user_name']) &&
        $_GET['role'] === 'admin'
    ) {
        $_SESSION['admin_id'] = (int)$_GET['admin_id'];
        $_SESSION['admin_username'] = $_GET['user_name'];
    } else {
        header("Location: LoginAdmin.php");
        exit;
    }
}

$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

/* ===============================
   DATA (UNCHANGED)
================================ */
$donors = pg_query($conn, "SELECT * FROM donor ORDER BY donor_id DESC");
$donees = pg_query($conn, "SELECT * FROM donee ORDER BY donee_id DESC");

/* COUNTS (SAFE) */
$donor_count = pg_num_rows($donors);
$donee_count = pg_num_rows($donees);
$donation_count = 0;
$waste_count = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | Zero Hunger</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
* { box-sizing:border-box;margin:0;padding:0;font-family:'Roboto',sans-serif; }

body {
    display:flex;
    min-height:100vh;
    background:#fffaf0;
}

/* SIDEBAR */
.sidebar {
    width:260px;
    background:linear-gradient(180deg,#f39c12,#f1c40f);
    padding:25px;
    color:#fff;
}

.sidebar h2 {
    text-align:center;
    margin-bottom:30px;
}

.menu-title {
    padding:12px;
    background:rgba(255,255,255,0.25);
    border-radius:8px;
    cursor:pointer;
    margin-bottom:8px;
    font-weight:600;
}

.menu-items {
    display:none;
    margin-bottom:15px;
}

.menu-items a {
    display:block;
    padding:10px;
    background:rgba(255,255,255,0.35);
    margin-top:6px;
    border-radius:6px;
    text-decoration:none;
    color:#000;
    font-size:14px;
}

.menu-items a:hover {
    background:#fff;
}

.logout {
    display:block;
    margin-top:30px;
    background:#e74c3c;
    color:#fff;
    text-align:center;
    padding:12px;
    border-radius:8px;
    text-decoration:none;
}

/* MAIN */
.main {
    flex:1;
    padding:30px;
}

.topbar {
    margin-bottom:25px;
}

.section {
    background:#fff;
    padding:25px;
    border-radius:14px;
    margin-bottom:30px;
    box-shadow:0 8px 20px rgba(0,0,0,0.1);
}

.hidden { display:none; }

/* STATS */
.stats {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:20px;
}

.stat-box {
    background:#fff3d6;
    padding:20px;
    border-radius:14px;
}

.stat-box h4 {
    font-weight:500;
}

.stat-box p {
    font-size:32px;
    font-weight:700;
    color:#e67e22;
}

/* TABLE */
table {
    width:100%;
    border-collapse:collapse;
    margin-top:15px;
}

th, td {
    padding:12px;
    border-bottom:1px solid #ddd;
    font-size:14px;
}

th {
    background:#f39c12;
    color:#fff;
    text-align:left;
}

tr:hover {
    background:#fff6e0;
}

.btn-danger {
    background:#e74c3c;
    color:#fff;
    padding:6px 12px;
    border-radius:6px;
    text-decoration:none;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>🌾 Zero Hunger</h2>

    <div class="menu-title" onclick="toggleMenu(this)">🎁 Donation & Waste</div>
    <div class="menu-items">
        <a href="http://10.175.254.3/mariadb_test/donation.php?role=admin&admin_id=<?= $admin_id ?>&user_name=<?= urlencode($admin_username) ?>">View Donation</a>
        <a href="http://10.175.254.152:3000/waste.php?role=admin&admin_id=<?= $admin_id ?>&user_name=<?= urlencode($admin_username) ?>">View Waste</a>
    </div>

    <div class="menu-title" onclick="toggleMenu(this)">🗂️ System Management</div>
    <div class="menu-items">
        <a href="http://10.175.254.152:3000/ManageCat.php?role=admin&admin_id=<?= $admin_id ?>&user_name=<?= urlencode($admin_username) ?>">Manage Category</a>
        <a href="http://10.175.254.2/workshop2/volunteer/volunteer.php?role=admin&admin_id=<?= $admin_id ?>&user_name=<?= urlencode($admin_username) ?>">Manage Volunteer</a>
        <a href="http://10.175.254.1/feedback_module/FEEDBACK_FORM/admin_feedback_manager.php?role=admin&admin_id=<?= $admin_id ?>&user_name=<?= urlencode($admin_username) ?>">Manage Feedback</a>
    </div>

    <div class="menu-title" onclick="toggleMenu(this)">📊 Report Section</div>
    <div class="menu-items">
        <a href="admin_waste_report.php">User Analytics</a>
        <a href="http://10.175.254.152:3000/foodRecord.php?role=admin&admin_id=<?= $admin_id ?>&user_name=<?= urlencode($admin_username) ?>">Food Report</a>
        <a href="http://10.175.254.1/feedback_module/FEEDBACK_FORM/admin_quality_report.php?role=admin&admin_id=<?= $admin_id ?>&user_name=<?= urlencode($admin_username) ?>">Quality Report</a>
    </div>

    <div class="menu-title" onclick="toggleMenu(this)">📋 User Records</div>
    <div class="menu-items">
        <a href="#" onclick="showTable('donor')">Show Donors</a>
        <a href="#" onclick="showTable('donee')">Show Donees</a>
    </div>

    <a href="adminLogOut.php" class="logout">🚪 Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">
    <h2>Welcome, <?= htmlspecialchars($admin_username); ?> 👋</h2>
</div>

<!-- DASHBOARD -->
<div class="section">
    <h3>📊 System Overview</h3>
    <div class="stats">
        <div class="stat-box"><h4>👥 Donors</h4><p><?= $donor_count ?></p></div>
        <div class="stat-box"><h4>🙋 Donees</h4><p><?= $donee_count ?></p></div>

    </div>
</div>

<!-- CHART -->
<div class="section">
    <h3>📈 User Distribution</h3>
    <canvas id="chart" height="120"></canvas>
</div>

<!-- DONOR TABLE -->
<div class="section hidden" id="donor-table">
<h3>Donor List</h3>
<table>
<tr>
<th>ID</th><th>Name</th><th>Username</th><th>Contact</th><th>Address</th><th>Type</th><th>Company</th><th>Action</th>
</tr>
<?php while ($row = pg_fetch_assoc($donors)) { ?>
<tr>
<td><?= $row['donor_id']; ?></td>
<td><?= htmlspecialchars($row['donor_name']); ?></td>
<td><?= htmlspecialchars($row['donor_username']); ?></td>
<td><?= htmlspecialchars($row['contact_number'] ?? 'N/A'); ?></td>
<td><?= htmlspecialchars(trim(($row['address'] ?? '').', '.($row['city'] ?? '')) ?: 'N/A'); ?></td>
<td><?= htmlspecialchars($row['donor_type'] ?? 'N/A'); ?></td>
<td><?= $row['company_name'] ? htmlspecialchars($row['company_name']) : '<em>Individual</em>'; ?></td>
<td>
<a class="btn-danger"
href="adminDelete.php?type=donor&id=<?= $row['donor_id']; ?>"
onclick="return confirm('Delete this donor?');">Delete</a>
</td>
</tr>
<?php } ?>
</table>
</div>

<!-- DONEE TABLE -->
<div class="section hidden" id="donee-table">
<h3>Donee List</h3>
<table>
<tr>
<th>ID</th><th>Name</th><th>Username</th><th>Contact</th><th>Address</th><th>Action</th>
</tr>
<?php while ($row = pg_fetch_assoc($donees)) { ?>
<tr>
<td><?= $row['donee_id']; ?></td>
<td><?= htmlspecialchars($row['donee_name']); ?></td>
<td><?= htmlspecialchars($row['donee_username']); ?></td>
<td><?= htmlspecialchars($row['contact_number'] ?? 'N/A'); ?></td>
<td><?= htmlspecialchars(trim(($row['address'] ?? '').', '.($row['city'] ?? '')) ?: 'N/A'); ?></td>
<td>
<a class="btn-danger"
href="adminDelete.php?type=donee&id=<?= $row['donee_id']; ?>"
onclick="return confirm('Delete this donee?');">Delete</a>
</td>
</tr>
<?php } ?>
</table>
</div>

</div>

<script>
function toggleMenu(el){
    const menu = el.nextElementSibling;
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

function showTable(type){
    document.getElementById('donor-table').classList.add('hidden');
    document.getElementById('donee-table').classList.add('hidden');
    document.getElementById(type+'-table').classList.remove('hidden');
}

new Chart(document.getElementById('chart'), {
    type: 'bar',
    data: {
        labels: ['Donors','Donees'],
        datasets: [{
            data: [<?= $donor_count ?>, <?= $donee_count ?>],
            backgroundColor: ['#f39c12','#f1c40f']
        }]
    },
    options: { plugins:{ legend:{ display:false } } }
});
</script>

</body>
</html>
