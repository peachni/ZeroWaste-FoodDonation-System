<?php
// view_my_feedback.php
session_start();
mysqli_report(MYSQLI_REPORT_OFF);
include 'db_connect.php'; // Your local MySQL connection

// Syakur's Dashboard Address
$dashboard_url = "http://10.175.254.163:3000/doneeMenu.php";

/**
 * 1. THE HETEROGENEOUS HANDSHAKE (POST VERSION)
 * We check if Syakur's dashboard sent a POST request.
 * If yes, we save the user's "Identity Card" to our local memory.
 */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['donee_id'])) {
    $_SESSION['donee_id']   = $_POST['donee_id'];
    $_SESSION['user_name']  = $_POST['donee_name'] ?? "User";
}

/**
 * 2. SECURITY CHECK
 * If no ID is found in the local session, it means the user 
 * didn't come from Syakur's dashboard correctly.
 */
if (!isset($_SESSION['donee_id'])) {
    die("<div style='text-align:center; margin-top:100px; font-family:sans-serif;'>
            <h2 style='color:#e67e22;'>🔒 Access Denied</h2>
            <p>Laptop B (Priyaa) needs an identity token from Laptop A (Syakur).</p>
            <p><strong>To fix:</strong> You MUST click the link on Syakur's Dashboard.</p>
            <br><a href='$dashboard_url' style='color:#3498db; text-decoration:none;'>← Return to Dashboard</a>
         </div>");
}

$donee_id  = $_SESSION['donee_id'];
$display_name = $_SESSION['user_name']; // Caught from Syakur's POST

/**
 * 3. OPTIONAL: LIVE REMOTE LOOKUP TO SYAKUR (Laptop A - PostgreSQL)
 * Only runs if the name is missing or we want to verify it live.
 */
if ($display_name == "User") {
    $syakur_ip   = "10.175.254.163";
    $syakur_db   = "postgres";
    $syakur_user = "Cako";
    $syakur_pass = "Sy@kur123";

    try {
        if (extension_loaded('pdo_pgsql')) {
            $dsn = "pgsql:host=$syakur_ip;port=5432;dbname=$syakur_db;";
            $pdo_remote = new PDO($dsn, $syakur_user, $syakur_pass, [PDO::ATTR_TIMEOUT => 3]);
            
            $stmt = $pdo_remote->prepare("SELECT donee_name FROM donee WHERE donee_id = ? LIMIT 1");
            $stmt->execute([(int)$donee_id]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_data) {
                $display_name = $user_data['donee_name'];
                $_SESSION['user_name'] = $display_name;
            }
        }
    } catch (Exception $e) {
        // Fallback name if remote lookup fails
        $display_name = "Donee #$donee_id";
    }
}

/**
 * 4. FETCH FEEDBACK HISTORY FROM LOCAL MYSQL
 * This query is dynamic. It will work for User 4, User 7, User 8, etc.
 */
$sql = "SELECT * FROM feedback WHERE Donee_ID = '$donee_id' ORDER BY Date_Submitted DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Feedback History</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; padding: 40px; }
        .container { max-width: 700px; margin: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .card { background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 6px solid #e67e22; }
        h2 { color: #2c3e50; margin: 0; }
        .rating { color: #f1c40f; font-size: 18px; margin-bottom: 10px; display: block; }
        .comment { color: #34495e; font-size: 15px; margin: 10px 0; font-style: italic; }
        .date { font-size: 12px; color: #95a5a6; }
        .reply-box { background: #fff3e0; padding: 15px; border-radius: 8px; margin-top: 15px; border: 1px solid #ffe0b2; }
        .reply-box strong { color: #d35400; font-size: 12px; text-transform: uppercase; }
        .btn-dash { text-decoration: none; padding: 10px 20px; border-radius: 6px; border: 1px solid #bdc3c7; color: #7f8c8d; font-weight: bold; font-size: 13px; transition: 0.3s; }
        .btn-dash:hover { background: #eee; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h2>My Feedback History</h2>
            <!-- DYNAMIC DISPLAY: ID is hidden, shows the Name of whoever is logged in -->
            <p style="color: #7f8c8d; margin: 5px 0 0 0;">Records for: <strong><?= htmlspecialchars($display_name); ?></strong></p>
        </div>
        <a href="<?= $dashboard_url; ?>" class="btn-dash">← Dashboard</a>
    </div>

    <hr style="border: 0; border-top: 1px solid #dcdde1; margin-bottom: 30px;">

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="card">
                <span class="rating"><?= str_repeat("★", (int)$row['Food_Quality_Rating']); ?></span>
                <p class="comment">"<?= htmlspecialchars($row['Comments']); ?>"</p>
                <div class="date">Submitted: <?= date('d M Y', strtotime($row['Date_Submitted'])); ?></div>

                <?php if (!empty($row['Admin_Reply'])): ?>
                    <div class="reply-box">
                        <strong>📢 Admin Response:</strong>
                        <p style="margin:5px 0; color: #2c3e50;"><?= htmlspecialchars($row['Admin_Reply']); ?></p>
                        <div class="date">Replied on: <?= date('d M Y', strtotime($row['Reply_Date'])); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 50px; color: #95a5a6; background: white; border-radius: 12px;">
            <p>Hello <?= htmlspecialchars($display_name); ?>, you haven't submitted any feedback yet.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>