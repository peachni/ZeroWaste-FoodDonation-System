<?php
// =============================
// 1️⃣ SYSTEM SETTINGS
// =============================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); 
include('DB.php'); 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// =============================
// 2️⃣ SESSION HANDSHAKE
// =============================
if (isset($_GET['role'])) {
    $_SESSION['role']      = $_GET['role'];
    $_SESSION['user_name'] = $_GET['user_name'] ?? "User";
    $_SESSION['donor_id']  = $_GET['donor_id'] ?? null;
}

if (!isset($_SESSION['role'])) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h2>🔒 Access Denied</h2><p>Please login via the Dashboard.</p></div>");
}

// =============================
// 3️⃣ ENSURE LOCAL TABLES EXIST (Self-Healing)
// =============================
$conn->query("CREATE TABLE IF NOT EXISTS donor (
    donor_id VARCHAR(10) PRIMARY KEY,
    donor_name VARCHAR(100),
    donor_username VARCHAR(50),
    donor_pass VARCHAR(255),
    donor_type VARCHAR(20),
    registration_date DATE
)");

// =============================
// 4️⃣ REMOTE CONNECTION & LOCAL SYNC
// =============================
$donor_id = $_SESSION['donor_id'] ?? null; 
$current_user = $_SESSION['user_name'];

try {
    $mod1_ip = "10.175.254.163"; 
    $mod1_conn = mysqli_init();
    $mod1_conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);

    if (@$mod1_conn->real_connect($mod1_ip, "syakur", "Sy@kur123", "postgres")) {
        $sql1 = "SELECT donor_id FROM donor WHERE donor_username = '$current_user' LIMIT 1";
        $res1 = $mod1_conn->query($sql1);
        if ($res1 && $row1 = $res1->fetch_assoc()) {
            $donor_id = $row1['donor_id'];
            $_SESSION['donor_id'] = $donor_id;
        }
        $mod1_conn->close();
    }
} catch (Exception $e) { }

if ($donor_id === null) {
    die("<div style='text-align:center; margin-top:50px;'><h2>❌ Error</h2><p>Could not resolve Donor ID.</p></div>");
}

// SYNC DONOR LOCALLY
$checkLocal = $conn->query("SELECT donor_id FROM donor WHERE donor_id = '$donor_id'");
if ($checkLocal->num_rows == 0) {
    $syncSql = "INSERT INTO donor (donor_id, donor_name, donor_username, donor_pass, donor_type, registration_date) 
                VALUES ('$donor_id', '$current_user', '$current_user', '1234', 'Individual', CURDATE())";
    $conn->query($syncSql);
}

// =============================
// 5️⃣ HELPER: Generate Food ID
// =============================
function generateFoodID($conn) {
    $result = $conn->query("SELECT FoodList_ID FROM food_don_list ORDER BY FoodList_ID DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastID = intval(substr($row['FoodList_ID'], 1));
        return "F" . str_pad($lastID + 1, 3, "0", STR_PAD_LEFT);
    }
    return "F001";
}

// =============================
// 6️⃣ FETCH EDIT DATA
// =============================
$editData = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM food_don_list WHERE FoodList_ID = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// =============================
// 7️⃣ HANDLE FORM SUBMISSION
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isEdit = isset($_POST['FoodList_ID']) && !empty($_POST['FoodList_ID']);
    $food_id = $isEdit ? $_POST['FoodList_ID'] : generateFoodID($conn);

    $food_name = trim($_POST['food_name'] ?? '');
    $food_desc = trim($_POST['food_desc'] ?? '');
    $quantity  = trim($_POST['quantity'] ?? '');
    $catID     = trim($_POST['food_cat'] ?? ''); 
    $m_date    = !empty($_POST['manufacture_date']) ? $_POST['manufacture_date'] : date('Y-m-d');
    $e_date    = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : date('Y-m-d', strtotime('+7 days'));
    $storage   = trim($_POST['storage_instruction'] ?? '');
    $allergen  = trim($_POST['allergen_info'] ?? '');
    $status    = $_POST['status'] ?? 'available';
    $created_date = date('Y-m-d');

    $image_path = $editData['Image'] ?? null;
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $image_name = uniqid('food_', true) . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name)) {
            $image_path = 'uploads/' . $image_name;
        }
    }

    if ($isEdit) {
        $stmt = $conn->prepare("UPDATE food_don_list SET Food_Name=?, Food_Desc=?, Quantity=?, CatID=?, Manufacture_Date=?, Expiry_Date=?, Storage_Instruction=?, Allergen_Info=?, Image=?, Status=?, donor_id=? WHERE FoodList_ID=?");
        $stmt->bind_param("ssssssssssss", $food_name, $food_desc, $quantity, $catID, $m_date, $e_date, $storage, $allergen, $image_path, $status, $donor_id, $food_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO food_don_list (FoodList_ID, Food_Name, Food_Desc, Quantity, CatID, Manufacture_Date, Expiry_Date, Storage_Instruction, Allergen_Info, Image, Status, Created_Date, donor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssss", $food_id, $food_name, $food_desc, $quantity, $catID, $m_date, $e_date, $storage, $allergen, $image_path, $status, $created_date, $donor_id);
    }

    $stmt->execute();
    header("Location: DisplayFoodList.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $editData ? 'Edit Donation' : 'Add Food Donation' ?></title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            margin: 0; 
            background-color: #f4f7f6; 
        }

        /* Dashboard Header Bar */
        .header-bar {
            background-color: #f39c12;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-bar h2 { margin: 0; font-size: 24px; }
        .dash-btn {
            background: white;
            color: #f39c12;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: 0.3s;
        }
        .dash-btn:hover { background: #eee; }

        /* Main Container Card */
        .container {
            width: 95%;
            max-width: 700px;
            margin: 40px auto;
            background: #fdf6e3; /* Warm Cream */
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0px 10px 30px rgba(0,0,0,0.1);
        }

        /* Donor Info Card (Dashboard Welcome Style) */
        .user-info { 
            background: white; 
            padding: 15px 25px; 
            border-radius: 12px; 
            border-left: 5px solid #3498db;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            color: #2c3e50;
        }

        h1 { color: #2c3e50; font-size: 26px; margin-top: 0; margin-bottom: 20px; }

        /* Form Styling */
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #444; font-size: 0.9em; }
        
        input[type="text"], input[type="date"], select, textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-sizing: border-box;
            font-size: 15px;
            background: white;
        }

        textarea { resize: vertical; }

        .btn-save {
            background-color: #27ae60;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            flex: 1;
        }
        .btn-save:hover { background-color: #219150; }

        .btn-cancel {
            background-color: #95a5a6;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: 0.3s;
            flex: 1;
        }
        .btn-cancel:hover { background-color: #7f8c8d; }

        .button-group { display: flex; gap: 15px; margin-top: 10px; }
    </style>
</head>
<body>

<!-- DASHBOARD HEADER -->
<div class="header-bar">
    <h2>Add Food Donation </h2>
    <a href="http://10.175.254.163:3000/donorMenu.php" class="dash-btn">🏠 Back to Main</a>
</div>

<div class="container">
    <h1><?= $editData ? '✏️ Edit Food Donation' : '🍱 Add New Food Donation' ?></h1>

    <!-- DONOR INFO CARD -->
    <div class="user-info">
        👤 <strong>Welcome, <?= htmlspecialchars($current_user) ?></strong> 👋<br>
        <small style="color: #666;">Logged in as Donor (ID: <?= htmlspecialchars($donor_id) ?>)</small>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <?php if ($editData): ?>
            <input type="hidden" name="FoodList_ID" value="<?= $editData['FoodList_ID'] ?>">
        <?php endif; ?>

        <label>Food Name:</label>
        <input type="text" name="food_name" value="<?= htmlspecialchars($editData['Food_Name'] ?? '') ?>" placeholder="e.g. Fried Rice" required>

        <label>Description:</label>
        <input type="text" name="food_desc" value="<?= htmlspecialchars($editData['Food_Desc'] ?? '') ?>" placeholder="e.g. Spicy and fresh" required>

        <label>Quantity:</label>
        <input type="text" name="quantity" value="<?= htmlspecialchars($editData['Quantity'] ?? '') ?>" placeholder="e.g. 5 (Only accept number)" required>

        <label>Category:</label>
        <select name="food_cat" required>
            <option value="">-- Select Category --</option>
            <?php
            $cats = $conn->query("SELECT CatID, CatType FROM category");
            while ($c = $cats->fetch_assoc()):
                $sel = ($editData['CatID'] ?? '') == $c['CatID'] ? 'selected' : '';
                echo "<option value='{$c['CatID']}' $sel>{$c['CatType']}</option>";
            endwhile;
            ?>
        </select>

        <div style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <label>Manufacture Date:</label>
                <input type="date" name="manufacture_date" value="<?= $editData['Manufacture_Date'] ?? date('Y-m-d') ?>" required>
            </div>
            <div style="flex: 1;">
                <label>Expiry Date:</label>
                <input type="date" name="expiry_date" value="<?= $editData['Expiry_Date'] ?? date('Y-m-d', strtotime('+7 days')) ?>" required>
            </div>
        </div>

        <label>Storage Instructions:</label>
        <textarea name="storage_instruction" rows="2" placeholder="e.g. Keep refrigerated"><?= htmlspecialchars($editData['Storage_Instruction'] ?? '') ?></textarea>

        <label>Allergen Information:</label>
        <textarea name="allergen_info" rows="2" placeholder="e.g. Contains peanuts"><?= htmlspecialchars($editData['Allergen_Info'] ?? '') ?></textarea>

        <label>Food Image:</label>
        <input type="file" name="image" accept="image/*" style="border: none; padding: 0;">

        <div class="button-group">
            <button type="submit" class="btn-save"><?= $editData ? 'Update Donation' : 'Save Donation' ?></button>
            <a href="DisplayFoodList.php" class="btn-cancel">Cancel & Back To List</a>
        </div>
    </form>
</div>

</body>
</html>