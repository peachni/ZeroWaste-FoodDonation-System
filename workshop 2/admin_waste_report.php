<?php
session_start();
include 'dbconnect.php';

/* ===============================
   ADMIN AUTH CHECK
================================ */
if (!isset($_SESSION['admin_username'])) {
    if (
        isset($_GET['role'], $_GET['admin_id'], $_GET['user_name']) &&
        $_GET['role'] === 'admin'
    ) {
        $_SESSION['admin_id'] = (int)$_GET['admin_id'];
        $_SESSION['admin_username'] = $_GET['user_name'];
    } else {
        echo "<script>
            alert('Access denied! Please login as admin.');
            window.location='LoginAdmin.php';
        </script>";
        exit;
    }
}

$admin_username = $_SESSION['admin_username'];

/* ===============================
   FETCH REPORT DATA (POSTGRES)
================================ */
$donor_result = pg_query($conn, "SELECT COUNT(*) AS total_donors FROM donor");
$donee_result = pg_query($conn, "SELECT COUNT(*) AS total_donees FROM donee");

$donor_count = pg_fetch_assoc($donor_result)['total_donors'] ?? 0;
$donee_count = pg_fetch_assoc($donee_result)['total_donees'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Donation Report</title>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body {
    font-family: Arial, sans-serif;
    background: #fdf6e3;
    margin: 0;
}

header {
    background: #f39c12;
    color: #fff;
    padding: 20px;
    text-align: center;
}

.container {
    width: 90%;
    max-width: 900px;
    margin: 40px auto;
}

.card {
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    text-align: center;
}

canvas {
    max-width: 500px;
    margin: 30px auto;
}

.back-btn {
    display: inline-block;
    margin-top: 20px;
    background: #e67e22;
    color: #fff;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
}
.back-btn:hover {
    background: #d35400;
}
</style>
</head>

<body>

<header>
    <h1>📊 Donation & Distribution Report</h1>
    <p>Administrator: <?= htmlspecialchars($admin_username) ?></p>
</header>

<div class="container">
    <div class="card">
        <h2>System Participation Overview</h2>
        <p>This pie chart compares the number of donors and donees in the system.</p>

        <canvas id="donationChart"></canvas>

        <!-- ✅ BACK BUTTON (ADDED ONLY) -->
        <a href="adminMenu.php" class="back-btn">⬅ Back to Admin Menu</a>
    </div>
</div>

<script>
const ctx = document.getElementById('donationChart').getContext('2d');

new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Donors', 'Donees'],
        datasets: [{
            data: [<?= $donor_count ?>, <?= $donee_count ?>],
            backgroundColor: ['#f39c12', '#27ae60'],
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

</body>
</html>
