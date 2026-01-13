<?php
// admin_quality_report.php
session_start();
mysqli_report(MYSQLI_REPORT_OFF);
include 'db_connect.php'; // Your local MySQL

// Syakur's Admin Dashboard Address
$admin_dashboard_url = "http://10.175.254.163:3000/adminMenu.php";

// Balqis's Connection Details (Module 2)
$balqis_ip = "10.175.254.152";
$balqis_user = "balqis";
$balqis_pass = "Balqis123";
$balqis_db = "workshop2";

$balqis_conn = @new mysqli($balqis_ip, $balqis_user, $balqis_pass, $balqis_db);

/**
 * 1. FETCH SUMMARY STATISTICS FROM YOUR DB
 */
$total_query = $conn->query("SELECT COUNT(*) as total FROM feedback");
$total_feedback = ($total_query) ? $total_query->fetch_assoc()['total'] : 0;

$status_counts = $conn->query("SELECT Quality_Status, COUNT(*) as count FROM feedback GROUP BY Quality_Status");
$stats = ['Fresh' => 0, 'Near Expiration' => 0, 'Damaged' => 0, 'Expired' => 0];
while($row = $status_counts->fetch_assoc()) {
    $stats[$row['Quality_Status']] = $row['count'];
}

// Calculate Safety Percentage (Fresh + Near Expiry)
$safe_count = $stats['Fresh'] + $stats['Near Expiration'];
$safety_score = ($total_feedback > 0) ? round(($safe_count / $total_feedback) * 100) : 0;

/**
 * 2. HETEROGENEOUS LOOKUP: Get food details by combining both DBs
 */
$report_data = [];
$feedback_query = $conn->query("SELECT FoodList_ID, Quality_Status, Food_Quality_Rating, Comments FROM feedback");

if ($feedback_query) {
    while($f = $feedback_query->fetch_assoc()) {
        $food_name = "Unknown Item";
        
        // Lookup name from Balqis's Laptop via ZeroTier
        if (!$balqis_conn->connect_error) {
            $id = $f['FoodList_ID'];
            $b_res = $balqis_conn->query("SELECT Food_Name FROM food_don_list WHERE FoodList_ID = '$id' LIMIT 1");
            if ($b_res && $row = $b_res->fetch_assoc()) {
                $food_name = $row['Food_Name'];
            }
        }
        
        $report_data[] = [
            'name' => $food_name,
            'status' => $f['Quality_Status'],
            'rating' => $f['Food_Quality_Rating'],
            'comment' => $f['Comments']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quality Compliance Report - ZeroWaste</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7f6; padding: 40px; margin: 0; }
        
        /* Relative container to allow absolute positioning of the back button */
        .container { max-width: 1000px; margin: auto; position: relative; padding-top: 40px; }
        
        /* TOP LEFT BACK LINK */
        .back-nav { 
            position: absolute; 
            top: 0; 
            left: 0; 
            color: #95a5a6; 
            text-decoration: none; 
            font-size: 13px; 
            font-weight: 600; 
            transition: 0.2s;
        }
        .back-nav:hover { color: #e67e22; }

        /* Typography and Grid */
        .score-box { background: #2c3e50; color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; border-left: 8px solid #e67e22; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center; border-bottom: 5px solid #e67e22; }
        .stat-card h3 { margin: 0; font-size: 12px; color: #7f8c8d; text-transform: uppercase; letter-spacing: 1px; }
        .stat-card p { margin: 10px 0 0 0; font-size: 30px; font-weight: bold; color: #2c3e50; }
        
        /* Table Styling */
        .main-table { width: 100%; background: white; border-radius: 12px; border-collapse: collapse; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .main-table th { background: #e67e22; color: white; padding: 18px; text-align: left; font-size: 14px; }
        .main-table td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; color: #34495e; }
        
        /* Status Badges */
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .Fresh { background: #e8f5e9; color: #2e7d32; }
        .NearExpiration { background: #fff9c4; color: #f57f17; }
        .Damaged { background: #ffebee; color: #c62828; }
        .Expired { background: #f5f5f5; color: #616161; }
        
        h2 { color: #2c3e50; margin-bottom: 20px; font-size: 22px; }
    </style>
</head>
<body>

<div class="container">
    <!-- TOP LEFT NAVIGATION LINK -->
    <a href="<?php echo $admin_dashboard_url; ?>" class="back-nav">← Back to Dashboard</a>

    <div class="score-box">
        <div>
            <h1 style="margin:0;">Food Quality Compliance</h1>
            <p style="opacity: 0.7; margin: 5px 0 0 0;">Heterogeneous System Audit: Module 2 (Food) + Module 4 (Feedback)</p>
        </div>
        <div style="text-align:right;">
            <span style="font-size: 13px; display:block; text-transform: uppercase; letter-spacing: 1px;">Overall Safety Score</span>
            <span style="font-size: 54px; font-weight:bold; color:#e67e22;"><?= $safety_score; ?>%</span>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="stat-card"><h3>Fresh Arrival</h3><p><?= $stats['Fresh']; ?></p></div>
        <div class="stat-card"><h3>Near Expiry</h3><p><?= $stats['Near Expiration']; ?></p></div>
        <div class="stat-card" style="border-color:#e74c3c;"><h3>Damaged</h3><p><?= $stats['Damaged']; ?></p></div>
        <div class="stat-card" style="border-color:#95a5a6;"><h3>Expired</h3><p><?= $stats['Expired']; ?></p></div>
    </div>

    <h2>Detailed Quality Audit</h2>
    <table class="main-table">
        <thead>
            <tr>
                <th width="30%">Food Item</th>
                <th width="20%">Condition Status</th>
                <th width="15%">Star Rating</th>
                <th width="35%">Donee Feedback Comment</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($report_data)): ?>
                <tr><td colspan="4" style="text-align:center; padding: 40px; color: #95a5a6;">No feedback records found to generate analysis.</td></tr>
            <?php else: ?>
                <?php foreach($report_data as $row): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['name']); ?></strong></td>
                    <td>
                        <span class="status-pill <?= str_replace(' ', '', $row['status']); ?>">
                            <?= $row['status']; ?>
                        </span>
                    </td>
                    <td style="color:#f1c40f; font-size: 16px; letter-spacing: 2px;">
                        <?= str_repeat("★", $row['rating']); ?>
                    </td>
                    <td style="font-style:italic; color:#7f8c8d;">
                        "<?= htmlspecialchars($row['comment']); ?>"
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>