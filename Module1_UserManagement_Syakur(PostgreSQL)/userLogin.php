<?php
include 'dbconnect.php';
session_start();

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // =========================
    // CHECK DONOR FIRST
    // =========================
    $query_donor = pg_query_params(
        $conn,
        "SELECT * FROM donor WHERE donor_username = $1",
        [$username]
    );

    if ($query_donor && pg_num_rows($query_donor) === 1) {
        $row = pg_fetch_assoc($query_donor);

        // ✅ ONLY CHANGE IS HERE
        if (password_verify($password, $row['donor_pass'])) {
            session_regenerate_id(true);
            $_SESSION['userID'] = $row['donor_id'];
            $_SESSION['role'] = 'Donor';
            $_SESSION['email'] = $row['donor_email'] ?? $row['donor_username'];
            $_SESSION['name'] = $row['donor_name'];
            header("Location: donorMenu.php");
            exit;
        } else {
            $_SESSION['login_error'] = "Incorrect username or password!";
            header("Location: userLogin.php");
            exit;
        }
    }

    // =========================
    // CHECK DONEE
    // =========================
    $query_donee = pg_query_params(
        $conn,
        "SELECT * FROM donee WHERE donee_username = $1",
        [$username]
    );

    if ($query_donee && pg_num_rows($query_donee) === 1) {
        $row = pg_fetch_assoc($query_donee);

        // ✅ ONLY CHANGE IS HERE
        if (password_verify($password, $row['donee_pass'])) {
            session_regenerate_id(true);
            $_SESSION['userID'] = $row['donee_id'];
            $_SESSION['role'] = 'Donee';
            $_SESSION['email'] = $row['donee_email'] ?? $row['donee_username'];
            $_SESSION['name'] = $row['donee_name'];
            header("Location: doneeMenu.php");
            exit;
        } else {
            $_SESSION['login_error'] = "Incorrect username or password!";
            header("Location: userLogin.php");
            exit;
        }
    }

    $_SESSION['login_error'] = "Incorrect username or password!";
    header("Location: userLogin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Login | Zero Hunger</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="photo/SDG2Logo.png">

<style>
/* BODY & BACKGROUND */
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: linear-gradient(135deg, #f39c12, #e67e22);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}

/* WRAPPER */
.wrapper {
    width: 900px;
    height: 550px;
    background: white;
    border-radius: 20px;
    display: flex;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0,0,0,0.3);
    animation: fadeIn 1s ease forwards;
}

/* LEFT PANEL LOGIN */
.login-section {
    width: 45%;
    padding: 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    transform: translateX(-50px);
    opacity: 0;
    animation: slideInLeft 1s forwards;
    animation-delay: 0.3s;
}

.login-section img {
    width: 60px;
    display: block;
    margin-bottom: 20px;
    animation: popUp 0.6s forwards;
}

.login-section h2 {
    color: #e67e22;
    font-size: 28px;
    margin-bottom: 10px;
    font-weight: bold;
    transform: scale(0);
    animation: popUp 0.6s forwards;
    animation-delay: 0.2s;
}

.subtitle {
    font-size: 14px;
    color: #555;
    margin-bottom: 30px;
    opacity: 0;
    animation: fadeIn 0.8s forwards;
    animation-delay: 0.4s;
}

/* INPUTS & BUTTONS */
input {
    width: 100%;
    padding: 14px;
    margin-bottom: 18px;
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 14px;
    transition: 0.3s;
}

input:focus {
    outline: none;
    border-color: #e67e22;
    box-shadow: 0 0 8px rgba(230,126,34,0.5);
    transform: scale(1.02);
}

button {
    width: 100%;
    padding: 14px;
    border-radius: 8px;
    border: none;
    background: #e67e22;
    color: white;
    font-weight: bold;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
}

button:hover {
    background: #d35400;
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.register-btn {
    background: #f39c12;
    margin-top: 10px;
}

.register-btn:hover {
    background: #e67e22;
}

/* ERROR MESSAGE */
.error-message {
    background: #fdecea;
    color: #c0392b;
    padding: 10px;
    border-radius: 6px;
    font-size: 13px;
    margin-bottom: 15px;
    transform: translateY(-10px);
    opacity: 0;
    animation: fadeIn 0.5s forwards;
    animation-delay: 0.5s;
}

a {
    display: block;
    margin-top: 18px;
    font-size: 13px;
    color: #e67e22;
    text-decoration: none;
    transition: 0.3s;
}

a:hover { text-decoration: underline; transform: scale(1.05); }

/* RIGHT PANEL IMAGE */
.image-section {
    width: 55%;
    background: linear-gradient(rgba(230,126,34,0.6), rgba(230,126,34,0.6)),
                url('photo/login.jpeg');
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
    padding: 40px;
    transform: translateX(50px);
    opacity: 0;
    animation: slideInRight 1s forwards;
    animation-delay: 0.6s;
}

.image-section h1 {
    font-size: 28px;
    line-height: 1.3;
    transform: scale(0);
    animation: popUp 0.6s forwards;
    animation-delay: 0.7s;
}

.image-section p {
    font-size: 15px;
    max-width: 350px;
    margin-top: 15px;
    opacity: 0;
    animation: fadeIn 0.8s forwards;
    animation-delay: 0.9s;
}

/* ANIMATIONS */
@keyframes fadeIn {
    to { opacity: 1; }
}

@keyframes slideInLeft {
    to { opacity: 1; transform: translateX(0); }
}

@keyframes slideInRight {
    to { opacity: 1; transform: translateX(0); }
}

@keyframes popUp {
    0% { transform: scale(0); opacity: 0; }
    70% { transform: scale(1.2); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}

/* MOBILE RESPONSIVE */
@media (max-width: 900px) {
    .wrapper { flex-direction: column; width: 90%; height: auto; }
    .login-section, .image-section { width: 100%; transform: translateX(0); animation: none; }
    .image-section { padding: 30px 20px; height: 300px; }
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- LOGIN FORM -->
    <div class="login-section">
        <img src="photo/SDG2Logo.png" alt="SDG 2 Logo">
        <h2>Zero Hunger System</h2>
        <div class="subtitle">SDG 2 · Food Donation Platform</div>

        <?php
        if (isset($_SESSION['login_error'])) {
            echo '<div class="error-message">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
            unset($_SESSION['login_error']);
        }
        ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>

        <button class="register-btn" onclick="window.location.href='userRegister.php'">
            Register New Account
        </button>

        <a href="adminLogin.php">Admin Login</a>
    </div>

    <!-- RIGHT IMAGE PANEL -->
    <div class="image-section">
        <div>
            <h1>Together Against Hunger</h1>
            <p>Reducing food waste and supporting communities through a connected donation system.</p>
        </div>
    </div>

</div>

</body>
</html>
