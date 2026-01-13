<?php
session_start();
include 'dbconnect.php';

if (!isset($_SESSION['admin_username'])) {
    echo "<script>
        alert('Admin login required');
        window.location='LoginAdmin.php';
    </script>";
    exit;
}

$type = $_GET['type'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

if (!$type || !$id) {
    die("Invalid request");
}

/* ===============================
   FETCH USER DATA
================================ */
if ($type === 'donor') {
    $result = pg_query($conn, "SELECT * FROM donor WHERE donor_id = $id");
    $user = pg_fetch_assoc($result);
} elseif ($type === 'donee') {
    $result = pg_query($conn, "SELECT * FROM donee WHERE donee_id = $id");
    $user = pg_fetch_assoc($result);
} else {
    die("Invalid type");
}

if (!$user) {
    die("User not found");
}

/* ===============================
   UPDATE LOGIC
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($type === 'donor') {
        $name = pg_escape_string($_POST['name']);
        $username = pg_escape_string($_POST['username']);
        $donor_type = pg_escape_string($_POST['donor_type']);
        $company = pg_escape_string($_POST['company_name']);

        pg_query($conn, "
            UPDATE donor SET
                donor_name = '$name',
                donor_username = '$username',
                donor_type = '$donor_type',
                company_name = '$company'
            WHERE donor_id = $id
        ");

    } else {
        $name = pg_escape_string($_POST['name']);
        $username = pg_escape_string($_POST['username']);

        pg_query($conn, "
            UPDATE donee SET
                donee_name = '$name',
                donee_username = '$username'
            WHERE donee_id = $id
        ");
    }

    echo "<script>
        alert('User updated successfully');
        window.location='adminMenu.php';
    </script>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit <?= ucfirst($type) ?></title>
<style>
body {
    font-family: Arial;
    background: #fdf6e3;
}
.container {
    width: 400px;
    margin: 60px auto;
    background: #fff;
    padding: 25px;
    border-radius: 10px;
}
input, select {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
}
button {
    background: #f39c12;
    color: #fff;
    padding: 10px;
    border: none;
    width: 100%;
    cursor: pointer;
}
</style>
</head>

<body>

<div class="container">
<h2>Edit <?= ucfirst($type) ?></h2>

<form method="post">

<label>Name</label>
<input type="text" name="name"
       value="<?= htmlspecialchars($type === 'donor' ? $user['donor_name'] : $user['donee_name']) ?>"
       required>

<label>Username</label>
<input type="text" name="username"
       value="<?= htmlspecialchars($type === 'donor' ? $user['donor_username'] : $user['donee_username']) ?>"
       required>

<?php if ($type === 'donor'): ?>
    <label>Donor Type</label>
    <input type="text" name="donor_type"
           value="<?= htmlspecialchars($user['donor_type']) ?>">

    <label>Company Name</label>
    <input type="text" name="company_name"
           value="<?= htmlspecialchars($user['company_name']) ?>">
<?php endif; ?>

<button type="submit">Update</button>

</form>
</div>

</body>
</html>
