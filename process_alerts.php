<?php
// NOTE: This script is designed to be run from the command line (CLI) or a scheduler.
// It assumes your db.php file defines $conn for the database connection.
include 'db.php'; 

// =========================================================
//                   *** SMS NOTIFICATION LOGIC ***
// (Copied from alerts.php to make this script standalone)
// =========================================================

/**
 * Sends a critical alert via SMS using a third-party API.
 * @param string $recipient_phone_number Phone number in international format (+CCXXXXXX).
 * @param string $alert_message The body of the message to send.
 * @return string Result status message.
 */
function send_sms_alert($recipient_phone_number, $alert_message) {
    // --------------------------------------------------------------------------------
    // !!! CRITICAL: REPLACE THESE PLACEHOLDERS WITH YOUR SMS API CREDENTIALS !!!
    // --------------------------------------------------------------------------------
    $twilio_account_sid = 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; 
    $twilio_auth_token = 'your_twilio_auth_token'; 
    $twilio_phone_number = '+15017122661'; // Your SMS service phone number
    // --------------------------------------------------------------------------------

    if ($twilio_account_sid === 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' || $recipient_phone_number === '') {
        return "SMS API credentials not configured. SMS simulation failed.";
    }

    // --- REAL API INTEGRATION LOGIC GOES HERE ---
    // You would use Twilio's SDK or cURL to send the message.
    
    // SIMULATED SUCCESS:
    return "SMS sent successfully to " . $recipient_phone_number . ".";
}

// =========================================================
//                  *** AUTOMATION CORE LOGIC ***
// =========================================================

// 1. Define Recipients for Automation
// Since this is a CLI script, we cannot use $_SESSION. We use the desired number.
$alert_recipients = ["+919597032013"]; // Hardcoded list for automation
$alert_type = 'Critical';
$low_stock_threshold = 50;

echo "--- SCM ALERT PROCESS STARTED ---\n";

// 2. Query Inventory for Alert Conditions
// ASSUMES an 'inventory' table with 'product_name', 'stock_level', and 'status'
$inventory_sql = "SELECT product_name, stock_level FROM inventory WHERE stock_level < $low_stock_threshold AND status = 'Active'";
$inventory_res = $conn->query($inventory_sql);

if ($inventory_res->num_rows > 0) {
    echo "Found " . $inventory_res->num_rows . " low stock items.\n";
    
    while ($product = $inventory_res->fetch_assoc()) {
        $message = "CRITICAL ALERT: Low Stock Detected for " . $product['product_name'] . 
                   ". Stock is at " . $product['stock_level'] . " units (Threshold: " . $low_stock_threshold . "). Place order immediately.";
        
        // --- A. Log Alert to Database ---
        $insert_sql = $conn->prepare("INSERT INTO alerts (type, message, created_at) VALUES (?, ?, NOW())");
        // Check if the prepare was successful before binding
        if ($insert_sql) {
            $insert_sql->bind_param("ss", $alert_type, $message);
            $insert_sql->execute();
            echo "-> DB Logged: " . $product['product_name'] . "\n";
            $insert_sql->close();
        } else {
            echo "-> ERROR logging alert: " . $conn->error . "\n";
        }

        // --- B. Trigger SMS Notification ---
        foreach ($alert_recipients as $recipient) {
            $sms_result = send_sms_alert($recipient, $message);
            echo "-> SMS Trigger: " . $sms_result . "\n";
        }
    }
} else {
    echo "No critical low stock alerts found. System nominal.\n";
}

$conn->close();
echo "--- SCM ALERT PROCESS FINISHED ---\n";
?>