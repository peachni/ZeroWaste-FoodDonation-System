<?php
session_start();
require_once 'connect.php';

// 1. SECURITY & DATA PREP
if (($_SESSION['role'] ?? '') !== 'donee') {
    die("Access Denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['food_id'])) {
    $donee_id = $_SESSION['donee_id'];
    $food_id = escape($_POST['food_id']);
    
    // Ensure input is an integer
    $qty_requested = (int)$_POST['quantity'];
    
    // Standardized Fulfillment Logic
    $fulfillment = $_POST['fulfillment_mode']; // 'self' or 'delivery'
    $date = date('Y-m-d');
    
    // Address Logic
    if ($fulfillment === 'self') {
        $final_address = "Self-Pickup at Source";
        $initial_status = "Collected"; 
    } else {
        $initial_status = "Pending"; 
        if (isset($_POST['use_other_addr'])) {
            $final_address = escape($_POST['other_address']);
        } else {
            $final_address = escape($_POST['registered_address_val']);
        }
    }

    // --- STEP 1: CONNECT TO BALQIS AND VALIDATE RULES ---
    // Use @ to suppress warnings and handle them manually for a cleaner UI
    $conn_balqis = @new mysqli("10.175.254.152", "balqis", "Balqis123", "workshop2");

    if (!$conn_balqis->connect_error) {
        // Fetch current stock to verify rules server-side
        // FIXED: Using FoodList_ID to match Balqis's schema
        $stock_check = $conn_balqis->query("SELECT Quantity FROM Food_Don_List WHERE FoodList_ID = '$food_id' LIMIT 1");
        
        if (!$stock_check || $stock_check->num_rows === 0) {
            die("<script>alert('Error: Item not found in inventory.'); window.history.back();</script>");
        }

        $stock_row = $stock_check->fetch_assoc();
        $current_stock = (int)$stock_row['Quantity'];

        // RULE A: Minimum 10 units
        if ($qty_requested < 10 && $current_stock >= 10) {
            die("<script>alert('Error: Minimum request is 10 units.'); window.history.back();</script>");
        }

        // RULE B: If stock is below 20, must take everything
        if ($current_stock < 20 && $qty_requested != $current_stock) {
            die("<script>alert('Error: Low stock detected. You must take the remaining $current_stock units.'); window.history.back();</script>");
        }

        // RULE C: Remainder Rule
        $remainder = $current_stock - $qty_requested;
        if ($remainder > 0 && $remainder < 10) {
            die("<script>alert('Error: This request leaves $remainder units. Minimum for next user is 10.'); window.history.back();</script>");
        }

    // --- STEP 2: ATTEMPT REMOTE UPDATE (Sync Available vs Consumed) ---
    // Logic: Available goes down, Consumed goes up. 
    // We CAST Quantity because Balqis defined it as VARCHAR.
    $update_sql = "UPDATE Food_Don_List 
                   SET Quantity = Quantity - $qty_requested, 
                       Qty_Consumed = COALESCE(Qty_Consumed, 0) + $qty_requested 
                   WHERE FoodList_ID = '$food_id' AND CAST(Quantity AS UNSIGNED) >= $qty_requested";
    
    $conn_balqis->query($update_sql);
    
    // Check if rows were actually changed (Concurrency Check)
    if ($conn_balqis->affected_rows > 0) {
        // NEW: If stock hits 0, set status to 'Consumed' for Balqis's reports
        if (($current_stock - $qty_requested) <= 0) {
            $conn_balqis->query("UPDATE Food_Don_List SET Status = 'Consumed' WHERE FoodList_ID = '$food_id'");
        }
        $balqis_updated = true;
    } else {
        $conn_balqis->close();
        die("<script>alert('Order Failed: Stock was updated by another user. Please refresh.'); window.location.href='food_marketplace.php';</script>");
    }
} else {
    die("Error: Food Module is currently offline. Please try again later.");
}

// --- STEP 3: ATTEMPT LOCAL SAVE (NADIA) ---
if ($balqis_updated) {
    // donation_id = '' triggers your TXXX auto-ID logic
    $sql_nadia = "INSERT INTO donation (donation_id, donee_id, foodlist_id, quantity, delivery_address, donation_date, status) 
                  VALUES ('', '$donee_id', '$food_id', $qty_requested, '$final_address', '$date', '$initial_status')";
    
    if ($conn->query($sql_nadia)) {
        // SUCCESS: Both databases synced
        $conn_balqis->close();
        header("Location: donation.php?msg=success");
        exit;
    } else {
        // --- STEP 4: COMPENSATING ROLLBACK ---
        // Nadia's DB failed! We MUST restore Balqis's data exactly as it was.
        $compensate_sql = "UPDATE Food_Don_List 
                           SET Quantity = Quantity + $qty_requested, 
                               Qty_Consumed = Qty_Consumed - $qty_requested,
                               Status = 'available' 
                           WHERE FoodList_ID = '$food_id'";
        
        $conn_balqis->query($compensate_sql);
        $conn_balqis->close();
        
        die("Critical Error: Local save failed. Inventory has been restored to Balqis's Module. Error: " . $conn->error);
    }
}
}
?>