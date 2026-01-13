<?php
// 1. SYSTEM SETTINGS
mysqli_report(MYSQLI_REPORT_OFF); 
include('DB.php'); 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/**
 * 2. SESSION HANDSHAKE
 */
if (isset($_GET['role'])) {
    $_SESSION['role']      = $_GET['role'];
    $_SESSION['user_name'] = $_GET['user_name'] ?? "User";
    $_SESSION['donor_id']  = $_GET['donor_id'] ?? null;
}

if (!isset($_SESSION['role'])) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h2>🔒 Access Denied</h2><p>Please login via the Dashboard.</p></div>");
}

$donor_id = $_SESSION['donor_id'] ?? null; 
$current_user = $_SESSION['user_name'];
$noty_script = ""; 

/**
 * 3. BACKGROUND PROCESSING (MODIFIED LOGIC)
 */
$today = date("Y-m-d");

// A. If quantity is 0 before expiry date, update status to 'consume'
$conn->query("UPDATE food_don_list SET Status = 'consumed' WHERE Quantity <= 0 AND Expiry_Date >= '$today'");

// B. If quantity is more than 0 and the date has expired, move to foodwaste and delete
$expiredQuery = "SELECT * FROM food_don_list WHERE Expiry_Date < '$today' AND Quantity > 0";
$expiredItems = $conn->query($expiredQuery);

if ($expiredItems && $expiredItems->num_rows > 0) {
    while ($item = $expiredItems->fetch_assoc()) {
        $fID = $item['FoodList_ID'];
        $qty = $item['Quantity'];
        
        $resMax = $conn->query("SELECT MAX(WasteID) AS max_id FROM foodwaste");
        $rowMax = $resMax->fetch_assoc();
        $newWID = "W" . str_pad((($rowMax['max_id']) ? (int) substr($rowMax['max_id'], 1) + 1 : 1), 3, "0", STR_PAD_LEFT);

        $stmtW = $conn->prepare("INSERT INTO foodwaste (WasteID, FoodList_ID, Quantity_Waste, Status, Date, Time, Admin_ID) VALUES (?, ?, ?, 'Expired Stock', CURDATE(), CURTIME(), ?)");
        $admin_placeholder = 0; 
        $stmtW->bind_param("ssii", $newWID, $fID, $qty, $admin_placeholder);
        
        if ($stmtW->execute()) {
            // Delete from food_don_list after moving to waste
            $conn->query("DELETE FROM food_don_list WHERE FoodList_ID = '$fID'");
        }
        $stmtW->close();
    }
}

/**
 * 4. BULK DELETE
 */
if (isset($_POST['bulk_delete']) && !empty($_POST['selected'])) {
    $ids = $_POST['selected'];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sqlDelete = "DELETE FROM food_don_list WHERE FoodList_ID IN ($placeholders) AND donor_id = ?";
    $stmt = $conn->prepare($sqlDelete);
    $types = str_repeat('s', count($ids)) . 's';
    $params = array_merge($ids, [$donor_id]);
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        $noty_script = "new Noty({type: 'error', text: '🗑️ Selected donations deleted.', timeout: 3000, theme: 'metroui'}).show();";
    }
    $stmt->close();
}

if (isset($_GET['success'])) $noty_script = "new Noty({type: 'success', text: '✅ Donation added!', timeout: 3000, theme: 'metroui'}).show();";
if (isset($_GET['updated'])) $noty_script = "new Noty({type: 'success', text: '✅ Donation updated!', timeout: 3000, theme: 'metroui'}).show();";

/**
 * 5. FETCH DATA FOR INTERFACE (MODIFIED)
 * Filters out items with 'consume' status or 0 quantity
 */
$stmt = $conn->prepare("
    SELECT f.*, c.CatType 
    FROM food_don_list f
    LEFT JOIN category c ON f.CatID = c.CatID
    WHERE f.donor_id = ? AND f.Status = 'available' AND f.Quantity > 0
    ORDER BY f.FoodList_ID ASC
");
$stmt->bind_param("s", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
$donations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Food Donations | Zero Hunger</title>
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
        .styled-table tbody tr { border-bottom: 1px solid #eee; }
        .btn-add { background-color: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin-bottom: 20px; }
        .delete-btn { background-color: #e74c3c; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .edit-btn { background-color: #3498db; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; margin-left: 10px; }
        .food-img { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
        .status-badge { color:#27ae60; font-weight: bold; text-transform: uppercase; font-size: 0.85em; }
    </style>
</head>
<body>

<div class="header-bar">
    <h2>Zero Hunger Hub</h2>
    <a href="http://10.175.254.163:3000/donorMenu.php" class="dash-btn">🏠 Back to Main</a>
</div>

<div class="container">
    <h3>📋 MY AVAILABLE DONATIONS</h3>
    <div class="user-info">
        👤 <strong>Welcome, <?= htmlspecialchars($current_user) ?></strong> 👋<br>
        <small style="color: #666;">Donor ID: <?= htmlspecialchars($donor_id) ?> 
    </div>

    <div style="margin-bottom: 20px;">
        <a href="AddItemForm.php" class="btn-add">➕ Add New Donation</a>
    </div>

    <form method="post" id="donationForm">
        <table class="styled-table">
            <thead>
                <tr>
                    <th width="50"><input type="checkbox" onclick="toggleAll(this)"></th>
                    <th>ID</th>
                    <th>Food Name</th>
                    <th>Qty Available</th>
                    <th>Qty Consumed</th>
                    <th>Category</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th style="text-align:center;">Image</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($donations) > 0): ?>
                <?php foreach ($donations as $don): ?>
                    <tr>
                        <td align="center"><input type="checkbox" name="selected[]" value="<?= htmlspecialchars($don['FoodList_ID']) ?>"></td>
                        <td><strong style="color: #2c3e50;"><?= htmlspecialchars($don['FoodList_ID']) ?></strong></td>
                        <td><strong><?= htmlspecialchars($don['Food_Name']) ?></strong></td>
                        <td><?= htmlspecialchars($don['Quantity']) ?></td>
                        <td style="color: #27ae60; font-weight: bold;">
                            <?= htmlspecialchars($don['Qty_Consumed'] ?? 0) ?>
                        </td>
                        <td><span style="background: #f0f0f0; padding: 4px 8px; border-radius: 4px; font-size: 0.9em;"><?= htmlspecialchars($don['CatType'] ?? 'Unassigned') ?></span></td>
                        <td><span style="color: #e67e22; font-weight: 600;"><?= htmlspecialchars($don['Expiry_Date']) ?></span></td>
                        <td><span class="status-badge">● <?= htmlspecialchars($don['Status']) ?></span></td>
                        <td align="center">
                            <?php if (!empty($don['Image'])): ?>
                                <img src="<?= htmlspecialchars($don['Image']) ?>" class="food-img">
                            <?php else: ?>
                                <small style="color:#ccc;">No Image</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" align="center" style="padding:40px; color: #999;">No active donations found. (Expired or empty items are automatically processed).</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 30px; display:flex;">
            <button type="button" class="delete-btn" onclick="confirmAction('delete')">🗑️ Delete Selected</button>
            <button type="button" class="edit-btn" onclick="confirmAction('edit')">✏️ Edit Selected</button>
        </div>
        <input type="hidden" name="bulk_delete" id="bulk_delete_trigger">
    </form>
</div>

<script>
    window.onload = function() { <?= $noty_script ?> };
    
    function toggleAll(source) {
        let checkboxes = document.querySelectorAll('input[name="selected[]"]');
        checkboxes.forEach(cb => cb.checked = source.checked);
    }

    function confirmAction(type) {
        let selected = document.querySelectorAll('input[name="selected[]"]:checked');
        if (selected.length === 0) {
            new Noty({type: 'warning', text: 'Please select at least one item.', timeout: 2000, theme: 'metroui'}).show();
            return;
        }

        if (type === 'edit') {
            if (selected.length > 1) {
                new Noty({type: 'warning', text: 'Please select only one item to edit.', timeout: 2000, theme: 'metroui'}).show();
                return;
            }
            window.location.href = 'AddItemForm.php?edit=' + encodeURIComponent(selected[0].value);
        } else {
            if(confirm('Are you sure you want to delete the selected donations?')) {
                document.getElementById('bulk_delete_trigger').value = "1";
                document.getElementById('donationForm').submit();
            }
        }
    }
</script>
</body>
</html>