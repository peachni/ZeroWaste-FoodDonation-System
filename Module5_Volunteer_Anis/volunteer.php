<?php
session_start();
require_once 'connect.php';

/* ===============================
   1️⃣ HANDSHAKE & SESSION
   =============================== */
if (isset($_GET['role']) && $_GET['role'] === 'admin') {
    $_SESSION['role'] = 'admin';
    $_SESSION['admin_id'] = $_GET['admin_id'] ?? null;
    $_SESSION['admin_username'] = $_GET['admin_username'] ?? 'Admin';
}

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    die("<h2 style='text-align:center;margin-top:50px;color:#e74c3c;'>🔒 Access Denied</h2>");
}

$admin_id = $_SESSION['admin_id']; 
$admin_username = $_SESSION['admin_username'];
$status_msg = ""; // Variable for notification toast

/* ===============================
   2️⃣ HANDLE ACTIONS (CRUD & SYNC)
   =============================== */

// Action: Deactivate Volunteer
if (isset($_GET['deactivate_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['deactivate_id']);
    mysqli_query($conn, "UPDATE volunteer SET availability_status = 'Inactive' WHERE volunteer_id = '$id'");
    header("Location: volunteer.php?msg=deactivated");
    exit;
}

// 🔄 UPDATED ACTION: Complete Task - Remote Sync with Nadia (Laptop B)
if (isset($_GET['complete_id'])) {
    $v_id = mysqli_real_escape_string($conn, $_GET['complete_id']);
    
    // 1. Get Donee ID from your local table
    $res = mysqli_query($conn, "SELECT donee_id FROM volunteer WHERE volunteer_id = '$v_id'");
    $row = mysqli_fetch_assoc($res);
    $d_id = (int)$row['donee_id']; 

    if ($d_id) {
        // 2. CONNECT TO NADIA (Laptop B)
        // IP: 10.175.254.3 | Port: 3307 | User: NadPnya
        $nadia_conn = @new mysqli("10.175.254.3", "NadPnya", "", "zerowaste", 3307);

        if (!$nadia_conn->connect_error) {
            // 3. UPDATE NADIA'S PICKUP TABLE
            // Nadia's TRIGGER handles the donation table update automatically!
            $nadia_conn->query("UPDATE pickup SET pickup_status = 'Collected' WHERE volunteer_id = '$v_id' AND pickup_status = 'Scheduled'");
            $nadia_conn->close();

            // 4. RESET VOLUNTEER IN YOUR LOCAL TABLE
            mysqli_query($conn, "UPDATE volunteer SET availability_status = 'Available', donee_id = NULL WHERE volunteer_id = '$v_id'");
            
            $status_msg = "✅ Success: Nadia's records updated (Remote Sync) and Volunteer reset.";
        } else {
            $status_msg = "⚠️ Error: Could not reach Nadia's Module. Check ZeroTier Connection.";
        }
    }
}

/* ===============================
   3️⃣ FETCH DATA (MARIADB)
   =============================== */
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$sql = "SELECT * FROM volunteer";
if ($search) {
    $sql .= " WHERE name LIKE '%$search%' OR area_assigned LIKE '%$search%' OR volunteer_id LIKE '%$search%'";
}
$sql .= " ORDER BY volunteer_id ASC";
$result = mysqli_query($conn, $sql);

$count_res = mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN availability_status = 'Available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN availability_status = 'Busy' THEN 1 ELSE 0 END) as busy
    FROM volunteer");
$stats = mysqli_fetch_assoc($count_res);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Volunteer Management | Zero Hunger Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
        :root { --primary: #f39c12; --dark-orange: #a04000; --bg: #fdf6e3; --text-dark: #2c3e50; --shadow: rgba(0,0,0,0.08); }
        
        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text-dark); }
        
        .toast { background: #27ae60; color: white; padding: 15px; text-align: center; font-weight: 800; border-bottom: 4px solid #1e8449; animation: slideDown 0.4s ease-out; }
        @keyframes slideDown { from { transform: translateY(-100%); } to { transform: translateY(0); } }

        header { background: linear-gradient(90deg, #f39c12, #f1c40f); padding: 20px 40px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 10px var(--shadow); }
        header h1 { color: white; margin: 0; font-size: 1.4rem; font-weight: 800; }
        
        .container { width: 95%; max-width: 1200px; margin: 30px auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px var(--shadow); border-bottom: 4px solid var(--primary); }
        .stat-card h3 { margin: 0; font-size: 11px; color: #7f8c8d; text-transform: uppercase; letter-spacing: 1px; }
        .stat-card p { margin: 10px 0 0; font-size: 28px; font-weight: 800; color: var(--text-dark); }

        .top-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px var(--shadow); border-left: 6px solid var(--primary); }
        .admin-tag { font-size: 14px; color: #7f8c8d; }
        .admin-tag strong { color: var(--dark-orange); }

        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-input { flex: 1; padding: 12px; border-radius: 8px; border: 2px solid #eee; font-family: 'Inter'; font-size: 14px; }
        
        .btn { padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: 800; font-size: 13px; text-transform: uppercase; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; }
        .btn-add { background: var(--primary); color: white; }
        .btn-logout { background: #e74c3c; color: white; }
        .btn-back { background: #607d8b; color: white; }

        .table-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px var(--shadow); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; color: #7f8c8d; font-size: 11px; text-transform: uppercase; padding: 15px; border-bottom: 2px solid #eee; text-align: left; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 800; }
        .status-available { background: #e8f8f5; color: #27ae60; }
        .status-busy { background: #fef9e7; color: #f39c12; }
        .status-inactive { background: #fdedec; color: #e74c3c; }

        .action-links { display: flex; gap: 8px; }
        .action-links a { text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; }
        .edit-link { background: #ebf5fb; color: #2980b9; }
        .complete-link { background: #27ae60; color: white; font-weight: 800; border: none; cursor: pointer; }
        .deactivate-link { background: #f4f6f7; color: #7f8c8d; }
    </style>
</head>
<body>

<?php if($status_msg): ?>
    <div class="toast"><i class="fas fa-sync"></i> <?= $status_msg ?></div>
<?php endif; ?>

<header>
    <h1>Zero Hunger Hub</h1>
    <a href="logout.php" class="btn btn-logout" onclick="return confirm('Logout?')">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</header>

<div class="container">
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Volunteers</h3>
            <p><?= $stats['total'] ?></p>
        </div>
        <div class="stat-card" style="border-color: #27ae60;">
            <h3>Available</h3>
            <p style="color: #27ae60;"><?= $stats['available'] ?? 0 ?></p>
        </div>
        <div class="stat-card" style="border-color: #f39c12;">
            <h3>Busy/Assigned</h3>
            <p style="color: #f39c12;"><?= $stats['busy'] ?? 0 ?></p>
        </div>
    </div>

    <div class="top-info">
        <div>
            <h2 style="margin:0;">Volunteer Records</h2>
            <div class="admin-tag">Logged in as: <strong><?= htmlspecialchars($admin_username) ?></strong></div>
        </div>
        <div class="btn-bar" style="display:flex; gap:10px;">
            <a href="add_volunteer.php" class="btn btn-add"><i class="fas fa-plus"></i> Add New</a>
            <a href="http://10.175.254.163:3000/adminMenu.php" class="btn btn-back">
                <i class="fas fa-home"></i> Admin Menu
            </a>
        </div>
    </div>

    <form method="GET" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-add">Search</button>
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Linked Donee</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>#<?= $row['volunteer_id'] ?></td>
                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                    <td>
                        <span class="badge status-<?= strtolower($row['availability_status']) ?>">
                            <?= strtoupper($row['availability_status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if(!empty($row['donee_id'])): ?>
                            <span style="color: #8e44ad;"><i class="fas fa-link"></i> D<?= htmlspecialchars($row['donee_id']) ?></span>
                        <?php else: ?>
                            <span style="color: #bdc3c7;">None</span>
                        <?php endif; ?>
                    </td>
                    <td class="action-links">
                        <a href="edit_volunteer.php?id=<?= $row['volunteer_id'] ?>" class="edit-link">Edit</a>
                        
                        <?php if($row['availability_status'] === 'Busy'): ?>
                            <a href="volunteer.php?complete_id=<?= $row['volunteer_id'] ?>" 
                               class="complete-link" onclick="return confirm('Update Nadia\'s records to Collected?')">
                               Complete Pickup
                            </a>
                        <?php endif; ?>

                        <a href="volunteer.php?deactivate_id=<?= $row['volunteer_id'] ?>" class="deactivate-link">Deactivate</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>