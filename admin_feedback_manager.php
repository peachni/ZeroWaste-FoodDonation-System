<?php
// ==========================================================
// admin_feedback_manager.php
// Module 4: Feedback Management (MySQL + PostgreSQL)
// ==========================================================

include 'db_connect.php'; // Your local MySQL connection
session_start();

// THE ADMIN DASHBOARD URL (Syakur's Module 1)
$admin_dashboard_url = "http://10.175.254.163:3000/adminMenu.php";

// Initialize variables
$pdo_remote = null; 
$remote_error = null;

/**
 * 1. REMOTE CONNECTION TO SYAKUR (Module 1 - PostgreSQL)
 */
$syakur_ip   = "10.175.254.163";
$syakur_db   = "postgres";
$syakur_user = "Cako";
$syakur_pass = "Sy@kur123";

try {
    if (!extension_loaded('pdo_pgsql')) {
        throw new Exception("PostgreSQL driver (pdo_pgsql) not enabled in WAMP.");
    }

    $dsn = "pgsql:host=$syakur_ip;port=5432;dbname=$syakur_db;";
    $pdo_remote = new PDO($dsn, $syakur_user, $syakur_pass, [
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    $remote_error = "Remote Lookup Failed: " . $e->getMessage();
}

/**
 * 2. HANDLE ADMIN REPLY
 */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_reply'])) {
    $fid = $_POST['feedback_id'];
    $reply = $conn->real_escape_string($_POST['admin_reply']);
    $now = date('Y-m-d H:i:s');
    
    $conn->query("UPDATE feedback SET Admin_Reply = '$reply', Reply_Date = '$now' WHERE Feedback_ID = $fid");
    echo "<script>alert('Reply sent successfully!'); window.location.href='admin_feedback_manager.php';</script>";
}

/**
 * 3. HELPER FUNCTION: Live Lookup Name from Syakur's PostgreSQL
 * Corrected spelling: donee_id (2 e's)
 */
function fetchDoneeInfo($id, $pdo) {
    if (!$pdo) return ["donee_name" => "Offline", "donee_username" => "N/A"];
    
    try {
        // We use explicit Integer casting to satisfy PostgreSQL's strict types.
        $clean_id = (int)$id; 

        $stmt = $pdo->prepare("SELECT donee_name, donee_username FROM donee WHERE donee_id = ? LIMIT 1");
        $stmt->execute([$clean_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            return $data;
        } else {
            // Connection is OK, but ID 4 returned no rows. 
            // Most likely Syakur did not click "COMMIT/SAVE" in DBeaver.
            return ["donee_name" => "User ID: $id", "donee_username" => "Not Found in Postgres"];
        }
    } catch (PDOException $e) {
        return ["donee_name" => "Postgres Error", "donee_username" => "Check Column Names"];
    }
}

// 4. FETCH FEEDBACKS FROM LOCAL MYSQL
$local_feedbacks = $conn->query("SELECT * FROM feedback ORDER BY Date_Submitted DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Feedback Monitoring</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7f6; padding: 40px; margin: 0; }
        .container { max-width: 900px; margin: auto; position: relative; }
        .btn-dash { position: absolute; top: 0; right: 0; text-decoration: none; padding: 8px 15px; border-radius: 6px; background: #eee; color: #7f8c8d; font-size: 13px; font-weight: bold; transition: 0.3s; }
        .btn-dash:hover { background: #dfe6e9; color: #2c3e50; }
        h2 { color: #2c3e50; margin-bottom: 5px; }
        .subtitle { color: #e67e22; font-weight: bold; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; display: block; margin-bottom: 30px; }
        .card { background: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-left: 6px solid #e67e22; text-align: left; }
        .user-info { font-weight: bold; color: #2c3e50; display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .username { color: #e67e22; font-size: 13px; font-weight: normal; }
        .date { color: #95a5a6; font-size: 12px; margin-left: auto; }
        .comment-box { background: #fdf2e9; padding: 15px; border-radius: 8px; margin: 15px 0; font-style: italic; border: 1px solid #fad7a0; line-height: 1.5; color: #34495e; }
        textarea { width: 100%; height: 70px; padding: 12px; border-radius: 8px; border: 2px solid #edeff2; box-sizing: border-box; outline: none; margin-top: 10px; }
        .btn-reply { background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; margin-top: 10px; font-weight: bold; }
        .reply-done { background: #ebf5fb; color: #2980b9; padding: 15px; border-radius: 8px; font-size: 14px; border-left: 4px solid #3498db; margin-top: 15px; }
        .error-banner { background: #fff5f5; color: #c53030; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; border: 1px solid #feb2b2; }
    </style>
</head>
<body>

<div class="container">
    <a href="<?php echo $admin_dashboard_url; ?>" class="btn-dash">← Back to Dashboard</a>
    <h2>⭐ Donee Feedback Monitoring</h2>
    <span class="subtitle">Distributed Live Lookup: Windows (MySQL) + Linux (PostgreSQL)</span>

    <?php if ($remote_error): ?>
        <div class="error-banner">⚠️ <strong>Remote Connection Issue:</strong> <?php echo $remote_error; ?></div>
    <?php endif; ?>

    <?php if ($local_feedbacks && $local_feedbacks->num_rows > 0): ?>
        <?php while($row = $local_feedbacks->fetch_assoc()): 
            $remote_user = fetchDoneeInfo($row['Donee_ID'], $pdo_remote);
        ?>
            <div class="card">
                <div class="user-info">
                    👤 <?php echo htmlspecialchars($remote_user['donee_name']); ?>
                    <span class="username">@<?php echo htmlspecialchars($remote_user['donee_username']); ?></span>
                    <div class="date">📅 <?php echo date('d M Y, h:i A', strtotime($row['Date_Submitted'])); ?></div>
                </div>
                
                <div style="font-size: 11px; color:#bdc3c7;">
                    Donee ID: <?php echo $row['Donee_ID']; ?> | Rating: <?php echo str_repeat("★", $row['Food_Quality_Rating']); ?>
                </div>

                <div class="comment-box">
                    "<?php echo htmlspecialchars($row['Comments']); ?>"
                </div>

                <?php if (empty($row['Admin_Reply'])): ?>
                    <form method="POST">
                        <input type="hidden" name="feedback_id" value="<?php echo $row['Feedback_ID']; ?>">
                        <textarea name="admin_reply" placeholder="Type your response to the Donee..." required></textarea>
                        <button type="submit" name="submit_reply" class="btn-reply">Send Official Response</button>
                    </form>
                <?php else: ?>
                    <div class="reply-done">
                        <strong>My Response:</strong><br>
                        <?php echo htmlspecialchars($row['Admin_Reply']); ?>
                        <div style="font-size:10px; margin-top:8px; color:#7f8c8d;">Replied on: <?php echo date('d M Y, h:i A', strtotime($row['Reply_Date'])); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align: center; color: #95a5a6; padding: 50px;">No feedback records found.</p>
    <?php endif; ?>
</div>

</body>
</html>