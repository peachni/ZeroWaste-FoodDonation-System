<?php
session_start();

// -------------------------
// 1️⃣ SESSION PROTECTION
// -------------------------
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'Donee') {
    header("Location: userLogin.php");
    exit();
}

// -------------------------
// 2️⃣ Assign session vars
// -------------------------
$donee_id = $_SESSION['userID'];
$donee_email = $_SESSION['email'] ?? $_SESSION['name'] ?? 'User';
$donee_name = $_SESSION['name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Donee Dashboard | Zero Hunger</title>

<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

<!-- Font Awesome (for icons) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root {
    --primary: #f39c12;
    --secondary: #f1c40f;
    --success: #27ae60;
    --text-dark: #333;
    --shadow: rgba(0,0,0,0.35);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Roboto', sans-serif;
}

body {
    background: linear-gradient(to bottom, #fff3e0, #fff8f0);
    min-height: 100vh;
    animation: pageFade 0.8s ease forwards;
}

@keyframes pageFade {
    from { opacity: 0; transform: translateY(15px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ================= HEADER ================= */

.top-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: linear-gradient(135deg, #f39c12, #f1c40f);
    padding: 22px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 12px 35px rgba(0,0,0,0.35);
    color: white;
}

.top-header h1 {
    font-size: 1.9rem;
    font-weight: 700;
}

/* ================= HEADER BUTTONS ================= */

.header-buttons {
    display: flex;
    align-items: center;
    gap: 16px;
}

/* Profile Button */
.profile-btn {
    width: 46px;
    height: 46px;
    background: rgba(255,255,255,0.15);
    border: 2px solid white;
    color: white;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.25s ease;
}

.profile-btn:hover {
    background: white;
    color: #f39c12;
    transform: scale(1.1) rotate(6deg);
}

/* Logout Button */
.logout-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #c0392b;
    color: white;
    padding: 12px 22px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.25s ease;
}

.logout-btn:hover {
    background: #e74c3c;
    transform: translateY(-2px);
}


/* ================= LAYOUT ================= */

.container {
    max-width: 1400px;
    margin: 30px auto;
    padding: 0 30px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 35px;
}

@media (max-width: 900px) {
    .container { grid-template-columns: 1fr; }
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
    to   { opacity: 1; transform: translateY(0); }
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

/* DARK OVERLAY */
.module-card::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.85), rgba(0,0,0,0.25));
    pointer-events: none;
}

/* ================= CLICKABLE BUTTON ================= */

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
    padding: 0;
}

/* CONTENT ABOVE BUTTON */
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
}

.module-content span {
    display: inline-block;
    margin-top: 18px;
    font-weight: 700;
    animation: arrowMove 1s infinite alternate;
}

@keyframes arrowMove {
    from { transform: translateX(0); }
    to   { transform: translateX(6px); }
}

/* ================= BACKGROUNDS ================= */

.donation { background: url("photo/donation.jpeg") center/cover no-repeat; }
.feedback { background: url("photo/feedback.jpeg") center/cover no-repeat; }
.reply    { background: url("photo/reply.jpeg") center/cover no-repeat; }
</style>
</head>

<body>

<header class="top-header">
    <h1>Donee Dashboard</h1>

    <div class="header-buttons">
        <!-- Profile -->
        <button 
            class="profile-btn" 
            title="Edit Profile"
            onclick="window.location.href='doneeProfile.php'">
            <i class="fa-solid fa-user-gear"></i>
        </button>

        <!-- Logout -->
        <a href="userLogOut.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </div>
</header>


<div class="container">

    <div class="welcome-section">
        <h2>Welcome back, <?= htmlspecialchars($donee_name); ?> 👋</h2>
        <p>Username: <strong><?= htmlspecialchars($donee_email); ?></strong></p>
        <p>Donee ID: <strong><?= htmlspecialchars($donee_id); ?></strong></p>
    </div>

    <!-- Donation -->
    <div class="module-card donation">
        <form action="http://10.175.254.3/mariadb_test/donation.php" method="POST">
            <input type="hidden" name="donee_id" value="<?= htmlspecialchars($donee_id); ?>">
            <input type="hidden" name="user_name" value="<?= htmlspecialchars($donee_name); ?>">
            <input type="hidden" name="role" value="donee">
            <button type="submit"></button>
            <div class="module-content">
                <h3>🎁 Donation Management</h3>
                <p>Browse available donations.</p>
                <span>Request →</span>
            </div>
        </form>
    </div>

    <!-- Feedback -->
    <div class="module-card feedback">
        <form action="http://10.175.254.1/FEEDBACK_MODULE/FEEDBACK_FORM/feedback_form.php" method="POST">
            <input type="hidden" name="donee_id" value="<?= htmlspecialchars($donee_id); ?>">
            <input type="hidden" name="donee_name" value="<?= htmlspecialchars($donee_name); ?>">
            <input type="hidden" name="admin_id" value="1">
            <button type="submit"></button>
            <div class="module-content">
                <h3>📝 Submit Feedback</h3>
                <p>Share your experience.</p>
                <span>Submit →</span>
            </div>
        </form>
    </div>

    <!-- Reply -->
    <div class="module-card reply">
        <form action="http://10.175.254.1/feedback_module/FEEDBACK_FORM/view_my_feedback.php" method="POST">
            <input type="hidden" name="donee_id" value="<?= htmlspecialchars($donee_id); ?>">
            <input type="hidden" name="donee_name" value="<?= htmlspecialchars($donee_name); ?>">
            <button type="submit"></button>
            <div class="module-content">
                <h3>📨 Feedback Replies</h3>
                <p>View admin responses.</p>
                <span>View →</span>
            </div>
        </form>
    </div>

</div>

</body>
</html>
