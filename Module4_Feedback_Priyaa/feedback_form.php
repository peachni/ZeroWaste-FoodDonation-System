<?php
// 1. SYSTEM SETTINGS
mysqli_report(MYSQLI_REPORT_OFF); // Prevent fatal crashes on connection errors
session_start();

// THE DASHBOARD URL (Syakur's Laptop A address)
$dashboard_url = "http://10.175.254.163:3000/doneeMenu.php"; 

/**
 * 2. THE HETEROGENEOUS HANDSHAKE
 */
if (isset($_REQUEST['donee_id'])) {
    $_SESSION['donee_id']   = $_REQUEST['donee_id'];
    $_SESSION['donee_name'] = $_REQUEST['donee_name'] ?? "User";
    $_SESSION['admin_id']   = $_REQUEST['admin_id'] ?? 1;
}

// Ensure we have a user identified
$donee_id   = $_SESSION['donee_id'] ?? null;
$donee_name = $_SESSION['donee_name'] ?? "User";
$admin_id   = $_SESSION['admin_id'] ?? 1;

// If no user data at all, show a clean Access Denied
if (!$donee_id) {
    die("<div style='text-align:center; margin-top:100px; font-family:sans-serif;'>
            <h1 style='color:#e67e22;'>🔒 Access Denied</h1>
            <p>No user data received. Please login via Syakur's Dashboard.</p>
            <br><a href='$dashboard_url' style='color:#3498db; text-decoration:none;'>Return to Dashboard</a>
         </div>");
}

$error_log = [];
$food_options = [];

// 3. REMOTE CONNECTIONS (ZeroTier)
$mod3_ip = "10.175.254.3";   // Nadia
$mod2_ip = "10.175.254.152"; // Balqis

// Connect to Module 3 (Nadia - zerowaste)
$mod3_conn = mysqli_init();
$mod3_conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
$mod3_status = @$mod3_conn->real_connect($mod3_ip, "NadPnya", "", "zerowaste", 3307);

// Connect to Module 2 (Balqis - workshop2)
$mod2_conn = mysqli_init();
$mod2_conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
$mod2_status = @$mod2_conn->real_connect($mod2_ip, "balqis", "Balqis123", "workshop2");

if (!$mod3_status) { $error_log[] = "Module 3 (Nadia) is offline or ZeroTier IP unreachable."; }
if (!$mod2_status) { $error_log[] = "Module 2 (Balqis) is offline or ZeroTier IP unreachable."; }

/**
 * 4. FETCH DATA
 */
if ($mod3_status) {
    $sql3 = "SELECT FoodList_ID FROM donation WHERE Donee_ID = '$donee_id'";
    $res3 = $mod3_conn->query($sql3);
    
    $received_ids = [];
    if ($res3 && $res3->num_rows > 0) {
        while($row = $res3->fetch_assoc()) {
            $received_ids[] = "'" . $row['FoodList_ID'] . "'";
        }
    }

    if (!empty($received_ids) && $mod2_status) {
        $id_list = implode(",", $received_ids);
        $sql2 = "SELECT FoodList_ID, Food_Name FROM food_don_list WHERE FoodList_ID IN ($id_list)";
        $res2 = $mod2_conn->query($sql2);
        if ($res2) {
            while($row = $res2->fetch_assoc()) {
                $food_options[] = $row;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quality Feedback - ZeroHunger</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: white; width: 450px; padding: 40px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); border-top: 6px solid #e67e22; position: relative; text-align: center; }
        .back-nav { position: absolute; top: 15px; left: 20px; color: #95a5a6; text-decoration: none; font-size: 13px; font-weight: 500; }
        .back-nav:hover { color: #e67e22; }
        h2 { color: #2c3e50; margin: 25px 0 5px 0; font-size: 22px; }
        .subtitle { color: #e67e22; font-weight: bold; text-transform: uppercase; font-size: 11px; display: block; margin-bottom: 25px; }
        .user-badge { background: #fff3e0; border: 1px solid #ffe0b2; padding: 12px; border-radius: 8px; margin-bottom: 20px; color: #d35400; font-size: 14px; }
        .error-msg { background: #fff5f5; color: #c53030; padding: 10px; border-radius: 6px; font-size: 11px; margin-bottom: 15px; border-left: 4px solid #fc8181; text-align: left; }
        label { display: block; text-align: left; font-weight: 600; margin-top: 15px; color: #34495e; font-size: 14px; }
        select, textarea { width: 100%; padding: 12px; border: 1px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box; outline: none; }
        textarea { height: 80px; resize: none; }
        
        /* Star Rating CSS */
        .stars { display: flex; justify-content: center; flex-direction: row-reverse; gap: 5px; margin: 10px 0; }
        .stars input { display: none; }
        .stars label { font-size: 40px; color: #dfe6e9; cursor: pointer; transition: 0.2s; }
        
        /* This handles the coloring logic when hovering or clicking */
        .stars input:checked ~ label, 
        .stars label:hover, 
        .stars label:hover ~ label { color: #f1c40f; }

        .btn-submit { background: #e67e22; color: white; width: 100%; padding: 14px; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 20px; transition: 0.3s; }
        .btn-submit:hover { background: #d35400; }
    </style>
</head>
<body>

<div class="card">
    <a href="<?php echo $dashboard_url; ?>" class="back-nav">← Back to Dashboard</a>

    <h2>Food Quality Feedback</h2>
    <span class="subtitle">Dynamic SDG Monitoring System</span>

    <div class="user-badge">
        👤 Logged in as: <strong><?php echo htmlspecialchars($donee_name); ?></strong>
    </div>

    <?php if (!empty($error_log)): ?>
        <div class="error-msg">
            <?php foreach($error_log as $err) echo "• $err <br>"; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($food_options)): ?>
        <div style="color: #636e72; font-size: 14px; padding: 10px 0;">
            <p>Hello <?php echo htmlspecialchars($donee_name); ?>,</p>
            <p>No delivered items found. You are able to submit feedback once you have received any donation.</p>
        </div>
    <?php else: ?>
        <form method="POST" action="submit_feedback.php">
            <input type="hidden" name="donee_id" value="<?php echo $donee_id; ?>">
            <input type="hidden" name="admin_id" value="<?php echo $admin_id; ?>">

            <label>1. Which food did you receive?</label>
            <select name="foodlist_id" required>
                <option value="" disabled selected>-- Select item --</option>
                <?php foreach ($food_options as $food): ?>
                    <option value="<?php echo $food['FoodList_ID']; ?>">
                        <?php echo htmlspecialchars($food['Food_Name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>2. Rate the Quality</label>
            <div class="stars">
                <!-- THE IDs AND 'FOR' ATTRIBUTES ARE NOW MATCHED CORRECTLY -->
                <input type="radio" name="rating" id="s5" value="5" required><label for="s5">★</label>
                <input type="radio" name="rating" id="s4" value="4"><label for="s4">★</label>
                <input type="radio" name="rating" id="s3" value="3"><label for="s3">★</label>
                <input type="radio" name="rating" id="s2" value="2"><label for="s2">★</label>
                <input type="radio" name="rating" id="s1" value="1"><label for="s1">★</label>
            </div>

            <label>3. Food Condition</label>
            <select name="quality_status" required>
                <option value="Fresh">Fresh</option>
                <option value="Near Expiration">Near Expiration</option>
                <option value="Damaged">Damaged / Packaging Torn</option>
                <option value="Expired">Expired</option>
            </select>

            <label>4. Your Comments</label>
            <textarea name="comments" placeholder="Describe the quality..." required></textarea>

            <button type="submit" class="btn-submit">Submit Feedback Report</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>