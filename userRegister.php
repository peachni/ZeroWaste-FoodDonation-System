<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'dbconnect.php';
session_start();

// Default values
$name = $username = $contact = $email = $address = $city = $usertype = $donor_type = $company_name = "";

if (isset($_POST['register'])) {

    $usertype = $_POST['usertype'];

    $username = pg_escape_string($conn, $_POST['username']);
    $name     = pg_escape_string($conn, $_POST['name']);
    $contact  = pg_escape_string($conn, $_POST['contact']);
    $email    = pg_escape_string($conn, $_POST['email']);
    $address  = pg_escape_string($conn, $_POST['address']);
    $city     = pg_escape_string($conn, $_POST['city']);

    // 🔐 PASSWORD HASHING
    $raw_password    = $_POST['password'];
    $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

    $date = date("Y-m-d");
    $admin_id = 1;

    // Check if username exists in donor or donee
    $check_username = "
        SELECT donor_username AS username FROM donor WHERE donor_username = $1
        UNION
        SELECT donee_username AS username FROM donee WHERE donee_username = $1
    ";
    $result = pg_query_params($conn, $check_username, array($username));

    if (pg_num_rows($result) > 0) {
        echo "<script>alert('Username already taken! Please choose another one.');</script>";
        $username = "";
    } else {

        if ($usertype === "donor") {

            $donor_type = $_POST['donor_type'] ?? '';
            if ($donor_type !== 'Individual' && $donor_type !== 'Organizational') {
                echo "<script>alert('Please select a valid donor type!'); window.history.back();</script>";
                exit;
            }

            $donor_type = pg_escape_string($conn, $donor_type);
            $company_name = ($donor_type === "Organizational")
                ? pg_escape_string($conn, $_POST['organization_name'])
                : null;

            $sql = "
                INSERT INTO donor
                (donor_name, donor_type, Company_Name, donor_username, donor_pass,
                 contact_number, email, address, city, registration_date, admin_id)
                VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11)
            ";

            $params = array(
                $name,
                $donor_type,
                $company_name,
                $username,
                $hashed_password,
                $contact,
                $email,
                $address,
                $city,
                $date,
                $admin_id
            );

        } else {

            $sql = "
                INSERT INTO donee
                (donee_name, donee_username, donee_pass,
                 contact_number, email, address, city, registration_date, admin_id)
                VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9)
            ";

            $params = array(
                $name,
                $username,
                $hashed_password,
                $contact,
                $email,
                $address,
                $city,
                $date,
                $admin_id
            );
        }

        $result = pg_query_params($conn, $sql, $params);

        if ($result) {
            echo "<script>alert('Registration successful! You can now log in.'); window.location='userLogin.php';</script>";
            exit;
        } else {
            echo "<script>alert('Registration failed: " . pg_last_error($conn) . "');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Registration | Zero Hunger</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="photo/SDG2Logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* BODY & BACKGROUND */
body {
    background: linear-gradient(135deg, #f39c12, #e67e22);
    font-family: 'Segoe UI', Tahoma, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

/* CONTAINER */
.container {
    max-width: 500px;
    background: white;
    padding: 40px 30px;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    animation: fadeIn 1s ease forwards;
    transform: translateY(-30px);
    opacity: 0;
}

/* HEADER */
h2 {
    color: #e67e22;
    font-weight: 700;
    text-align: center;
    margin-bottom: 20px;
    transform: scale(0);
    animation: popUp 0.6s forwards;
}

/* INPUTS */
input, select {
    margin-bottom: 18px;
    padding: 14px;
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 14px;
    transition: 0.3s;
}

input:focus, select:focus {
    outline: none;
    border-color: #e67e22;
    box-shadow: 0 0 8px rgba(230,126,34,0.5);
    transform: scale(1.02);
}

/* BUTTONS */
button {
    width: 100%;
    padding: 14px;
    border-radius: 8px;
    border: none;
    font-weight: bold;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
}

button[name="register"] {
    background: #e67e22;
    color: white;
}

button[name="register"]:hover {
    background: #d35400;
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* ERROR MESSAGE */
.error-message {
    background: #fdecea;
    color: #c0392b;
    padding: 10px;
    border-radius: 6px;
    font-size: 13px;
    margin-bottom: 15px;
}

/* LINKS */
a {
    display: block;
    margin-top: 15px;
    font-size: 14px;
    color: #e67e22;
    text-decoration: none;
    text-align: center;
    transition: 0.3s;
}

a:hover {
    text-decoration: underline;
    transform: scale(1.05);
}

/* DONOR ORG FIELD */
#orgNameField {
    transition: 0.3s;
}

/* ANIMATIONS */
@keyframes fadeIn {
    to { opacity: 1; transform: translateY(0); }
}

@keyframes popUp {
    0% { transform: scale(0); opacity: 0; }
    70% { transform: scale(1.2); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}

/* RESPONSIVE */
@media(max-width: 600px){
    .container { width: 90%; padding: 30px 20px; }
}
</style>
</head>

<body>

<div class="container">
<h2>User Registration</h2>

<form method="POST" action="userRegister.php">

<div class="mb-3">
<label class="form-label">User Type</label>
<select name="usertype" id="usertype" class="form-select" onchange="handleUserTypeChange()" required>
<option value="">Select User Type</option>
<option value="donor" <?php if($usertype=="donor") echo "selected"; ?>>Donor</option>
<option value="donee" <?php if($usertype=="donee") echo "selected"; ?>>Donee</option>
</select>
</div>

<div id="donorSection" class="<?php echo ($usertype=='donor') ? '' : 'd-none'; ?>">
<div class="mb-3">
<label class="form-label">Donor Type</label>
<select name="donor_type" id="donor_type" class="form-select" onchange="handleDonorTypeChange()">
<option value="">Select Donor Type</option>
<option value="Individual" <?php if($donor_type=="Individual") echo "selected"; ?>>Individual</option>
<option value="Organizational" <?php if($donor_type=="Organizational") echo "selected"; ?>>Organizational</option>
</select>
</div>

<div id="orgNameField" class="mb-3 <?php echo ($donor_type=='Organizational') ? '' : 'd-none'; ?>">
<input type="text" name="organization_name" class="form-control" placeholder="Organization Name"
value="<?php echo htmlspecialchars($company_name); ?>">
</div>
</div>

<div class="mb-3"><input type="text" name="name" class="form-control" placeholder="Full Name" required></div>
<div class="mb-3"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
<div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
<div class="mb-3"><input type="text" name="contact" class="form-control" placeholder="Contact Number" required></div>
<div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
<div class="mb-3"><input type="text" name="address" class="form-control" placeholder="Address" required></div>
<div class="mb-3"><input type="text" name="city" class="form-control" placeholder="City" required></div>

<button type="submit" name="register">Register</button>
</form>

<a href="userLogin.php">Back to Login</a>
</div>

<script>
function handleUserTypeChange() {
    const userType = document.getElementById('usertype').value;
    const donorSection = document.getElementById('donorSection');
    if (userType === "donor") {
        donorSection.classList.remove('d-none');
    } else {
        donorSection.classList.add('d-none');
        document.getElementById('orgNameField').classList.add('d-none');
    }
}

function handleDonorTypeChange() {
    const donorType = document.getElementById('donor_type').value;
    const orgField = document.getElementById('orgNameField');
    donorType === "Organizational"
        ? orgField.classList.remove('d-none')
        : orgField.classList.add('d-none');
}
</script>

</body>
</html>
