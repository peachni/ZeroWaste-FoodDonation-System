<?php
include 'dbconnect.php';
session_start();

if (isset($_POST['login'])) {

    // Escape input
    $username = pg_escape_string($conn, $_POST['username']);
    $password = pg_escape_string($conn, $_POST['password']);

    // Query PostgreSQL
    $query = pg_query(
        $conn,
        "SELECT * FROM admin
         WHERE admin_username = '$username'
         AND admin_pass = '$password'"
    );

    if (!$query) {
        die("Database query failed: " . pg_last_error($conn));
    }

    if (pg_num_rows($query) > 0) {

        $row = pg_fetch_assoc($query);

        $_SESSION['admin_id'] = $row['admin_id'];
        $_SESSION['admin_username'] = $row['admin_username'];

        echo "<script>
                alert('Welcome Admin!');
                window.location='adminMenu.php';
              </script>";
        exit;

    } else {

        echo "<script>
                alert('Incorrect admin username or password!');
                window.location='adminLogin.php';
              </script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style>
        /* Background & Font */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #FFE0B2, #FFB74D);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Container */
        .container {
            background: #fff3e0;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-align: center;
            width: 360px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }

        /* Header */
        .container h2 {
            margin-bottom: 25px;
            color: #FF6F00;
            font-size: 28px;
            font-weight: 700;
        }

        /* Inputs */
        input {
            margin: 12px 0;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid #FFB74D;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
        }

        input:focus {
            border-color: #FF6F00;
            box-shadow: 0 0 8px #FFB74D;
        }

        /* Button */
        button {
            margin-top: 15px;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            border: none;
            background: #FF6F00;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        button:hover {
            background: #E65100;
            transform: scale(1.05);
        }

        /* Link */
        a {
            display: inline-block;
            margin-top: 18px;
            color: #E65100;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
        }

        a:hover {
            color: #FF6F00;
            text-decoration: underline;
        }

        /* Mobile Responsive */
        @media (max-width: 400px) {
            .container {
                width: 90%;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Admin Login</h2>
    <form method="POST" action="adminLogin.php">
        <input type="text" name="username" placeholder="Admin Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>
    <p><a href="userLogin.php">Go to User Login</a></p>
</div>

</body>
</html>
