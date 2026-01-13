<?php
session_start();
require_once 'connect.php';

/* ===============================
   1. SECURITY CHECK
================================ */
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: system_backup.php?msg=restore_failed&reason=AccessDenied");
    exit;
}

/* ===============================
   2. CHECK FILE
================================ */
if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== 0) {
    header("Location: system_backup.php?msg=restore_failed&reason=No+file+uploaded");
    exit;
}

$file = $_FILES['backup_file']['tmp_name'];

/* ===============================
   3. DATABASE SETTINGS
================================ */
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'zerowaste';

/* ===============================
   4. MYSQL PATH
================================ */
$mysql = "C:/xampp/mysql/bin/mysql.exe";

if (!file_exists($mysql)) {
    header("Location: system_backup.php?msg=restore_failed&reason=mysql_not_found");
    exit;
}

/* ===============================
   5. BUILD COMMAND
================================ */
$command = "\"$mysql\" --user={$db_user} --password=\"{$db_pass}\" --host={$db_host} {$db_name} < " . escapeshellarg($file);

/* ===============================
   6. EXECUTE AND CAPTURE ERRORS
================================ */
$output = [];
$return_var = -1;

exec($command . " 2>&1", $output, $return_var);

/* ===============================
   7. VERIFY AND REDIRECT
================================ */
if ($return_var === 0) {
    header("Location: system_backup.php?msg=restored&file=" . urlencode(basename($file)));
} else {
    $error = implode(" | ", $output);
    $error = $error ?: "mysql restore failed or exec disabled";
    header("Location: system_backup.php?msg=restore_failed&reason=" . urlencode($error));
}

exit;
