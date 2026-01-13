<?php
// admin_feedback_manager.php
include 'dbconnect.php'; // Your local database connection

// 1. HANDLE POST REQUEST (When Admin clicks "Submit Reply")
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_reply'])) {
    $feedback_id = $_POST['feedback_id'];
    $reply_text  = $conn->real_escape_string($_POST['admin_reply']);
    $reply_date  = date('Y-m-d H:i:s');

    // Update the record in YOUR local MySQL database
    $update_sql = "UPDATE feedback SET Admin_Reply = '$reply_text', Reply_Date = '$reply_date' WHERE Feedback_ID = $feedback_id";
    
    if ($conn->query($update_sql)) {
        echo "<script>alert('Reply sent successfully!'); window.location.href='admin_feedback_manager.php';</script>";
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

// 2. FETCH ALL FEEDBACKS FOR THE ADMIN TO SEE
$sql = "SELECT * FROM feedback ORDER BY Date_Submitted DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Feedback Management</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; padding: 40px; }
        .container { max-width: 900px; margin: auto; }
        h2 { color: #2c3e50; border-bottom: 3px solid #e67e22; padding-bottom: 10px; }
        
        .feedback-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .info-row { display: flex; justify-content: space-between; font-size: 13px; color: #7f8c8d; margin-bottom: 10px; }
        .rating { color: #f1c40f; font-weight: bold; font-size: 18px; }
        .comment-box { background: #fdf2e9; padding: 15px; border-radius: 8px; border-left: 5px solid #e67e22; margin: 10px 0; }
        
        /* Reply Form Styling */
        .reply-section { margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        textarea { width: 100%; height: 70px; padding: 10px; border: 1px solid #ddd; border-radius: 6px; resize: none; box-sizing: border-box; }
        .btn-reply { background: #27ae60; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; margin-top: 10px; font-weight: bold; }
        .btn-reply:hover { background: #219150; }
        
        .already-replied { background: #ebf5fb; color: #2980b9; padding: 10px; border-radius: 6px; font-size: 14px; font-style: italic; }
    </style>
</head>
<body>

<div class="container">
    <h2>⭐ Donee Feedback Management</h2>
    <p>Admin Portal: Monitoring Quality Reports from Module 4</p>

    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="feedback-card">
                <div class="info-row">
                    <span><strong>Donee ID:</strong> <?php echo $row['Donee_ID']; ?></span>
                    <span><strong>Date:</strong> <?php echo date('d M Y', strtotime($row['Date_Submitted'])); ?></span>
                </div>
                
                <div class="rating"><?php echo str_repeat("★", $row['Food_Quality_Rating']); ?></div>
                <div class="comment-box">
                    <strong>User Comment:</strong><br>
                    "<?php echo htmlspecialchars($row['Comments']); ?>"
                </div>

                <div class="reply-section">
                    <?php if (empty($row['Admin_Reply'])): ?>
                        <!-- Form to submit a new reply -->
                        <form method="POST">
                            <input type="hidden" name="feedback_id" value="<?php echo $row['Feedback_ID']; ?>">
                            <textarea name="admin_reply" placeholder="Type your response to the Donee here..." required></textarea>
                            <button type="submit" name="submit_reply" class="btn-reply">Send Reply</button>
                        </form>
                    <?php else: ?>
                        <!-- Show the reply that was already sent -->
                        <div class="already-replied">
                            <strong>My Response:</strong><br>
                            <?php echo htmlspecialchars($row['Admin_Reply']); ?>
                            <div style="font-size: 11px; margin-top: 5px;">Sent on: <?php echo $row['Reply_Date']; ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No feedback records found in the database.</p>
    <?php endif; ?>
</div>

</body>
</html>