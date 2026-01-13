<?php
session_start();
require_once 'connect.php';
mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: donation.php"); 
    exit();
}

$display_id = $_SESSION['admin_id'] ?? 'A001';
$user_name = $_SESSION['user_name'] ?? 'Admin';
$role = "admin";

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
    'Logistics' => true
];

/* ----------------------------------------
   FETCH DONEES (SYAKUR - PostgreSQL)
---------------------------------------- */
$donee_list = [];
if ($module_status['User']) {
    $pg = @pg_connect("host=10.175.254.163 port=5432 dbname=postgres user=Cako password=Sy@kur123");
    if ($pg) {
        $res = pg_query($pg,"SELECT donee_id, donee_name, address, city FROM donee ORDER BY donee_name ASC");
        while ($row = pg_fetch_assoc($res)) { $donee_list[] = $row; }
        pg_close($pg);
    }
}

/* ----------------------------------------
   FETCH AVAILABLE FOOD (BALQIS - MySQL)
---------------------------------------- */
$food_options = [];
$conn_b = new mysqli();
$conn_b->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
$connected_balqis = @$conn_b->real_connect("10.175.254.152", "balqis", "Balqis123", "workshop2");

if ($connected_balqis) {
    $res = $conn_b->query("SELECT FoodList_id, Food_name, Quantity FROM Food_Don_List WHERE Status = 'Available' AND Quantity > 0");
    if ($res) {
        while ($f = $res->fetch_assoc()) {
            $food_options[] = [
                'food_id'   => $f['FoodList_id'],
                'food_name' => $f['Food_name'],
                'quantity'  => (int)$f['Quantity']
            ];
        }
    }
    $conn_b->close();
}

/* ----------------------------------------
   PROCESS SUBMISSION
---------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donee_id = (int)$_POST['donee_id'];
    $food_id  = escape($_POST['foodlist_id']);
    $qty      = (int)$_POST['quantity'];
    $don_date = escape($_POST['donation_date']);
    $mode     = $_POST['fulfillment_mode'];
    
    $status   = ($mode === 'self') ? 'Collected' : 'Pending';
    $address  = ($mode === 'self') ? "Self-Pickup at Source" : (isset($_POST['use_other_addr']) ? escape($_POST['other_address']) : escape($_POST['registered_address_val']));

    // --- STEP 1: VALIDATE & UPDATE BALQIS (Laptop C) ---
    $balqis_updated = false;
    $conn_remote = @new mysqli("10.175.254.152", "balqis", "Balqis123", "workshop2");
    
    if (!$conn_remote->connect_error) {
        $check = $conn_remote->query("SELECT Quantity FROM Food_Don_List WHERE FoodList_id = '$food_id'");
        $stock_row = $check->fetch_assoc();
        $current_stock = (int)$stock_row['Quantity'];
        $remainder = $current_stock - $qty;

        // Business Rules Validation
        if ($qty > $current_stock) { die("<script>alert('Error: Not enough stock.'); window.history.back();</script>"); }
        if ($qty < 10 && $current_stock >= 10) { die("<script>alert('Error: Min 10 units.'); window.history.back();</script>"); }
        if ($current_stock < 20 && $qty != $current_stock) { die("<script>alert('Error: Low stock. Must take all.'); window.history.back();</script>"); }
        if ($remainder > 0 && $remainder < 10) { die("<script>alert('Error: Remainder rule violated.'); window.history.back();</script>"); }

        // A. Subtract Stock and Increment Consumed (Status logic removed)
        $update_remote = "UPDATE Food_Don_List 
                         SET Quantity = Quantity - $qty, 
                             Qty_Consumed = COALESCE(Qty_Consumed, 0) + $qty 
                         WHERE FoodList_id = '$food_id'";
        
        if ($conn_remote->query($update_remote)) {
            $balqis_updated = true;
        }
    }

    // --- STEP 2: SAVE LOCALLY (Laptop B) ---
    if ($balqis_updated) {
        $sql_local = "INSERT INTO donation (donation_id, donee_id, foodlist_id, quantity, delivery_address, donation_date, status) 
                      VALUES ('', '$donee_id', '$food_id', $qty, '$address', '$don_date', '$status')";
        
        if ($conn->query($sql_local)) {
            $conn_remote->close();
            header("Location: donation.php?msg=added");
            exit;
        } else {
            // --- STEP 3: COMPENSATING ROLLBACK ---
            // If local save fails, reverse the math on Balqis's side (Status logic removed)
            $conn_remote->query("UPDATE Food_Don_List 
                                 SET Quantity = Quantity + $qty, 
                                     Qty_Consumed = Qty_Consumed - $qty 
                                 WHERE FoodList_id = '$food_id'");
            $conn_remote->close();
            die("Critical System Error: Local record failed. Balqis inventory has been rolled back.");
        }
    } else {
        die("Error: Could not update Food Module stock.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>New Donation | Zero Hunger</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
:root { --primary: #f39c12; --dark-orange: #a04000; --bg: #fdf6e3; --text-dark: #2c3e50; --shadow: rgba(0,0,0,0.08); }
body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text-dark); }

header { background: linear-gradient(90deg, #f39c12, #f1c40f); padding: 20px 40px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 10px var(--shadow); }
.header-left { display: flex; flex-direction: column; gap: 4px; }
header h1 { font-family: 'Segoe UI', sans-serif; color: white; margin: 0; font-size: 1.4rem; font-weight: 800; line-height: 1; }
.breadcrumb { font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; margin-top: 4px; line-height: 1; }
.breadcrumb .past { color: rgba(255,255,255,0.55); text-decoration: none; }
.breadcrumb .current { color: #ffffff; }

.header-right { display: flex; align-items: center; gap: 20px; }
.module-health { display: flex; gap: 12px; align-items: center; }
.health-item { background: rgba(0,0,0,0.7); color: white; padding: 6px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.dot { height: 7px; width: 7px; border-radius: 50%; }
.on { background: #2ecc71; } .off { background: #e74c3c; }

.main-wrapper { max-width: 650px; margin: 30px auto; padding: 0 20px; }
.welcome-card { background: white; padding: 15px 25px; border-radius: 12px; border-left: 10px solid var(--dark-orange); box-shadow: 0 4px 15px var(--shadow); margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
.welcome-card h2 { font-family: 'Segoe UI', sans-serif; margin: 0; font-size: 18px; font-weight: 700; }
.id-pill { background: #fff3e0; color: #d35400; padding: 4px 15px; border-radius: 50px; font-weight: 800; font-size: 12px; border: 1.5px solid #ffe0b2; display: inline-block; margin-top: 5px; }

.form-card { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 10px 30px var(--shadow); border-top: 5px solid var(--primary); }
label { display: block; font-family: 'Trebuchet MS', sans-serif; font-size: 12px; text-transform: uppercase; font-weight: 800; color: #7f8c8d; margin-bottom: 8px; }
input, select, textarea { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 8px; font-family: 'Trebuchet MS', sans-serif; font-size: 15px; margin-bottom: 20px; box-sizing: border-box; outline: none; }
input:focus, select:focus { border-color: var(--primary); }

.btn-save { width: 100%; background: var(--primary); color: white; border: none; padding: 15px; border-radius: 8px; font-weight: 800; cursor: pointer; transition: 0.3s; font-size: 13px; letter-spacing: 1px; }
.btn-save:hover { background: var(--dark-orange); transform: translateY(-2px); }

.addr-box { background: #f9f9f9; padding: 12px; border-radius: 8px; border: 1px dashed #ccc; margin-bottom: 10px; font-size: 14px; color: #555; }
.checkbox-row { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; font-size: 13px; font-weight: 800; color: #7f8c8d; }
.checkbox-row input { width: auto; margin: 0; }
.stock-warning { font-size: 11px; color: #e67e22; font-weight: bold; margin-top: -15px; margin-bottom: 15px; display: none; }
</style>
</head>
<body>

<header>
    <div class="header-left">
        <h1>Zero Hunger Hub</h1>
        <div class="breadcrumb">
            <a href="donation.php" class="past">Menu</a> <i class="fas fa-chevron-right"></i> <span class="current">Donation</span>
        </div>
    </div>
    <div class="header-right">
        <div class="module-health">
            <?php foreach($module_status as $name => $status): ?>
                <div class="health-item"><div class="dot <?= $status?'on':'off' ?>"></div> <?= $name ?></div>
            <?php endforeach; ?>
        </div>
        <a href="http://10.175.254.163:3000/userLogOut.php" style="background: white; color: #c0392b; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 800; border: 1px solid #c0392b; margin-left:15px;">LOGOUT</a>
    </div>
</header>

<div class="main-wrapper">
    <div class="welcome-card">
        <div><h2>Manual Donation Record</h2><div class="id-pill"><?= htmlspecialchars($display_id) ?> &nbsp;&nbsp;&nbsp; Admin</div></div>
    </div>

    <div class="form-card">
        <form method="POST" onsubmit="return validateStock()">
            <label>Recipient Donee</label>
            <select name="donee_id" id="donee_select" required onchange="autoFillAddress()">
                <option value="" disabled selected>-- Select Registered Donee --</option>
                <?php foreach ($donee_list as $d): ?>
                  <option value="<?= $d['donee_id'] ?>" data-addr="<?= htmlspecialchars($d['address']) ?>|<?= htmlspecialchars($d['city']) ?>">
                    <?= htmlspecialchars($d['donee_name'] . ' (' . $d['donee_id'] . ')') ?>
                  </option>
                <?php endforeach; ?>
            </select>

            <label>Fulfillment Mode</label>
            <select name="fulfillment_mode" id="fulfillment_mode" onchange="toggleFulfillment()" required>
                <option value="delivery">Logistics Required (Delivery)</option>
                <option value="self">Self-Pickup (Direct Handover)</option>
            </select>

            <div id="delivery_fields">
                <label>Delivery Point</label>
                <div id="addr_display" class="addr-box">Select donee to view address...</div>
                <input type="hidden" name="registered_address_val" id="hidden_addr">

                <div class="checkbox-row">
                    <input type="checkbox" name="use_other_addr" id="other_check" onchange="toggleManualAddr()">
                    <span>Other Address Choice</span>
                </div>

                <div id="manual_addr_area" style="display:none;">
                    <textarea name="other_address" id="manual_input" rows="2" placeholder="Insert custom delivery address..."></textarea>
                </div>
            </div>

            <label>Requested Item</label>
            <select name="foodlist_id" id="food_select" required onchange="updateStockRules()">
                <option value="" disabled selected>-- Select Available Item --</option>
                <?php foreach ($food_options as $food): ?>
                    <option value="<?= $food['food_id'] ?>" data-stock="<?= $food['quantity'] ?>">
                        <?= htmlspecialchars($food['food_name']) ?> (Stock: <?= $food['quantity'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <div id="stock_alert" class="stock-warning"></div>

            <div style="display:flex; gap:15px;">
                <div style="flex:1;"><label>Quantity</label><input type="number" name="quantity" id="qty_input" min="10" value="10" required></div>
                <div style="flex:1;"><label>Date</label><input type="date" name="donation_date" value="<?= date('Y-m-d') ?>" required></div>
            </div>

            <button type="submit" class="btn-save">REGISTER TRANSACTION</button>
        </form>
        <a href="donation.php" style="display:block; text-align:center; margin-top:20px; color:#95a5a6; text-decoration:none; font-size:13px; font-weight:800;">Cancel</a>
    </div>
</div>

<script>
function toggleFulfillment() {
    const mode = document.getElementById('fulfillment_mode').value;
    document.getElementById('delivery_fields').style.display = (mode === 'self') ? 'none' : 'block';
}

function updateStockRules() {
    const sel = document.getElementById('food_select');
    if (!sel.value) return;
    const stock = parseInt(sel.options[sel.selectedIndex].getAttribute('data-stock'));
    const qtyInput = document.getElementById('qty_input');
    const alertBox = document.getElementById('stock_alert');

    if (stock < 20) {
        qtyInput.value = stock;
        qtyInput.readOnly = true;
        qtyInput.style.backgroundColor = "#f0f0f0";
        alertBox.innerHTML = `* Low stock detected (${stock}). Allocating full amount to prevent wastage.`;
        alertBox.style.display = 'block';
    } else {
        qtyInput.value = 10;
        qtyInput.readOnly = false;
        qtyInput.style.backgroundColor = "white";
        alertBox.style.display = 'none';
    }
}

function validateStock() {
    const sel = document.getElementById('food_select');
    const stock = parseInt(sel.options[sel.selectedIndex].getAttribute('data-stock'));
    const qty = parseInt(document.getElementById('qty_input').value);
    const remainder = stock - qty;

    if (qty > stock) { alert("Error: Over stock allocation."); return false; }
    if (qty < 10 && stock >= 10) { alert("Min 10 units required."); return false; }
    if (remainder > 0 && remainder < 10) { alert("Remainder rule violation."); return false; }

    return confirm('Register this transaction?');
}

function autoFillAddress() {
    const sel = document.getElementById('donee_select');
    const data = sel.options[sel.selectedIndex].getAttribute('data-addr');
    if (!data) return;
    const [addr, city] = data.split('|');
    const addrDisplay = document.getElementById('addr_display');
    const hiddenAddr = document.getElementById('hidden_addr');
    if (!document.getElementById('other_check').checked) {
        addrDisplay.innerHTML = `<strong>Address:</strong> ${addr}<br><strong>City:</strong> ${city}`;
        hiddenAddr.value = addr + ', ' + city;
    }
}

function toggleManualAddr() {
    const isChecked = document.getElementById('other_check').checked;
    document.getElementById('manual_addr_area').style.display = isChecked ? 'block' : 'none';
    const addrDisplay = document.getElementById('addr_display');
    if (isChecked) { addrDisplay.style.opacity = '0.4'; } 
    else { addrDisplay.style.opacity = '1'; autoFillAddress(); }
}
</script>
</body>
</html>