<?php
// 1. SYSTEM SETTINGS
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); 
include('DB.php'); 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/**
 * 2. SESSION & HANDSHAKE
 */
if (isset($_GET['role'])) {
    $_SESSION['role']      = $_GET['role'];
    $_SESSION['user_name'] = $_GET['user_name'] ?? "User";
    $_SESSION['admin_id']  = $_GET['admin_id'] ?? null;
}

if (!isset($_SESSION['role'])) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h2>🔒 Access Denied</h2><p>Please login via the Dashboard.</p></div>");
}

$admin_id = $_SESSION['admin_id'] ?? 1; 
$current_user = $_SESSION['user_name'];
$noty_script = ""; 

/**
 * 3. AUTO-DETECT EXPIRED ITEMS (MOVE THEN DELETE)
 */
$today = date("Y-m-d");
$expiredItems = $conn->query("SELECT * FROM food_don_list WHERE Expiry_Date < '$today' AND Quantity > 0");

if ($expiredItems && $expiredItems->num_rows > 0) {
    $detectedCount = 0;
    while ($item = $expiredItems->fetch_assoc()) {
        $fID = $item['FoodList_ID'];
        $qty = $item['Quantity'];
        
        $resMax = $conn->query("SELECT MAX(WasteID) as mid FROM foodwaste");
        $mw = $resMax->fetch_assoc();
        $wID = "W" . str_pad((($mw['mid']) ? (int)substr($mw['mid'], 1) + 1 : 1), 3, "0", STR_PAD_LEFT);

        // Prepare Insert
        $stmt = $conn->prepare("INSERT INTO foodwaste (WasteID, FoodList_ID, Quantity_Waste, Status, Date, Time, Admin_ID) VALUES (?, ?, ?, 'Expired Stock', CURDATE(), CURTIME(), ?)");
        $stmt->bind_param("ssii", $wID, $fID, $qty, $admin_id);
        
        if($stmt->execute()){
            // AFTER successful insert, DELETE FROM SOURCE
            $conn->query("DELETE FROM food_don_list WHERE FoodList_ID = '$fID'");
            $detectedCount++;
        }
        $stmt->close();
    }
    if($detectedCount > 0) {
        $noty_script .= "new Noty({type: 'warning', text: '⚠️ $detectedCount expired items moved to waste and removed from active list.', timeout: 4000, theme: 'metroui'}).show();";
    }
}

/**
 * 4. DELETE SELECTED LOGIC
 */
if (isset($_POST['delete_selected_trigger']) && !empty($_POST['delete_id'])) {
    $ids = array_map(function($id) use ($conn) { return "'" . $conn->real_escape_string($id) . "'"; }, $_POST['delete_id']);
    $conn->query("DELETE FROM foodwaste WHERE WasteID IN (" . implode(',', $ids) . ")");
    $noty_script .= "new Noty({type: 'error', text: '🗑️ Selected records deleted.', timeout: 3000, theme: 'metroui'}).show();";
}

/**
 * 5. FETCH FOR DISPLAY
 */
$query = "
    SELECT 
        w.WasteID, w.Quantity_Waste, w.Status, w.Date, w.Time,
        COALESCE(f.Food_Name, 'Removed Item') AS food_name,
        f.Image AS food_image,
        COALESCE(d.donor_username, 'N/A') AS donor_name
    FROM foodwaste w
    LEFT JOIN food_don_list f ON w.FoodList_ID = f.FoodList_ID
    LEFT JOIN donor d ON f.donor_id = d.donor_id
    ORDER BY w.Date DESC, w.Time DESC
";
$wasteData = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Waste Records Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noty/3.1.4/noty.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noty/3.1.4/themes/metroui.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noty/3.1.4/noty.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background-color: #f4f7f6; }
        .header-bar { background-color: #f39c12; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .dash-btn { background: white; color: #f39c12; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .container { width: 95%; max-width: 1200px; margin: 30px auto; background: #fdf6e3; padding: 30px; border-radius: 15px; box-shadow: 0px 10px 30px rgba(0,0,0,0.1); }
        .user-info { background: white; padding: 15px 25px; border-radius: 12px; border-left: 5px solid #3498db; margin-bottom: 25px; color: #2c3e50; }
        .styled-table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); } 
        .styled-table thead tr { background-color: #f39c12; color: white; text-align: left; }
        .styled-table th, .styled-table td { padding: 15px; }
        .styled-table tbody tr { border-bottom: 1px solid #eee; transition: 0.2s; }
        .donor-tag { background: #e8f4fd; color: #2980b9; padding: 5px 10px; border-radius: 6px; font-size: 0.85em; font-weight: bold; border: 1px solid #b3d7f2; }
        .waste-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .btn-delete { background-color: #e74c3c; color: white; padding: 12px 25px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 20px; }
    </style>
</head>
<body>

<div class="header-bar">
    <h2>Admin Dashboard</h2>
    <a href="http://10.175.254.163:3000/adminMenu.php" class="dash-btn">🏠 Back</a>
</div>

<div class="container">
    <h3>📋 WASTE RECORDS MANAGEMENT</h3>
    <div class="user-info">
        👤 <strong>Welcome, <?= htmlspecialchars($current_user) ?></strong> 👋<br>
        <small style="color: #666;"> Admin ID: <?= htmlspecialchars($admin_id) ?></small>
    </div>

    <form method="POST" id="wasteForm">
        <table class="styled-table">
            <thead>
                <tr>
                    <th width="50">Select</th>
                    <th>WasteID</th>
                    <th>Donor (User)</th>
                    <th>Food Item</th>
                    <th>Qty Wasted</th>
                    <th>Status / Description</th>
                    <th style="text-align:center;">Image</th>
                    <th>Date / Time</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($wasteData && $wasteData->num_rows > 0): ?>
                <?php while ($row = $wasteData->fetch_assoc()): ?>
                <tr>
                    <td align="center"><input type="checkbox" name="delete_id[]" value="<?= $row['WasteID']; ?>"></td>
                    <td><strong><?= $row['WasteID']; ?></strong></td>
                    <td><span class="donor-tag">👤 <?= htmlspecialchars($row['donor_name']) ?></span></td>
                    <td><strong><?= $row['food_name']; ?></strong></td>
                    <td><span style="background: #fdf2f2; color: #d32f2f; padding: 4px 10px; border-radius: 4px; font-weight: bold;"><?= $row['Quantity_Waste']; ?></span></td>
                    <td><?= htmlspecialchars($row['Status']); ?></td>
                    <td align="center"><?php if (!empty($row['food_image'])): ?><img src="<?= htmlspecialchars($row['food_image']) ?>" class="waste-img"><?php else: ?><small>No Image</small><?php endif; ?></td>
                    <td><?= $row['Date']; ?> <br><small><?= $row['Time']; ?></small></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" align="center" style="padding: 40px; color: #999;">No waste records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <button type="button" class="btn-delete" onclick="confirmDelete()">🗑️ Delete Selected Records</button>
        <input type="hidden" name="delete_selected_trigger" id="delete_trigger">
    </form>
</div>

<script>
    window.onload = function() { <?= $noty_script ?> };
    function confirmDelete() {
        const selected = document.querySelectorAll('input[name="delete_id[]"]:checked');
        if (selected.length === 0) return;
        if(confirm("Confirm deletion of logs?")) {
            document.getElementById('delete_trigger').value = "1";
            document.getElementById('wasteForm').submit();
        }
    }
</script>
</body>
</html>