<?php
session_start();
include 'db.php'; 

// --- 1. Handle Automation Toggle State (Simulated) ---
// In a real application, you would update a database table here.

// Simulated rule states for display (using defaults if not set in session)
$rules = $_SESSION['automation_rules'] ?? [
    'auto_reorder' => ['active' => true, 'id' => 'auto_reorder'],
    'critical_email' => ['active' => true, 'id' => 'critical_email'],
    'daily_report' => ['active' => false, 'id' => 'daily_report'],
    'supplier_sync' => ['active' => true, 'id' => 'supplier_sync'],
];

if (isset($_POST['toggle_rule'])) {
    $rule_id = $_POST['rule_id'];
    // Checkbox presence indicates 'on' (1), lack of presence means 'off' (0)
    $is_active = isset($_POST['is_active']) ? true : false;
    
    // Update the simulated state in the session
    if (isset($rules[$rule_id])) {
        $rules[$rule_id]['active'] = $is_active;
        $_SESSION['automation_rules'] = $rules;
    }

    // Redirect to prevent form resubmission on refresh
    header("Location: automation.php");
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>SCM Automation Center</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* --- Professional Toggle Switch Styles (Previously in style.css, now embedded) --- */
        .automation-rules-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .automation-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            border-left: 5px solid #3498db; /* Primary accent */
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .automation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        .switch-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .switch-container:last-child {
            border-bottom: none;
        }
        .switch-info h4 {
            margin-bottom: 5px;
            color: #34495e;
            font-size: 16px;
            font-weight: 600;
        }
        .switch-info p {
            color: #7f8c8d;
            font-size: 13px;
        }

        /* Base Switch Styling */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #27ae60; /* Green for ON */
        }
        input:focus + .slider {
            box-shadow: 0 0 1px #27ae60;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .slider.round {
            border-radius: 34px;
        }
        .slider.round:before {
            border-radius: 50%;
        }
    </style>
</head>
<body>
    
    <div class="sidebar">
        <h2>SCM Pro</h2>
        <a href="dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="inventory.php"><i class="fas fa-warehouse"></i> Inventory</a>
        <a href="analytics.php"><i class="fas fa-chart-line"></i> Predictive Analytics</a>
        <a href="automation.php" style="background:#34495e; color:white;"><i class="fas fa-robot"></i> Automation</a>
        <a href="alerts.php"><i class="fas fa-bell"></i> Alerts</a>
        <a href="collaboration.php"><i class="fas fa-handshake"></i> Collaboration</a>
        <a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <h1>Automation & Rule Engine ⚙️</h1>
        
        <div class="card">
            <p style="color:#555;">Configure automated workflows to manage inventory, alerts, and reporting without manual intervention.</p>
        </div>

        <div class="automation-rules-container">
            
            <div class="automation-card">
                <h3><i class="fas fa-boxes"></i> Inventory & Reordering</h3>

                <div class="switch-container">
                    <div class="switch-info">
                        <h4>Auto-Generate Purchase Orders</h4>
                        <p>Automatically create a draft PO when stock falls below the predicted demand or reorder level.</p>
                    </div>
                    <form method="post" action="automation.php" style="margin: 0;">
                        <label class="switch">
                            <input type="hidden" name="rule_id" value="auto_reorder">
                            <input type="checkbox" name="is_active" onchange="this.form.submit()" <?= $rules['auto_reorder']['active'] ? 'checked' : '' ?>>
                            <span class="slider round"></span>
                        </label>
                        <input type="hidden" name="toggle_rule" value="1">
                    </form>
                </div>

                <div class="switch-container">
                    <div class="switch-info">
                        <h4>Automatic Supplier Data Sync</h4>
                        <p>Sync pricing and lead times from preferred supplier APIs daily.</p>
                    </div>
                    <form method="post" action="automation.php" style="margin: 0;">
                        <label class="switch">
                            <input type="hidden" name="rule_id" value="supplier_sync">
                            <input type="checkbox" name="is_active" onchange="this.form.submit()" <?= $rules['supplier_sync']['active'] ? 'checked' : '' ?>>
                            <span class="slider round"></span>
                        </label>
                        <input type="hidden" name="toggle_rule" value="1">
                    </form>
                </div>
            </div> <div class="automation-card">
                <h3><i class="fas fa-bell"></i> Alert & Reporting Rules</h3>

                <div class="switch-container">
                    <div class="switch-info">
                        <h4>Critical Alert Email Notification</h4>
                        <p>Send immediate email notifications for all 'High Risk' inventory predictions.</p>
                    </div>
                    <form method="post" action="automation.php" style="margin: 0;">
                        <label class="switch">
                            <input type="hidden" name="rule_id" value="critical_email">
                            <input type="checkbox" name="is_active" onchange="this.form.submit()" <?= $rules['critical_email']['active'] ? 'checked' : '' ?>>
                            <span class="slider round"></span>
                        </label>
                        <input type="hidden" name="toggle_rule" value="1">
                    </form>
                </div>

                <div class="switch-container">
                    <div class="switch-info">
                        <h4>Scheduled Daily Performance Report</h4>
                        <p>Generate and email the Executive Summary report every morning at 8:00 AM.</p>
                    </div>
                    <form method="post" action="automation.php" style="margin: 0;">
                        <label class="switch">
                            <input type="hidden" name="rule_id" value="daily_report">
                            <input type="checkbox" name="is_active" onchange="this.form.submit()" <?= $rules['daily_report']['active'] ? 'checked' : '' ?>>
                            <span class="slider round"></span>
                        </label>
                        <input type="hidden" name="toggle_rule" value="1">
                    </form>
                </div>
            </div> </div> </div>
</body>
</html>