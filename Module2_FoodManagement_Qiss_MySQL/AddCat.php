<?php
// 1. SYSTEM SETTINGS
mysqli_report(MYSQLI_REPORT_OFF); 
include "DB.php"; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/**
 * 2. THE HETEROGENEOUS HANDSHAKE
 */
if (isset($_GET['role'])) {
    $_SESSION['role']      = $_GET['role'];
    $_SESSION['user_name'] = $_GET['user_name'] ?? "User";
    $_SESSION['admin_id']  = $_GET['admin_id'] ?? null;
}

if (!isset($_SESSION['role'])) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h2>🔒 Access Denied</h2><p>Please login via Syakur's Dashboard.</p></div>");
}

/**
 * 3. REMOTE CONNECTION (SYAKUR)
 */
$mod1_ip = "10.175.254.163"; 
$mod1_conn = mysqli_init();
$mod1_conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
$mod1_status = @$mod1_conn->real_connect($mod1_ip, "syakur", "Sy@kur123", "postgres");

$admin_id = $_SESSION['admin_id'] ?? 1; 

if ($mod1_status) {
    $current_user = $_SESSION['user_name'];
    $sql1 = "SELECT admin_id FROM admin WHERE admin_username = '$current_user' LIMIT 1";
    $res1 = $mod1_conn->query($sql1);
    
    if ($res1 && $row1 = $res1->fetch_assoc()) {
        $admin_id = $row1['admin_id'];
        $_SESSION['admin_id'] = $admin_id; 

        $checkLocal = $conn->query("SELECT Admin_ID FROM admin WHERE Admin_ID = '$admin_id'");
        if ($checkLocal->num_rows == 0) {
            $conn->query("INSERT INTO admin (Admin_ID, admin_username) VALUES ('$admin_id', '$current_user')");
        }
    }
    $mod1_conn->close();
}

$message = "";
$editMode = false;
$catID = "";
$catType = "";

// 4. LOAD EDIT MODE
if (isset($_GET['edit'])) {
    $editMode = true;
    $catID = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM category WHERE CatID = ?");
    $stmt->bind_param("s", $catID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $catType = $row['CatType'];
    }
    $stmt->close();
}

// 5. FORM SUBMITTED
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newType = trim($_POST['catType']);

    if (!preg_match("/^[a-zA-Z\s]+$/", $newType)) {
        $message = "<div class='message error'>Invalid Input! Category Type must only contain letters.</div>";
    } 
    else {
        // --- CASE A: EDITING AN EXISTING CATEGORY ---
        if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
            $catID = $_POST['edit_id'];
            $check = $conn->prepare("SELECT * FROM category WHERE CatType = ? AND CatID != ?");
            $check->bind_param("ss", $newType, $catID);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $message = "<div class='message error'>Duplicate Category! This type already exists.</div>";
            } else {
                $update = $conn->prepare("UPDATE category SET CatType = ? WHERE CatID = ?");
                $update->bind_param("ss", $newType, $catID);
                if ($update->execute()) {
                    header("Location: ManageCat.php?updated=1");
                    exit();
                }
            }
        }
        // --- CASE B: ADDING A NEW CATEGORY ---
        else {
            // 1. CHECK FOR DUPLICATION BEFORE INSERTING
            $checkDup = $conn->prepare("SELECT CatID FROM category WHERE CatType = ?");
            $checkDup->bind_param("s", $newType);
            $checkDup->execute();
            $resDup = $checkDup->get_result();

            if ($resDup->num_rows > 0) {
                // Category already exists
                $message = "<div class='message error'><strong>Duplicate Category!</strong> '$newType' already exists in the system.</div>";
            } 
            else {
                // 2. NO DUPLICATE: GENERATE ID AND INSERT
                $result = $conn->query("SELECT MAX(CatID) AS max_id FROM category");
                $row = $result->fetch_assoc();
                $maxId = $row['max_id'];
                $num = ($maxId) ? (int) substr($maxId, 1) + 1 : 1;
                $catID = "C" . str_pad($num, 3, "0", STR_PAD_LEFT);

                $insertStmt = $conn->prepare("INSERT INTO category (CatID, CatType, admin_id) VALUES (?, ?, ?)");
                $insertStmt->bind_param("ssi", $catID, $newType, $admin_id);

                if ($insertStmt->execute()) {
                    header("Location: ManageCat.php?success=1");
                    exit();
                } else {
                    $message = "<div class='message error'><strong>Database Error:</strong> " . $conn->error . "</div>";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $editMode ? "Edit Category" : "Add Category" ?></title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background-color: #f4f7f6; }
        .header-bar { background-color: #f39c12; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .container { width: 95%; max-width: 500px; margin: 50px auto; background: #fdf6e3; padding: 40px; border-radius: 15px; box-shadow: 0px 10px 30px rgba(0,0,0,0.1); }
        .user-info { background: white; padding: 15px 25px; border-radius: 12px; border-left: 5px solid #3498db; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px; color: #2c3e50; }
        h2 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #444; }
        input[type="text"] { width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #ccc; box-sizing: border-box; font-size: 16px; }
        input[disabled] { background-color: #eee; color: #666; cursor: not-allowed; }
        button { width: 100%; padding: 14px; background: #f39c12; border: none; color: white; font-size: 16px; font-weight: bold; border-radius: 8px; cursor: pointer; transition: 0.3s; }
        button:hover { background: #e67e22; }
        .cancel-btn { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; font-size: 14px; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-size: 14px; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
    <script>
        function confirmSubmit() { return confirm("Are you sure you want to save this category?"); }
        function validateChars(input) { input.value = input.value.replace(/[^a-zA-Z\s]/g, ''); }
    </script>
</head>
<body>

<div class="header-bar">
    <h2>Zero Hunger Hub</h2>
</div>

<div class="container">
    <h2><?= $editMode ? "✏️ Edit Category" : "➕ Add New Category" ?></h2>
    
    <div class="user-info">
        👤 <strong>Admin:</strong> <?= htmlspecialchars($_SESSION['user_name'] ?? 'System') ?> 👋<br>
        <small style="color: #666;">Admin ID: <?= htmlspecialchars($admin_id) ?></small>
    </div>

    <!-- Notification Message Area -->
    <?= $message ?>

    <form method="POST" action="AddCat.php" onsubmit="return confirmSubmit();">
        <?php if ($editMode): ?>
            <input type="hidden" name="edit_id" value="<?= htmlspecialchars($catID) ?>">
        <?php endif; ?>

        <label>Category ID:</label>
        <input type="text" value="<?= $editMode ? htmlspecialchars($catID) : 'Auto-generated' ?>" disabled>

        <label>Category Type (Letters Only):</label>
        <input type="text" 
               name="catType" 
               value="<?= htmlspecialchars($catType) ?>" 
               placeholder="e.g., Frozen Food" 
               required
               oninput="validateChars(this)"
               pattern="[A-Za-z\s]+"
               title="Only letters and spaces are allowed">

        <button type="submit"><?= $editMode ? "Update Category" : "Add Category" ?></button>
        <a href="ManageCat.php" class="cancel-btn">Cancel and Go Back</a>
    </form>
</div>

</body>
</html>