<?php
session_start();

// -------------------------
// 1️⃣ SESSION PROTECTION
// -------------------------
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'Donor') {
    header("Location: userLogin.php");
    exit();
}

// -------------------------
// 2️⃣ Assign session vars
// -------------------------
$donor_id    = $_SESSION['userID'];
$donor_name  = $_SESSION['name']  ?? 'Donor';
$donor_email = $_SESSION['email'] ?? 'Not Provided';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Donor Dashboard | Zero Hunger</title>

<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root {
    --primary: #e67e22;
    --secondary: #f39c12;
    --shadow: rgba(0,0,0,0.35);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Roboto', sans-serif;
}

body {
    background: linear-gradient(to bottom, #fff3e0, #fff8f0);
    min-height: 100vh;
    animation: pageFade 0.8s ease forwards;
}

@keyframes pageFade {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ================= HEADER ================= */

header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    padding: 24px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 12px 35px rgba(0,0,0,0.35);
    color: white;
}

.header-buttons {
    display: flex;
    gap: 16px;
}

.profile-btn {
    width: 46px;
    height: 46px;
    background: rgba(255,255,255,0.15);
    border: 2px solid white;
    color: white;
    font-size: 18px;
    cursor: pointer;
    transition: 0.25s;
}

.profile-btn:hover {
    background: white;
    color: var(--primary);
    transform: scale(1.1) rotate(6deg);
}

.logout-btn {
    background: #c0392b;
    color: white;
    padding: 12px 22px;
    text-decoration: none;
    font-weight: 600;
    transition: 0.25s;
}

.logout-btn:hover {
    background: #e74c3c;
    transform: translateY(-2px);
}

/* ================= LAYOUT (CENTERED FIX) ================= */

.container {
    max-width: 1400px;
    margin: 30px auto;
    padding: 0 30px;

    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(360px, 440px));
    gap: 40px;
    justify-content: center;
}

@media (max-width: 900px) {
    .container {
        grid-template-columns: 1fr;
    }
}

/* ================= WELCOME ================= */

.welcome-section {
    grid-column: 1 / -1;
    background: white;
    padding: 40px;
    border-left: 12px solid var(--primary);
    box-shadow: 0 20px 45px var(--shadow);
}

/* ================= MODULE CARD ================= */

.module-card {
    position: relative;
    height: 340px;
    overflow: hidden;
    box-shadow: 0 25px 55px rgba(0,0,0,0.35);
    animation: cardEnter 0.9s ease forwards;
}

@keyframes cardEnter {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.module-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 40px 80px rgba(0,0,0,0.5);
}

/* SHIMMER */
.module-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, transparent, rgba(255,255,255,0.25), transparent);
    transform: translateX(-120%);
    pointer-events: none;
}

.module-card:hover::before {
    animation: shimmer 1.2s ease;
}

@keyframes shimmer {
    to { transform: translateX(120%); }
}

/* OVERLAY */
.module-card::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.85), rgba(0,0,0,0.25));
    pointer-events: none;
}

/* CLICKABLE */
.module-card form {
    height: 100%;
}

.module-card button {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    background: transparent;
    border: none;
    cursor: pointer;
    z-index: 3;
}

/* TEXT */
.module-content {
    position: absolute;
    bottom: 32px;
    left: 32px;
    right: 32px;
    color: white;
    z-index: 4;
    pointer-events: none;
}

.module-content h3 {
    font-size: 1.7rem;
    margin-bottom: 8px;
}

.module-content span {
    display: inline-block;
    margin-top: 18px;
    font-weight: 700;
    animation: arrowMove 1s infinite alternate;
}

@keyframes arrowMove {
    from { transform: translateX(0); }
    to { transform: translateX(6px); }
}

/* ================= BACKGROUNDS ================= */

.add-food {
    background: url("photo/add_donation.jpeg") center/cover no-repeat;
}

.view-food {
    background: url("photo/view_donation.jpeg") center/cover no-repeat;
}
</style>
</head>

<body>

<header>
    <h1>Donor Dashboard</h1>
    <div class="header-buttons">
        <button class="profile-btn" onclick="window.location.href='donorProfile.php'">
            <i class="fa-solid fa-user-gear"></i>
        </button>
        <a href="userLogOut.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</header>

<div class="container">

    <div class="welcome-section">
        <h2>Welcome back, <?= htmlspecialchars($donor_name); ?> 👋</h2>
        <p>Username: <strong><?= htmlspecialchars($donor_email); ?></strong></p>
        <p>Donor ID: <strong><?= htmlspecialchars($donor_id); ?></strong></p>
    </div>

    <!-- ADD FOOD -->
    <div class="module-card add-food">
        <form action="http://10.175.254.152:3000/AddItemForm.php" method="GET">
            <input type="hidden" name="role" value="donor">
            <input type="hidden" name="donor_id" value="<?= htmlspecialchars($donor_id); ?>">
            <input type="hidden" name="user_name" value="<?= htmlspecialchars($donor_name); ?>">
            <button type="submit"></button>
            <div class="module-content">
                <h3>🍱 Add Food</h3>
                <p>Create a new donation entry.</p>
                <span>Donate →</span>
            </div>
        </form>
    </div>

    <!-- VIEW FOOD -->
    <div class="module-card view-food">
        <form action="http://10.175.254.152:3000/DisplayFoodList.php" method="GET">
            <input type="hidden" name="role" value="donor">
            <input type="hidden" name="donor_id" value="<?= htmlspecialchars($donor_id); ?>">
            <input type="hidden" name="user_name" value="<?= htmlspecialchars($donor_name); ?>">
            <button type="submit"></button>
            <div class="module-content">
                <h3>📋 View Food Donations and manage it</h3>
                <p>See all your donated food.</p>
                <span>Manage →</span>
            </div>
        </form>
    </div>

</div>

</body>
</html>
