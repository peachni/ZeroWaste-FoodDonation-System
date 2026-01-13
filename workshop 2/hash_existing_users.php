<?php
include 'dbconnect.php';

// ===== HASH DONEE PASSWORDS =====
$result = pg_query($conn, "SELECT donor_id, donor_pass FROM donor");

while ($row = pg_fetch_assoc($result)) {
    // Skip already-hashed passwords
    if (str_starts_with($row['donor_pass'], '$2y$')) {
        continue;
    }

    $hashed = password_hash($row['donor_pass'], PASSWORD_DEFAULT);

    pg_query_params(
        $conn,
        "UPDATE donor SET donor_pass = $1 WHERE donor_id = $2",
        [$hashed, $row['donor_id']]
    );
}

echo "DONE";
