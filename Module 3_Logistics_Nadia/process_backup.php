<?php
session_start();
require_once 'connect.php';

/* ===============================
   1. SECURITY CHECK
================================ */
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: system_backup.php?msg=backup_failed&reason=AccessDenied");
    exit;
}

/* ===============================
   2. BACKUP FOLDER
================================ */
$backup_folder = "C:/xampp/htdocs/mariadb_test/Backups/";
if (!is_dir($backup_folder)) mkdir($backup_folder, 0777, true);

/* ===============================
   3. FILE NAME (Standardized)
================================ */
$filename  = "ZeroHunger_" . date('Y-m-d_H-i') . ".sql";
$full_path = $backup_folder . $filename;

/* ===============================
   4. DATABASE SETTINGS
================================ */
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "zerowaste";

/* ===============================
   5. mysqldump PATH
================================ */
$mysqldump = "C:/xampp/mysql/bin/mysqldump.exe";
if (!file_exists($mysqldump)) {
    header("Location: system_backup.php?msg=backup_failed&reason=mysqldump_not_found");
    exit;
}

/* ===============================
   6. BUILD COMMAND
================================ */
$command = "\"$mysqldump\" --user={$db_user} --password=\"{$db_pass}\" --host={$db_host} --routines --triggers {$db_name} > " . escapeshellarg($full_path);

/* ===============================
   7. EXECUTE
================================ */
$output = [];
$return_var = -1;
exec($command . " 2>&1", $output, $return_var);

/* ===============================
   8. VERIFY AND REDIRECT
================================ */
if ($return_var === 0 && file_exists($full_path) && filesize($full_path) > 0) {
    header("Location: system_backup.php?msg=saved&file=" . urlencode($filename));
} else {
    $error = implode(" | ", $output);
    $error = $error ?: "mysqldump failed or exec disabled";
    header("Location: system_backup.php?msg=backup_failed&reason=" . urlencode($error));
}
exit;
