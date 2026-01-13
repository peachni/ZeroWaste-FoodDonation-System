<?php
include 'dbconnect.php';
session_start();

// -------------------------
// 1️⃣ SESSION PROTECTION
// -------------------------
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'Donor') {
    header("Location: userLogin.php");
    exit();
}

// -------------------------
// 2️⃣ SESSION VARIABLES
// -------------------------
$donor_id = $_SESSION['userID'];
$donor_email = $_SESSION['email'] ?? $_SESSION['name'] ?? 'User';
$donor_name = $_SESSION['name'] ?? 'User';

// ==============================================
// 3️⃣ FETCH DONOR INFO
// ==============================================
$fetch_query = "SELECT * FROM donor WHERE donor_id = $1";
$result = pg_query_params($conn, $fetch_query, array($donor_id));

if (!$result) {
    die("Query failed: " . pg_last_error($conn));
}

$donor = pg_fetch_assoc($result);

// ==============================================
// 4️⃣ HANDLE FORM SUBMISSION
// ==============================================
if (isset($_POST['save'])) {
    $name    = trim($_POST['name']);
    $type    = $_POST['type'];
    $company = trim($_POST['company']);
    $contact = trim($_POST['contact']);
    $email   = trim($_POST['email']);
    $address = trim($_POST['address']);
    $city    = trim($_POST['city']);

    $update_query = "
        UPDATE donor
        SET donor_name     = $1,
            donor_type     = $2,
            company_name   = $3,
            contact_number = $4,
            email          = $5,
            address        = $6,
            city           = $7
        WHERE donor_id = $8
    ";

    $update_result = pg_query_params($conn, $update_query, array(
        $name, $type, $company, $contact, $email, $address, $city, $donor_id
    ));

    if ($update_result) {
        // update session for dashboard
        $_SESSION['name']  = $name;
        $_SESSION['email'] = $email;

        echo "<script>alert('Profile updated successfully!'); window.location='donorProfile.php';</script>";
    } else {
        echo "<script>alert('Update failed: " . pg_last_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Donor Profile | Zero Hunger</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #e67e22;
    --secondary: #f39c12;
    --success: #27ae60;
    --bg: #fff5e6;
    --card-bg: #ffffff;
    --shadow: rgba(0,0,0,0.1);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Roboto', sans-serif;
}

body {
    background: var(--bg);
    color: #333;
}

/* Header */
header {
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    color: white;
    padding: 20px 30px;
    text-align: center;
    font-size: 1.5rem;
    font-weight: 600;
    box-shadow: 0 4px 15px var(--shadow);
}

/* Container */
.container {
    max-width: 600px;
    margin: 40px auto;
    padding: 0 20px;
}

/* Card */
.card {
    background: var(--card-bg);
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 25px var(--shadow);
    animation: fadeIn 0.8s ease forwards;
}

.card h2 {
    color: var(--primary);
    margin-bottom: 20px;
    text-align: center;
    font-weight: 700;
    animation: popUp 0.6s forwards;
}

.form-label {
    font-weight: 500;
    margin-bottom: 5px;
}

input, select {
    width: 100%;
    padding: 12px 14px;
    margin-bottom: 18px;
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 14px;
    transition: all 0.3s;
}

input:focus, select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 8px rgba(230,126,34,0.3);
}

/* Buttons */
button, .back-btn {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: none;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
}

button {
    background: var(--success);
    color: white;
    margin-bottom: 10px;
}
button:hover {
    background: #1e8449;
    transform: scale(1.03);
}

.back-btn {
    background: #ccc;
    color: #333;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}
.back-btn:hover {
    background: #b3b3b3;
    transform: scale(1.03);
}

/* Animations */
@keyframes fadeIn {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}

@keyframes popUp {
    0% { transform: scale(0); opacity: 0; }
    70% { transform: scale(1.2); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}
</style>
</head>
<body>

<header>Donor Profile</header>

<div class="container">
    <div class="card">
        <h2>Edit Your Profile</h2>
        <form method="POST">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($donor['donor_name']) ?>" required>

            <label class="form-label">Donor Type</label>
            <select name="type" required>
                <option value="Individual" <?= ($donor['donor_type'] === "Individual" ? "selected" : "") ?>>Individual</option>
                <option value="Organizational" <?= ($donor['donor_type'] === "Organizational" ? "selected" : "") ?>>Organizational</option>
            </select>

            <label class="form-label">Company Name</label>
            <input type="text" name="company" value="<?= htmlspecialchars($donor['company_name']) ?>">

            <label class="form-label">Contact Number</label>
            <input type="text" name="contact" value="<?= htmlspecialchars($donor['contact_number']) ?>" required>

            <label class="form-label">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($donor['email']) ?>" required>

            <label class="form-label">Address</label>
            <input type="text" name="address" value="<?= htmlspecialchars($donor['address']) ?>" required>

            <label class="form-label">City</label>
            <input type="text" name="city" value="<?= htmlspecialchars($donor['city']) ?>" required>

            <button type="submit" name="save">Save Changes</button>
            <a href="donorMenu.php" class="back-btn">Back to Dashboard</a>
        </form>
    </div>
</div>

</body>
</html>
