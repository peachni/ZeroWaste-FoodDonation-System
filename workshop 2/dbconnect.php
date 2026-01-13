<?php
$host   = "127.0.0.1";
$port   = "5432";
$dbname = "postgres";
$user   = "postgres";
$pass   = "1234";

$conn_string = "host=$host port=$port dbname=$dbname user=$user password=$pass";
$conn = pg_connect($conn_string);

if (!$conn) {
    die("Database connection failed.");
}
?>
