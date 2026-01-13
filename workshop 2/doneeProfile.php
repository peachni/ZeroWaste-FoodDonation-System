<?php
include 'dbconnect.php';
session_start();

// -------------------------
// 1️⃣ SESSION PROTECTION
// -------------------------
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'Donee') {
    header("Location: userLogin.php");
    exit();
}

// -------------------------
// 2️⃣ SESSION VARIABLES
// -------------------------
$donee_id = $_SESSION['userID'];
$donee_email = $_SESSION['email'] ?? $_SESSION['name'] ?? 'User';
$donee_name = $_SESSION['name'] ?? 'User';

// ==============================================
// 3️⃣ FETCH DONEE INFO
// ==============================================
$fetch_query = "SELECT * FROM donee WHERE donee_id = $1";
$result = pg_query_params($conn, $fetch_query, array($donee_id));

if (!$result) {
    die("Query failed: " . pg_last_error($conn));
}

$donee = pg_fetch_assoc($result);

// ==============================================
// 4️⃣ HANDLE FORM SUBMISSION
// ==============================================
if (isset($_POST['save'])) {
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);

    $update_query = "
        UPDATE donee 
        SET donee_name = $1, 
            contact_number = $2, 
            email = $3, 
            address = $4, 
            city = $5 
        WHERE donee_id = $6
    ";

    $update_result = pg_query_params($conn, $update_query, array(
        $name, $contact, $email, $address, $city, $donee_id
    ));

    if ($update_result) {
        $_SESSION['name']  = $name;
        $_SESSION['email'] = $email;
        echo "<script>alert('Profile updated successfully!'); window.location='doneeProfile.php';</script>";
    } else {
        echo "<script>alert('Update failed: " . pg_last_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Donee Profile | Zero Hunger</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #f39c12; /* Orange */
    --secondary: #e67e22; /* Darker orange */
    --success: #27ae60; /* Green Save button */
    --bg: #fdf6e3;
    --card-bg: #ffffff;
    --shadow: rgba(0,0,0,0.15);
}

* { box-sizing: border-box; margin:0; padding:0; font-family: 'Roboto', sans-serif; }
body { background: var(--bg); color: #333; }

/* Header */
header {
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    color: white;
    padding: 20px 30px;
    text-align: center;
    font-size: 1.6rem;
    font-weight: 600;
    box-shadow: 0 4px 15px var(--shadow);
    border-radius: 0 0 12px 12px;
    margin-bottom: 40px;
}

/* Container */
.container { max-width: 600px; margin: 0 auto; padding: 0 20px; }

/* Card */
.card {
    background: var(--card-bg);
    padding: 35px 30px;
    border-radius: 15px;
    box-shadow: 0 12px 28px var(--shadow);
    animation: fadeIn 1s ease forwards;
}

.card h2 {
    color: var(--primary);
    margin-bottom: 25px;
    text-align: center;
    font-weight: 700;
    font-size: 1.8rem;
    animation: popUp 0.8s ease forwards;
}

.form-label { font-weight: 500; margin-bottom: 5px; }
input, select {
    width: 100%; padding: 12px 14px; margin-bottom: 18px;
    border-radius: 8px; border: 1px solid #ddd; font-size: 14px;
    transition: all 0.3s;
}
input:focus, select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 10px rgba(243,156,18,0.4);
}

/* Buttons */
button, .back-btn {
    width: 100%; padding: 12px; border-radius: 8px; border: none;
    font-size: 15px; font-weight: 500; cursor: pointer; transition: all 0.3s;
}

button { background: var(--success); color: white; margin-bottom: 12px; }
button:hover { background: #1e8449; transform: scale(1.05); }

.back-btn {
    background: #ccc; color: #333; text-decoration: none; display: inline-block;
    text-align: center; padding: 12px;
}
.back-btn:hover { background: #b3b3b3; transform: scale(1.03); }

/* Animations */
@keyframes fadeIn {
    0% { opacity: 0; transform: translateY(40px); }
    100% { opacity: 1; transform: translateY(0); }
}

@keyframes popUp {
    0% { transform: scale(0.6); opacity: 0; }
    70% { transform: scale(1.15); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}
</style>
</head>
<body>

<header>Donee Profile</header>

<div class="container">
    <div class="card">
        <h2>Edit Your Profile</h2>
        <form method="POST">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($donee['donee_name']) ?>" required>

            <label class="form-label">Contact Number</label>
            <input type="text" name="contact" value="<?= htmlspecialchars($donee['contact_number']) ?>" required>

            <label class="form-label">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($donee['email']) ?>" required>

            <label class="form-label">Address</label>
            <input type="text" name="address" value="<?= htmlspecialchars($donee['address']) ?>" required>

            <label class="form-label">City</label>
            <input type="text" name="city" value="<?= htmlspecialchars($donee['city']) ?>" required>

            <button type="submit" name="save">Save Changes</button>
            <a href="doneeMenu.php" class="back-btn">Back to Dashboard</a>
        </form>
    </div>
</div>

</body>
</html>
