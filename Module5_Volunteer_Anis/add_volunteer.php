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
   2️⃣ HANDLE FORM SUBMISSION (MariaDB)
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = mysqli_real_escape_string($conn, $_POST['name']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']); 
    $contact  = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $vehicle  = mysqli_real_escape_string($conn, $_POST['vehicle_type']);
    $area     = mysqli_real_escape_string($conn, $_POST['area_assigned']);
    $status   = mysqli_real_escape_string($conn, $_POST['availability_status']);

    // Logic: Insert new record. donee_id is NULL because they haven't been assigned a task yet.
    $sql = "INSERT INTO volunteer 
            (name, email, contact_number, vehicle_type, area_assigned, availability_status, donee_id)
            VALUES 
            ('$name', '$email', '$contact', '$vehicle', '$area', '$status', NULL)";

    if (mysqli_query($conn, $sql)) {
        // Redirect with a success message for the dashboard notification
        header("Location: volunteer.php?msg=added");
        exit;
    } else {
        $error_msg = "Database Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Volunteer | Zero Hunger Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
        :root { --primary: #f39c12; --dark-orange: #a04000; --bg: #fdf6e3; --text-dark: #2c3e50; --shadow: rgba(0,0,0,0.08); }
        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text-dark); }
        header { background: linear-gradient(90deg, #f39c12, #f1c40f); padding: 20px 40px; box-shadow: 0 4px 10px var(--shadow); }
        header h1 { color: white; margin: 0; font-size: 1.4rem; font-weight: 800; }
        
        .main-wrapper { max-width: 700px; margin: 30px auto; padding: 0 20px; }
        .form-card { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 10px 30px var(--shadow); border-top: 5px solid var(--primary); }
        
        .admin-info { font-size: 12px; color: #7f8c8d; text-align: right; margin-bottom: 10px; font-weight: 600; }
        .form-header { background: #fef9e7; padding: 15px; border-radius: 8px; margin-bottom: 25px; font-size: 13px; color: var(--dark-orange); border: 1px solid #f9e79f; font-weight: 800; text-align: center; }
        
        .err-banner { background: #fdedec; color: #e74c3c; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: bold; border: 1px solid #fadbd8; }

        label { display: block; font-size: 11px; text-transform: uppercase; font-weight: 800; color: #7f8c8d; margin-bottom: 8px; letter-spacing: 0.5px; }
        input, select { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px; margin-bottom: 20px; box-sizing: border-box; transition: 0.3s; }
        input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 8px rgba(243, 156, 18, 0.2); }
        
        .readonly-field { background-color: #f9f9f9; color: #95a5a6; cursor: not-allowed; border-style: dashed; }
        .btn-save { width: 100%; background: var(--primary); color: white; border: none; padding: 16px; border-radius: 8px; font-weight: 800; cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; }
        .btn-save:hover { background: var(--dark-orange); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3); }
        
        .footer-links { display: flex; justify-content: space-between; margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px; }
        .footer-links a { text-decoration: none; color: #7f8c8d; font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>

<header><h1>Zero Hunger Hub</h1></header>

<div class="main-wrapper">
    <div class="admin-info">
        Acting Admin: <strong><?= htmlspecialchars($admin_username) ?></strong>
    </div>

    <div class="form-card">
        <div class="form-header">
            <i class="fas fa-user-plus"></i> REGISTER NEW VOLUNTEER
        </div>

        <?php if(isset($error_msg)): ?>
            <div class="err-banner"><i class="fas fa-exclamation-circle"></i> <?= $error_msg ?></div>
        <?php endif; ?>

        <form method="POST">
            <label><i class="fas fa-magic"></i> ID Generation</label>
            <input type="text" value="ASSIGNED BY DATABASE TRIGGER" class="readonly-field" readonly>

            <label>Full Name</label>
            <input type="text" name="name" placeholder="Enter volunteer's full name" required>

            <label>Email Address</label>
            <input type="email" name="email" placeholder="email@example.com">

            <div style="display:flex; gap:15px;">
                <div style="flex:1;">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" placeholder="e.g. 012-3456789" required>
                </div>
                <div style="flex:1;">
                    <label>Vehicle Type</label>
                    <select name="vehicle_type">
                        <option value="Motorcycle">Motorcycle</option>
                        <option value="Car">Car</option>
                        <option value="Van">Van</option>
                        <option value="Truck">Truck</option>
                    </select>
                </div>
            </div>

            <label>Operation Area</label>
            <input type="text" name="area_assigned" placeholder="e.g. Malacca City" required>

            <label>Initial Status</label>
            <select name="availability_status">
                <option value="Available">Available (Ready for Tasks)</option>
                <option value="Inactive">Inactive (Not Currently Working)</option>
            </select>

            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> Save Volunteer Record
            </button>
        </form>

        <div class="footer-links">
            <a href="volunteer.php"><i class="fas fa-arrow-left"></i> Cancel</a>
            <a href="http://10.175.254.163:3000/adminMenu.php"><i class="fas fa-home"></i> Admin Menu</a>
        </div>
    </div>
</div>

</body>
</html>