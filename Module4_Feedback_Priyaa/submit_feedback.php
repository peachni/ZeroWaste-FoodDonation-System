<?php
// ==========================================
// submit_feedback.php
// Handles the database INSERT for Module 4
// ==========================================
include 'db_connect.php'; // Your local database connection

// Dashboard URL - To go back to Syakur's Module 1
$dashboard_url = "http://10.175.254.163:3000/doneeMenu.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. RECEIVE AND SANITIZE INPUTS
    // We use strings for IDs because some are VARCHAR in your system
    $donee_id       = filter_input(INPUT_POST, 'donee_id', FILTER_SANITIZE_SPECIAL_CHARS);
    $admin_id       = filter_input(INPUT_POST, 'admin_id', FILTER_SANITIZE_SPECIAL_CHARS);
    $foodlist_id    = filter_input(INPUT_POST, 'foodlist_id', FILTER_SANITIZE_SPECIAL_CHARS);
    $rating         = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    
    $quality_status = $conn->real_escape_string($_POST['quality_status'] ?? ''); 
    $comments       = $conn->real_escape_string($_POST['comments'] ?? '');
    
    // ERD Column: Date_Submitted
    $date_submitted = date('Y-m-d H:i:s');

    // CSS Styling for the result page
    echo "<style>
            body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; max-width: 400px; border-top: 6px solid #e67e22; }
            .btn { display: inline-block; margin-top: 25px; padding: 12px 25px; background: #e67e22; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; transition: 0.3s; }
            .btn:hover { background: #d35400; }
          </style>";

    // 2. VALIDATION
    if (!$donee_id || !$foodlist_id || !$rating) {
        echo "<div class='card'>
                <h2 style='color:#e74c3c;'>❌ Submission Failed</h2>
                <p>Missing required data. Please make sure you selected a food item and a star rating.</p>
                <a href='javascript:history.back()' class='btn' style='background:#95a5a6;'>Go Back to Form</a>
              </div>";
        exit();
    }

    // 3. SQL INSERT (Strictly following your ERD)
    $sql = "INSERT INTO Feedback (Food_Quality_Rating, Quality_Status, Comments, Date_Submitted, Donee_ID, FoodList_ID, Admin_ID) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    // Type mapping: i=int, s=string
    // rating(i), status(s), comments(s), date(s), donee(s), foodlist(s), admin(s)
    $stmt->bind_param("issssss", $rating, $quality_status, $comments, $date_submitted, $donee_id, $foodlist_id, $admin_id);

    echo "<div class='card'>";
    if ($stmt->execute()) {
        echo "<h2 style='color:#27ae60;'>✅ Feedback Recorded</h2>";
        echo "<p>Your quality report for item <b>$foodlist_id</b> has been successfully recorded and will be reviewed by admin.</p>";
        echo "<a href='$dashboard_url' class='btn'>Back to Dashboard</a>";
    } else {
        echo "<h2 style='color:#e74c3c;'>❌ Database Error</h2>";
        echo "<p>Could not save feedback: " . $stmt->error . "</p>";
        echo "<a href='$dashboard_url' class='btn'>Back to Dashboard</a>";
    }
    echo "</div>";

    $stmt->close();
    $conn->close();
} else {
    // Redirect if they try to access this file directly without submitting the form
    header("Location: feedback_form.php");
    exit();
}
?>