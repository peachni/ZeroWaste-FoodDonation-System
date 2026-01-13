<?php
mysqli_report(MYSQLI_REPORT_OFF); 
include "DB.php"; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    die("Access Denied.");
}

/**
 * FETCH DATA FOR REPORT
 */

// A. Successfully Consumed (Sum of Qty_Consumed from main list)
$q_consumed = $conn->query("SELECT SUM(Qty_Consumed) as total FROM food_don_list");
$consumed = ($q_consumed) ? $q_consumed->fetch_assoc()['total'] : 0;

// B. Damaged/Expired (Sum of Quantity_Waste from waste table)
$q_wasted = $conn->query("SELECT SUM(Quantity_Waste) as total FROM foodwaste");
$wasted = ($q_wasted) ? $q_wasted->fetch_assoc()['total'] : 0;

$total = ($consumed + $wasted) ?: 1; // Avoid division by zero
$consumed_pct = ($consumed / $total) * 100;
$wasted_pct   = ($wasted / $total) * 100;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Consumption Report</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background-color: #f4f7f6; }
        .container { width: 95%; max-width: 800px; margin: 50px auto; background: #fdf6e3; padding: 40px; border-radius: 15px; box-shadow: 0px 10px 30px rgba(0,0,0,0.1); }
        .stats-grid { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-card { flex: 1; padding: 25px; border-radius: 10px; text-align: center; color: white; }
        .bg-success { background-color: #27ae60; }
        .bg-danger { background-color: #e74c3c; }
        .val { font-size: 3.5em; font-weight: bold; display: block; }
        .progress-container { height: 40px; background: #eee; border-radius: 20px; overflow: hidden; display: flex; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h2 style="color: #2c3e50;">📊 Impact Analysis Report</h2>
    <p>• <b>What it shows:</b> Total quantity of food successfully consumed vs. food reported as "Damaged/Expired."</p>

    <div class="stats-grid">
        <div class="stat-card bg-success">
            <span class="val"><?= number_format($consumed) ?></span>
            Successfully Consumed
        </div>
        <div class="stat-card bg-danger">
            <span class="val"><?= number_format($wasted) ?></span>
            Damaged / Expired
        </div>
    </div>

    <h4>Ratio Distribution</h4>
    <div class="progress-container">
        <div style="width: <?= $consumed_pct ?>%; background: #27ae60;"></div>
        <div style="width: <?= $wasted_pct ?>%; background: #e74c3c;"></div>
    </div>
    
    <div style="display: flex; justify-content: space-between; margin-top: 10px; font-weight: bold;">
        <span style="color: #27ae60;">Consumed (<?= round($consumed_pct) ?>%)</span>
        <span style="color: #e74c3c;">Waste (<?= round($wasted_pct) ?>%)</span>
    </div>
    
    <a href="waste.php" style="display:inline-block; margin-top:30px; color:#f39c12; font-weight:bold; text-decoration:none;">⬅ Back to Waste Logs</a>
</div>
</body>
</html>