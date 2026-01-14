<?php
session_start();
require_once 'connect.php';

/* ===============================
   1️⃣ SESSION PROTECTION
   =============================== */
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    die("<h2 style='text-align:center;margin-top:50px;color:#e74c3c;'>🔒 Access Denied</h2>");
}

$admin_username = $_SESSION['admin_username'] ?? "Admin";

/* ===============================
   2️⃣ FETCH VOLUNTEER DATA
   =============================== */
$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if (!$id) {
    header("Location: volunteer.php");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM volunteer WHERE volunteer_id='$id'");
$v = mysqli_fetch_assoc($result);

if (!$v) {
    die("<h3 style='text-align:center;margin-top:50px;'>Volunteer not found</h3>");
}

/* ===============================
   3️⃣ HANDLE UPDATE (POST)
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = mysqli_real_escape_string($conn, $_POST['name']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']); 
    $contact  = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $vehicle  = mysqli_real_escape_string($conn, $_POST['vehicle_type']);
    $area     = mysqli_real_escape_string($conn, $_POST['area_assigned']);
    $status   = mysqli_real_escape_string($conn, $_POST['availability_status']);

    // --- LOGIC GUARD ---
    // If the volunteer has a linked Donee, they MUST stay 'Busy' until 'Complete' is clicked.
    if (!empty($v['donee_id']) && $status === 'Available') {
        $error = "Cannot set to Available while a Task (Donee D{$v['donee_id']}) is still linked. Please use the 'Complete' button on the main dashboard instead.";
    } else {
        $sql = "UPDATE volunteer SET 
                name='$name', 
                email='$email',
                contact_number='$contact', 
                vehicle_type='$vehicle', 
                area_assigned='$area', 
                availability_status='$status'
                WHERE volunteer_id='$id'";
        
        if (mysqli_query($conn, $sql)) {
            header("Location: volunteer.php?msg=updated");
            exit;
        } else {
            $error = "Error updating record: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Volunteer | Zero Hunger Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
        :root { --primary: #f39c12; --dark-orange: #a04000; --bg: #fdf6e3; --text-dark: #2c3e50; --shadow: rgba(0,0,0,0.08); }
        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text-dark); }
        header { background: linear-gradient(90deg, #f39c12, #f1c40f); padding: 20px 40px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 10px var(--shadow); }
        header h1 { color: white; margin: 0; font-size: 1.4rem; font-weight: 800; }
        
        .main-wrapper { max-width: 700px; margin: 30px auto; padding: 0 20px; }
        .form-card { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 10px 30px var(--shadow); border-top: 5px solid var(--primary); }
        
        .error-msg { background: #fdedec; color: #e74c3c; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; border-left: 5px solid #e74c3c; }
        .admin-badge { background: #fef9e7; padding: 12px; border-radius: 8px; margin-bottom: 25px; font-size: 13px; color: var(--dark-orange); border: 1px solid #f9e79f; font-weight: 800; text-align: center; }
        
        label { display: block; font-size: 11px; text-transform: uppercase; font-weight: 800; color: #7f8c8d; margin-bottom: 8px; }
        input, select { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; margin-bottom: 20px; box-sizing: border-box; }
        .readonly-field { background-color: #f9f9f9; border: 2px solid #ddd; color: #95a5a6; cursor: not-allowed; }
        
        .btn-update { width: 100%; background: var(--primary); color: white; border: none; padding: 16px; border-radius: 8px; font-weight: 800; cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; }
        .btn-update:hover { background: var(--dark-orange); transform: translateY(-2px); }
        
        .footer-links { display: flex; justify-content: space-between; margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px; }
        .footer-links a { text-decoration: none; color: #7f8c8d; font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>

<header><h1>Zero Hunger Hub</h1></header>

<div class="main-wrapper">
    <div style="text-align: right; font-size: 12px; margin-bottom: 10px; color: #7f8c8d;">
        Editing as: <strong><?= htmlspecialchars($admin_username) ?></strong>
    </div>

    <div class="form-card">
        <div class="admin-badge">
            <i class="fas fa-edit"></i> UPDATE VOLUNTEER PROFILE
        </div>

        <?php if(isset($error)): ?>
            <div class="error-msg"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <label><i class="fas fa-id-badge"></i> Volunteer ID</label>
            <input type="text" value="<?= htmlspecialchars($v['volunteer_id']) ?>" class="readonly-field" readonly>

            <label><i class="fas fa-link"></i> Linked Task</label>
            <input type="text" 
                   value="<?= !empty($v['donee_id']) ? 'Assigned to Donee D' . htmlspecialchars($v['donee_id']) : 'No Active Task' ?>" 
                   class="readonly-field" readonly>
            
            <p style="font-size: 11px; color: #e67e22; margin-top: -15px; margin-bottom: 20px;">
                *Donee tasks are synced from the Donation Module.
            </p>

            <label>Full Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($v['name']) ?>" required>

            <label>Email Address</label>
            <input type="email" name="email" value="<?= htmlspecialchars($v['email'] ?: '') ?>">

            <div style="display:flex; gap:15px;">
                <div style="flex:1;">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" value="<?= htmlspecialchars($v['contact_number']) ?>" required>
                </div>
                <div style="flex:1;">
                    <label>Vehicle Type</label>
                    <select name="vehicle_type">
                        <?php $types = ['Motorcycle', 'Car', 'Van', 'Truck'];
                        foreach ($types as $t): ?>
                            <option value="<?= $t ?>" <?= ($v['vehicle_type'] == $t) ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <label>Assigned Area</label>
            <input type="text" name="area_assigned" value="<?= htmlspecialchars($v['area_assigned']) ?>" required>

            <label>Availability Status</label>
            <select name="availability_status">
                <option value="Available" <?= ($v['availability_status'] == 'Available') ? 'selected' : '' ?>>Available</option>
                <option value="Busy" <?= ($v['availability_status'] == 'Busy') ? 'selected' : '' ?>>Busy</option>
                <option value="Inactive" <?= ($v['availability_status'] == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
            </select>

            <button type="submit" class="btn-update">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>

        <div class="footer-links">
            <a href="volunteer.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="http://10.175.254.163:3000/adminMenu.php"><i class="fas fa-home"></i> Admin Menu</a>
        </div>
    </div>
</div>

</body>
</html>