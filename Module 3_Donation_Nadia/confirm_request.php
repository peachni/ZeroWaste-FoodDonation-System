<?php
session_start();
require_once 'connect.php';
mysqli_report(MYSQLI_REPORT_OFF);

if (($_SESSION['role'] ?? '') !== 'donee') {
    header("Location: donation.php");
    exit();
}

$food_id   = $_GET['food_id'] ?? die("No Item Selected.");
$donee_id  = $_SESSION['donee_id'];
$user_name = $_SESSION['user_name'];
$display_id = $_SESSION['donee_id'];
$role = "donee";

/* ----------------------------------------
   MODULE HEALTH CHECK
---------------------------------------- */
function check_module($ip, $port, $timeout = 0.3) {
    $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    if ($fp) { fclose($fp); return true; }
    return false;
}

$module_status = [
    'User'      => check_module('10.175.254.163', 5432),
    'Food'      => check_module('10.175.254.152', 3306),
    'Logistics' => true,
    'Feedback'  => check_module('10.175.254.1', 3306)
];

/* ----------------------------------------
   FETCH DONEE ADDRESS (SYAKUR - POSTGRES)
---------------------------------------- */
$registered_address = "Address not found";

if ($module_status['User'] && function_exists('pg_connect')) {
    // UPDATED: Password to Sy@kur123 and Database to postgres
    $pg = @pg_connect("host=10.175.254.163 port=5432 dbname=postgres user=Cako password=Sy@kur123 connect_timeout=5");
    if ($pg) {
        // FIXED: Removed double quotes and used lowercase names as suggested by the Error Hint
        $res = pg_query_params(
            $pg,
            'SELECT address, city FROM donee WHERE donee_id = $1',
            [(int)$donee_id] // Cast to int since Syakur uses INT for IDs
        );
        if ($res && $row = pg_fetch_assoc($res)) {
            $registered_address = trim(($row['address'] ?? '') . ', ' . ($row['city'] ?? ''));
        }
        pg_close($pg);
    }
}

/* ----------------------------------------
   FETCH FOOD ITEM (BALQIS - MYSQL)
---------------------------------------- */
$conn_balqis = @new mysqli("10.175.254.152", "balqis", "Balqis123", "workshop2");
$item = null;
$remote_server_url = "http://10.175.254.152:3000/";

if (!$conn_balqis->connect_error) {
    $res = $conn_balqis->query("SELECT * FROM food_don_list WHERE FoodList_ID = '$food_id' LIMIT 1");
    $item = $res->fetch_assoc();
    $conn_balqis->close();
}

if (!$item) {
    die("Item details currently unavailable.");
}

/* ----------------------------------------
   QUANTITY LOGIC (Waste Prevention)
---------------------------------------- */
$stock = (int)$item['Quantity'];
$min_req = 10;
$force_all = false;

if ($stock < 20) {
    $force_all = true;
    $default_qty = $stock;
} else {
    $default_qty = 10;
}

/* ----------------------------------------
   IMAGE PATH LOGIC
---------------------------------------- */
if (empty($item['Image'])) {
    $img_src = 'https://via.placeholder.com/300x160?text=No+Image';
} elseif (filter_var($item['Image'], FILTER_VALIDATE_URL)) {
    $img_src = $item['Image'];
} else {
    $img_src = $remote_server_url . $item['Image'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Confirm Request | Zero Hunger</title>
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
.breadcrumb .past { color: rgba(255,255,255,0.5); text-decoration: none; transition: 0.3s; } 
.breadcrumb .past:hover { color: #ffffff; opacity: 1; }
.breadcrumb .current { color: #ffffff; }
.breadcrumb i { font-size: 9px; margin: 0 8px; color: rgba(255,255,255,0.4); display: inline-flex; align-items: center; }

.header-right { display: flex; align-items: center; gap: 20px; }

.main-wrapper { max-width: 750px; margin: 30px auto; padding: 0 20px; }
.welcome-card { background: white; padding: 15px 25px; border-radius: 12px; border-left: 10px solid var(--primary); box-shadow: 0 4px 15px var(--shadow); margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
.welcome-card h2 { font-family: 'Segoe UI', sans-serif; margin: 0; font-size: 18px; font-weight: 700; }

.form-card { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 10px 30px var(--shadow); border-top: 5px solid var(--primary); }
.item-summary { display: flex; gap: 20px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
.item-summary img { width: 140px; height: 140px; border-radius: 8px; object-fit: cover; background: #eee; border: 1px solid #ddd; }
.item-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px; font-size: 12px; color: #555; }

label { display: block; font-family: 'Trebuchet MS', sans-serif; font-size: 12px; text-transform: uppercase; font-weight: 800; color: #7f8c8d; margin-bottom: 8px; }
input, select, textarea { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 8px; font-family: 'Trebuchet MS', sans-serif; font-size: 15px; margin-bottom: 20px; box-sizing: border-box; outline: none; }
input:focus { border-color: var(--primary); }

.addr-box { background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px dashed #ccc; margin-bottom: 10px; font-size: 14px; min-height: 40px; color: #555; text-align: left; }
.checkbox-row { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; font-size: 13px; font-weight: 800; color: #7f8c8d; }
.checkbox-row input { width: auto; margin: 0; }

.btn-submit { width: 100%; background: var(--primary); color: white; border: none; padding: 15px; border-radius: 8px; font-weight: 800; cursor: pointer; transition: 0.3s; font-size: 13px; letter-spacing: 1px; }
.btn-submit:hover { background: var(--dark-orange); transform: translateY(-2px); }

.fas { transition: 0.3s; } .fas:hover { opacity: 0.7; transform: scale(1.1); }
</style>
</head>
<body>

<header>
    <div class="header-left">
        <h1>Zero Hunger</h1>
        <div class="breadcrumb">
            <a href="http://10.175.254.163:3000/<?= $role ?>Menu.php" class="past">Menu</a> 
            <i class="fas fa-chevron-right"></i> 
            <a href="donation.php" class="past">Donation</a> 
            <i class="fas fa-chevron-right"></i> 
            <span class="current">Request</span>
        </div>
    </div>
    <div class="header-right">
    </div>
</header>

<div class="main-wrapper">
    <div class="welcome-card">
        <div>
            <h2>Donation Request Form</h2>
        </div>
    </div>

    <div class="form-card">
        <div class="item-summary">
            <img src="<?= htmlspecialchars($img_src) ?>" onerror="this.src='https://via.placeholder.com/140?text=Error'">
            <div style="flex:1;">
                <h3 style="margin:0; font-family:'Trebuchet MS'; color:var(--text-dark);"><?= htmlspecialchars($item['Food_Name']) ?></h3>
                
                <div class="item-details-grid">
                    <div><strong>Expiry:</strong> <?= $item['Expiry_Date'] ?></div>
                    <div><strong>Storage:</strong> <?= htmlspecialchars($item['Storage_Instruction']) ?></div>
                    <div style="grid-column: span 2;"><strong>Allergens:</strong> <?= htmlspecialchars($item['Allergen_Info'] ?? 'None') ?></div>
                </div>

                <div style="color:var(--primary); font-weight:800; margin-top:10px;">Stock Availability: <?= $stock ?> Units</div>
            </div>
        </div>

        <form action="process_request.php" method="POST" onsubmit="return validateWastage()">
            <input type="hidden" name="food_id" value="<?= htmlspecialchars($food_id) ?>">
            <input type="hidden" id="current_stock" value="<?= $stock ?>">

            <label>Requested Quantity (Min: 10)</label>
            <input type="number" name="quantity" id="qty_input" 
                   min="<?= $min_req ?>" 
                   max="<?= $stock ?>" 
                   value="<?= $default_qty ?>" 
                   <?= $force_all ? 'readonly style="background:#f0f0f0;"' : '' ?> 
                   required>
            
            <?php if($force_all): ?>
                <p style="font-size:11px; color:#e67e22; margin-top:-15px; margin-bottom:15px; font-weight:bold;">
                    * Low stock detected. Min request <?= $stock ?> units to prevent wastage.
                </p>
            <?php endif; ?>

            <label>Fulfillment Mode</label>
            <select name="fulfillment_mode" id="fulfillment_mode" onchange="toggleFulfillment()" required>
                <option value="delivery">Delivery</option>
                <option value="self">Self-Pickup</option>
            </select>

            <div id="delivery_fields">
                <label>Delivery Point</label>
                <div id="addr_display" class="addr-box">
                    <strong>Registered Profile Address:</strong><br>
                    <?= htmlspecialchars($registered_address) ?>
                </div>
                <input type="hidden" name="registered_address_val" id="hidden_addr" value="<?= htmlspecialchars($registered_address) ?>">

                <div class="checkbox-row">
                    <input type="checkbox" name="use_other_addr" id="other_check" onchange="toggleManualAddr()">
                    <span>OTHER DELIVERY ADDRESS</span>
                </div>

                <div id="manual_addr_area" style="display:none;">
                    <label>Custom Delivery Address</label>
                    <textarea name="other_address" id="manual_input" rows="3" placeholder="Enter full recipient address details..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit">CONFIRM REQUEST</button>
        </form>
        <a href="food_marketplace.php" style="display:block; text-align:center; margin-top:20px; color:#95a5a6; text-decoration:none; font-size:13px; font-weight:800;">Cancel and Return</a>
    </div>
</div>

<script>
function toggleFulfillment() {
    const mode = document.getElementById('fulfillment_mode').value;
    document.getElementById('delivery_fields').style.display = (mode === 'self') ? 'none' : 'block';
}

function toggleManualAddr() {
    const isChecked = document.getElementById('other_check').checked;
    document.getElementById('manual_addr_area').style.display = isChecked ? 'block' : 'none';
    const addrDisplay = document.getElementById('addr_display');
    
    if (isChecked) {
        addrDisplay.style.opacity = '0.4';
        document.getElementById('manual_input').required = true;
    } else {
        addrDisplay.style.opacity = '1';
        document.getElementById('manual_input').required = false;
    }
}

function validateWastage() {
    const stock = parseInt(document.getElementById('current_stock').value);
    const requested = parseInt(document.getElementById('qty_input').value);
    const remainder = stock - requested;

    if (requested < 10) {
        alert("Minimum request is 10 units.");
        return false;
    }

    if (remainder > 0 && remainder < 10) {
        alert("Wastage Prevention Rule: You cannot leave a remainder of " + remainder + " units. Please either take all " + stock + " units or request fewer items to leave at least 10.");
        return false;
    }

    return confirm('Submit this request?');
}
</script>

</body>
</html>