<?php
ini_set('memory_limit', '256M'); 
session_start();
require_once 'connect.php';
mysqli_report(MYSQLI_REPORT_OFF);

// SESSION HANDSHAKE
if (isset($_REQUEST['role'])) {
    $_SESSION['role']      = $_REQUEST['role'];
    $_SESSION['user_name'] = $_REQUEST['user_name'] ?? $_REQUEST['donee_name'] ?? "User";
    $_SESSION['donee_id']  = $_REQUEST['donee_id'] ?? null;
    $_SESSION['donor_id']  = $_REQUEST['donor_id'] ?? null;
    $_SESSION['admin_id']  = $_REQUEST['admin_id'] ?? null;
}

if (!isset($_SESSION['role'])) {
    header("Location: http://10.175.254.163:3000/userLogin.php");
    exit();
}

$role = strtolower($_SESSION['role']);
$user_name = $_SESSION['user_name'];
$display_id = ($role === 'admin') ? $_SESSION['admin_id'] : (($role === 'donor') ? $_SESSION['donor_id'] : $_SESSION['donee_id']);
$theme_color = "#f39c12";

// MODULE CHECKS
function check_module($ip, $port = 3306) {
    $fp = @fsockopen($ip, $port, $errno, $errstr, 0.1);
    if ($fp) { fclose($fp); return true; }
    return false;
}
$module_status = [
    'User' => check_module('10.175.254.163', 3000), 
    'Food' => check_module('10.175.254.152', 3306), 
    'Donation' => true, 
    'Volunteer' => check_module('10.175.254.2', 3306)
];

// CONNECT TO FOOD DB
$conn_balqis = @new mysqli("10.175.254.152", "balqis", "Balqis123", "workshop2");
$available_items = [];
$categories = [];
$balqis_online = false;

// IMAGE SERVER (CORRECT SERVER)
$remote_server_url = "http://10.175.254.152:3000/";

if (!$conn_balqis->connect_error) {
    $balqis_online = true;

    $res = $conn_balqis->query("
        SELECT f.FoodList_ID, f.Food_Name, f.Quantity, f.Food_Desc, f.Image, 
               f.Allergen_Info, f.Storage_Instruction, f.Manufacture_Date, f.Expiry_Date,
               c.CatID, c.CatType
        FROM food_don_list f
        LEFT JOIN category c ON f.CatID = c.CatID
        WHERE f.Status = 'Available'
    ");
    if ($res) {
        while($row = $res->fetch_assoc()) { $available_items[] = $row; }
    }

    $res_cat = $conn_balqis->query("SELECT CatID, CatType FROM category ORDER BY CatType ASC");
    if ($res_cat) {
        while($row = $res_cat->fetch_assoc()) { $categories[] = $row; }
    }

    $conn_balqis->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Available Food | Zero Hunger</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');
:root { --primary: #f39c12; --bg: #fdf6e3; --text-dark: #2c3e50; --shadow: rgba(0,0,0,0.08); }

body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text-dark); }
header { background: linear-gradient(90deg, #f39c12, #f1c40f); padding: 20px 40px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 10px var(--shadow); }
.header-left { display: flex; flex-direction: column; gap: 4px; }
header h1 { font-family: 'Segoe UI', sans-serif; color: white; margin: 0; font-size: 1.4rem; font-weight: 800; }
.breadcrumb { font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; margin-top: 4px; line-height: 1; }
.breadcrumb .past { color: rgba(255,255,255,0.5); text-decoration: none; transition: 0.3s; display: inline-block; } 
.breadcrumb .past:hover { color: #ffffff; transform: scale(1.05); }
.breadcrumb .current { color: #ffffff; }
.breadcrumb i { font-size: 9px; margin: 0 8px; color: rgba(255,255,255,0.4); display: inline-flex; align-items: center; }

.header-right { display: flex; align-items: center; gap: 20px; }
.module-health { display: flex; gap: 12px; align-items: center; }
.health-item { background: rgba(0,0,0,0.7); color: white; padding: 5px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; display: flex; align-items: center; gap: 6px; transition: 0.3s; }
.dot { height: 7px; width: 7px; border-radius: 50%; }
.on { background: #2ecc71; box-shadow: 0 0 8px #2ecc71; }
.off { background: #e74c3c; }
.btn-logout { background: white; color: #c0392b; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 800; transition: 0.3s; }

.main-wrapper { max-width: 1150px; margin: 20px auto; padding: 0 20px; }
.welcome-card { background: white; padding: 15px 25px; border-radius: 12px; border-left: 10px solid <?= $accent_color ?? '#f39c12' ?>; box-shadow: 0 4px 15px var(--shadow); margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
.welcome-card h2 { font-family: 'Segoe UI', sans-serif; margin: 0; font-size: 18px; font-weight: 700; }
.id-pill { background: #fff3e0; color: #d35400; padding: 4px 15px; border-radius: 50px; font-weight: 800; font-size: 12px; border: 1.5px solid #ffe0b2; display: inline-block; margin-top: 5px; }

.nav-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.nav-tabs { display: flex; gap: 8px; }
.nav-tabs a { background: white; color: var(--text-dark); text-decoration: none; padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; box-shadow: 0 2px 5px var(--shadow); transition: 0.3s; }
.nav-tabs a.active { background: var(--primary); color: white; }
.btn-menu { background: #2c3e50; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 700; transition: 0.3s; }

.data-card { background: white; border-radius: 12px; box-shadow: 0 8px 30px var(--shadow); overflow: hidden; padding-bottom: 30px; }
.table-label { font-family: 'Trebuchet MS', sans-serif; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; color: #2c3e50; padding: 25px 25px 20px; display: block; font-weight: 800; }

.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; padding: 0 25px; align-items: start; }
.card { background: #fff; border-radius: 12px; border: 1px solid #eee; border-top: 5px solid <?= $theme_color ?>; box-shadow: 0 4px 10px rgba(0,0,0,0.02); display: flex; flex-direction: column; overflow: hidden; transition: 0.3s; cursor:pointer; }
.card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }

.card-img { width: 100%; height: 160px; background: #f9f9f9; object-fit: cover; border-bottom: 1px solid #eee; }
.card-content { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; }

.card h3 { margin: 0 0 8px; color: #2c3e50; font-size: 16px; font-family: 'Trebuchet MS', sans-serif; }
.card p { font-size: 12px; color: #7f8c8d; line-height: 1.4; margin-bottom: 15px; }
.qty-tag { font-weight: 800; color: <?= $theme_color ?>; font-size: 13px; margin-top: auto; }

.btn-req { display: block; background: <?= $theme_color ?>; color: white; text-align: center; padding: 10px; border-radius: 8px; text-decoration: none; font-weight: 800; margin-top: 15px; font-size: 12px; transition: 0.3s; }
.btn-req:hover { background: #d35400; }

.offline-msg { text-align: center; padding: 60px; color: #95a5a6; font-weight: 800; }

.filter-bar { display: flex; gap: 10px; margin: 0 25px 25px 25px; flex-wrap: wrap; }
.filter-bar input, .filter-bar select { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; flex: 1 1 220px; min-width: 180px; max-width: 320px; }

.extra-details { display:none; margin-top:10px; font-size:13px; color:#555; border-top:1px dashed #ccc; padding-top:10px; }
.card.expanded .extra-details { display:block; }
</style>
</head>
<body>

<header>
    <div class="header-left">
        <h1>Zero Hunger Hub</h1>
        <div class="breadcrumb">
            <a href="http://10.175.254.163:3000/<?= $role ?>Menu.php" class="past">Menu</a> 
            <i class="fas fa-chevron-right"></i> 
            <a href="donation.php" class="past">Donation</a>
            <i class="fas fa-chevron-right"></i> 
            <span class="current">Available Food</span>
        </div>
    </div>
    <div class="header-right">
        <div class="module-health">
            <?php foreach($module_status as $name => $status): ?>
                <div class="health-item"><div class="dot <?= $status?'on':'off' ?>"></div> <?= $name ?></div>
            <?php endforeach; ?>
        </div>
        <a href="http://10.175.254.163:3000/userLogOut.php" class="btn-logout">LOGOUT</a>
    </div>
</header>

<div class="main-wrapper">
    <div class="welcome-card">
        <div>
            <h2>Welcome, <?= htmlspecialchars($user_name) ?></h2>
            <div class="id-pill"><?= htmlspecialchars($display_id) ?> &nbsp;&nbsp;&nbsp; <?= ucfirst($role) ?></div>
        </div>
    </div>

    <div class="nav-section">
        <div class="nav-tabs">
            <a href="donation.php"><i class="fas fa-receipt"></i> Records Hub</a>
            <a href="food_marketplace.php" class="active"><i class="fas fa-store"></i> Available Food</a>
        </div>
        <a href="http://10.175.254.163:3000/<?= $role ?>Menu.php" class="btn-menu">Back to Menu</a>
    </div>

    <div class="data-card">
        <span class="table-label">Availability</span>
        <?php if (!$balqis_online): ?>
            <div class="offline-msg">Food Module Offline</div>
        <?php else: ?>
            <div class="filter-bar">
                <input type="text" id="searchInput" placeholder="Search food..." onkeyup="filterCards()" />
                <select id="categoryFilter" onchange="filterCards()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars(strtolower($cat['CatType'])) ?>"><?= htmlspecialchars($cat['CatType']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid">
                <?php foreach ($available_items as $item): ?>
                    <div class="card" 
                         data-catid="<?= htmlspecialchars($item['CatID'] ?? '') ?>" 
                         data-catname="<?= htmlspecialchars(strtolower($item['CatType'] ?? '')) ?>">

                        <?php
                            if (empty($item['Image'])) {
                                $img_src = 'https://via.placeholder.com/300x160?text=No+Image';
                            } elseif (filter_var($item['Image'], FILTER_VALIDATE_URL)) {
                                $img_src = $item['Image'];
                            } else {
                                $img_src = $remote_server_url . $item['Image'];
                            }
                        ?>

                        <img src="<?= htmlspecialchars($img_src) ?>"
                             class="card-img"
                             alt="<?= htmlspecialchars($item['Food_Name']) ?>"
                             onerror="this.src='https://via.placeholder.com/300x160?text=Image+Not+Found';"
                             onclick="toggleDetails(this)">

                        <div class="card-content" onclick="toggleDetails(this)">
                            <h3 class="food-name"><?= htmlspecialchars($item['Food_Name']) ?></h3>
                            <p><?= htmlspecialchars($item['Food_Desc']) ?></p>
                            <div class="qty-tag">Available: <?= $item['Quantity'] ?> Units</div>

                            <div class="extra-details">
                                <p><strong>Manufacture Date:</strong> <?= $item['Manufacture_Date'] ?></p>
                                <p><strong>Expiry Date:</strong> <?= $item['Expiry_Date'] ?></p>
                                <p><strong>Storage:</strong> <?= htmlspecialchars($item['Storage_Instruction']) ?></p>
                                <p><strong>Allergens:</strong> <?= htmlspecialchars($item['Allergen_Info'] ?? 'None') ?></p>
                            </div>

                            <a href="confirm_request.php?food_id=<?= $item['FoodList_ID'] ?>" class="btn-req">SELECT ITEM</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleDetails(el) {
    let card = el.closest('.card');
    card.classList.toggle('expanded');
}

function filterCards() {
    let search = document.getElementById('searchInput').value.toLowerCase();
    let category = document.getElementById('categoryFilter').value.toLowerCase();
    let cards = document.querySelectorAll('.grid .card');

    cards.forEach(card => {
        let name = card.querySelector('.food-name').textContent.toLowerCase();
        let desc = card.querySelector('p').textContent.toLowerCase();
        let cat = card.getAttribute('data-catname') ?? '';

        let matchesSearch = name.includes(search) || desc.includes(search) || search === '';
        let matchesCategory = cat.includes(category) || category === '';

        card.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
    });
}
</script>

</body>
</html>