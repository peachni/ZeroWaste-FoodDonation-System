<?php 
// 1. CONNECTION & SESSION
include('DB.php');
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Capture session logic
if (isset($_GET['role'])) {
    $_SESSION['role']      = $_GET['role'];
    $_SESSION['user_name'] = $_GET['user_name'] ?? "User";
    $_SESSION['donor_id']  = $_GET['donor_id'] ?? null;
    $_SESSION['admin_id']  = $_GET['admin_id'] ?? null; 
}

if (!isset($_SESSION['role'])) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
            <h2>🔒 Access Denied</h2>
            <p>No session data received. Please login via the Dashboard.</p>
         </div>");
}

$noty_message = ""; 

// 2. BULK DELETE LOGIC
if (isset($_POST['bulk_delete'])) {
    if (!empty($_POST['selected'])) {
        $ids = $_POST['selected'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('s', count($ids));

        $stmt = $conn->prepare("DELETE FROM category WHERE CatID IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);

        if ($stmt->execute()) {
            $noty_message = "new Noty({type: 'success', text: '🗑️ Selected categories deleted successfully.', timeout: 3000, theme: 'metroui'}).show();";
        } else {
            $noty_message = "new Noty({type: 'error', text: '❌ Error deleting categories.', timeout: 3000, theme: 'metroui'}).show();";
        }
        $stmt->close();
    }
}

// 3. STATUS MESSAGES
if (isset($_GET['success'])) $noty_message = "new Noty({type: 'success', text: '✅ Category added successfully.', timeout: 3000, theme: 'metroui'}).show();";
if (isset($_GET['updated'])) $noty_message = "new Noty({type: 'success', text: '✅ Category updated successfully.', timeout: 3000, theme: 'metroui'}).show();";
if (isset($_GET['deleted'])) $noty_message = "new Noty({type: 'error', text: '🗑️ Category deleted successfully.', timeout: 3000, theme: 'metroui'}).show();";

// 4. FETCH ALL CATEGORIES
$result = $conn->query("SELECT CatID, CatType, admin_id FROM category ORDER BY CatID ASC");
$categories = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noty/3.1.4/noty.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noty/3.1.4/themes/metroui.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noty/3.1.4/noty.min.js"></script>

    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            margin: 0; 
            background-color: #f4f7f6; 
        }

        /* Dashboard Header */
        .header-bar {
            background-color: #f39c12;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-bar h2 { margin: 0; font-size: 24px; }
        .dash-btn {
            background: white;
            color: #f39c12;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: 0.3s;
        }
        .dash-btn:hover { background: #eee; }

        /* Main Container */
        .container {
            width: 95%;
            max-width: 1200px;
            margin: 30px auto;
            background: #fdf6e3; /* Warm Cream */
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 10px 30px rgba(0,0,0,0.1);
        }

        /* Admin Info Card */
        .user-info { 
            background: white; 
            padding: 15px 25px; 
            border-radius: 12px; 
            border-left: 5px solid #3498db;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            color: #2c3e50;
        }

        /* Table Design */
        .styled-table { 
            width: 100%;
            border-collapse: collapse; 
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        } 
        .styled-table thead tr {
            background-color: #f39c12;
            color: white;
            text-align: left;
        }
        .styled-table th, .styled-table td { padding: 15px; }
        .styled-table tbody tr { border-bottom: 1px solid #eee; transition: 0.2s; }
        .styled-table tbody tr:hover { background-color: #fffbf0; }

        /* Buttons matching Waste Design */
        .btn-add {
            background-color: #f39c12;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
        }
        .btn-delete {
            background-color: #e74c3c;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-edit {
            background-color: #3498db;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-left: 10px;
        }

        input[type="checkbox"] { transform: scale(1.3); cursor: pointer; }
    </style>
</head>
<body>

<!-- DASHBOARD HEADER -->
<div class="header-bar">
    <h2></h2>
    <a href="http://10.175.254.163:3000/adminMenu.php" class="dash-btn">🏠 Back to Dashboard</a>
</div>

<div class="container">
    <h3>📋 CATEGORY MANAGEMENT</h3>

    <!-- ADMIN INFO CARD -->
    <div class="user-info">
        👤 <strong>Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Guest') ?></strong> 👋<br>
        <small style="color: #666;">Admin ID: <?= htmlspecialchars($_SESSION['admin_id'] ?? 'N/A') ?></small>
    </div>

    <a href="AddCat.php" class="btn-add">➕ Add New Category</a>

    <form method="post" id="categoryForm">
        <table class="styled-table">
            <thead>
                <tr>
                    <th width="50px"><input type="checkbox" id="select_all" onclick="toggleAll(this)"></th>
                    <th>Category ID</th>
                    <th>Category Type</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><input type="checkbox" name="selected[]" value="<?= htmlspecialchars($cat['CatID']) ?>"></td>
                        <td><strong style="color: #2c3e50;"><?= htmlspecialchars($cat['CatID']) ?></strong></td>
                        <td><?= htmlspecialchars($cat['CatType']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" align="center" style="padding: 40px; color: #999;">No categories found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 25px;">
            <button type="button" class="btn-delete" onclick="confirmAction('delete')">🗑️ Delete Selected</button>
            <button type="button" class="btn-edit" onclick="confirmAction('edit')">✏️ Edit Selected</button>
            <input type="hidden" name="bulk_delete" id="bulk_delete_trigger">
        </div>
    </form>
</div>

<script>
window.onload = function() { <?= $noty_message ?> };

function toggleAll(source) {
    const checkboxes = document.querySelectorAll('input[name="selected[]"]');
    checkboxes.forEach(cb => cb.checked = source.checked);
}

function confirmAction(type) {
    const selected = document.querySelectorAll('input[name="selected[]"]:checked');

    if (selected.length === 0) {
        new Noty({ type: 'warning', text: '⚠️ Please select an item.', timeout: 2000, theme: 'metroui' }).show();
        return;
    }

    if (type === 'edit') {
        if (selected.length > 1) {
            new Noty({ type: 'warning', text: '⚠️ select only one for editing.', timeout: 2000, theme: 'metroui' }).show();
            return;
        }
        
        let n = new Noty({
            text: 'Do you want to edit the selected category?',
            theme: 'metroui',
            layout: 'center',
            modal: true,
            buttons: [
                Noty.button('YES', '', function() {
                    window.location.href = 'AddCat.php?edit=' + encodeURIComponent(selected[0].value);
                }, {style: 'background: #3498db; color: white; padding: 8px 15px; border-radius: 5px; border:none; margin-right: 10px; cursor: pointer; font-weight: bold;'}),
                Noty.button('NO', '', function() { n.close(); }, {style: 'background: #95a5a6; color: white; padding: 8px 15px; border-radius: 5px; border:none; cursor: pointer; font-weight: bold;'})
            ]
        }).show();

    } else if (type === 'delete') {
        let n = new Noty({
            text: '<strong>Confirm Delete</strong><br>Are you sure you want to delete ' + selected.length + ' item(s)?',
            theme: 'metroui',
            layout: 'center',
            modal: true,
            buttons: [
                Noty.button('YES, DELETE', '', function() {
                    document.getElementById('bulk_delete_trigger').name = "bulk_delete";
                    document.getElementById('categoryForm').submit();
                }, {style: 'background: #e74c3c; color: white; padding: 8px 15px; border-radius: 5px; border:none; margin-right: 10px; cursor: pointer; font-weight: bold;'}),
                Noty.button('CANCEL', '', function() { n.close(); }, {style: 'background: #95a5a6; color: white; padding: 8px 15px; border-radius: 5px; border:none; cursor: pointer; font-weight: bold;'})
            ]
        }).show();
    }
}
</script>
</body>
</html>