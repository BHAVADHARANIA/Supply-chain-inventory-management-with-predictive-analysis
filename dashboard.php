<?php 
include 'db.php'; 
session_start(); 

// Ensure connection exists before proceeding
if (!$conn) {
    die("Database connection failed.");
}

// =========================================================
//            *** DATABASE MAINTENANCE (Cleanup) ***
// =========================================================

// Deletes alerts where the product_id is no longer found in the inventory table.
$cleanup_sql = "
    DELETE a 
    FROM alerts a
    LEFT JOIN inventory i ON a.product_id = i.id
    WHERE i.id IS NULL AND a.product_id IS NOT NULL
";
$conn->query($cleanup_sql);


// =========================================================
//            *** 1. Data Fetching for KPIs ***
// =========================================================

// Total Inventory Value (KPI 1)
$res_val = $conn->query("SELECT SUM(stock_level * unit_price) as val FROM inventory");
$inv_value = $res_val->fetch_assoc()['val'] ?? 0;

// Total Unique Products (KPI 2)
$res_prod = $conn->query("SELECT COUNT(*) as cnt FROM inventory");
$total_products = $res_prod->fetch_assoc()['cnt'] ?? 0;

// KPI 3: Items Below Static Reorder Point (stock_level <= reorder_point)
$res_reorder = $conn->query("SELECT COUNT(*) as cnt FROM inventory WHERE stock_level <= reorder_point");
$below_reorder_count = $res_reorder->fetch_assoc()['cnt'] ?? 0;

// High Risk Items (KPI - From Predictive Analytics - Retained but unused in display logic)
$res_risk = $conn->query("SELECT COUNT(*) as cnt FROM predictions WHERE risk_level='High'");
$high_risk = $res_risk->fetch_assoc()['cnt'] ?? 0;

// KPI 4: Critical Alerts (Total from alerts table)
$res_alerts = $conn->query("SELECT COUNT(*) as cnt FROM alerts WHERE type='Critical'");
$critical_alerts = $res_alerts->fetch_assoc()['cnt'] ?? 0;

// --- REMOVED: Query for $critical_alerts_res list ---

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>SCM Pro - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS for the KPI cards */
        .kpi-cards-container { display: flex; gap: 20px; margin-bottom: 25px; }
        .kpi-card { flex: 1; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); background: white; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid; }
        .kpi-card h4 { font-size: 14px; color: #777; margin-bottom: 5px; }
        .kpi-card h2 { font-size: 28px; font-weight: 700; margin: 0; }
        .kpi-icon { font-size: 36px; opacity: 0.3; }
        
        /* Color definitions */
        .border-primary { border-left-color: #3498db; }
        .text-primary { color: #3498db; }
        .border-success { border-left-color: #2ecc71; }
        .text-success { color: #2ecc71; }
        .border-danger { border-left-color: #e74c3c; }
        .text-danger { color: #e74c3c; }
        .border-warning { border-left-color: #f39c12; }
        .text-warning { color: #f39c12; }

        /* Style for the central informational box */
        .info-box { padding: 50px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); background: white; margin-top: 25px; text-align: center; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>SCM Pro</h2>
        <a href="dashboard.php" style="background:#34495e; color:white;"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="inventory.php"><i class="fas fa-warehouse"></i> Inventory</a>
        <a href="analytics.php"><i class="fas fa-chart-line"></i> Predictive Analytics</a>
        <a href="alerts.php"><i class="fas fa-bell"></i> Alerts</a>
        <a href="collaboration.php"><i class="fas fa-handshake"></i> Collaboration</a>
        <a href="index.php?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
            <h1>Executive Summary</h1>
            <div style="color: #555;">
                <i class="fas fa-user-circle fa-lg"></i> Admin User
            </div>
        </div>

        <div class="kpi-cards-container">
            <div class="kpi-card border-primary">
                <div>
                    <h4>TOTAL INVENTORY VALUE</h4>
                    <h2 class="text-primary">$<?= number_format($inv_value, 2) ?></h2>
                </div>
                <div class="kpi-icon"><i class="fas fa-money-bill-trend-up"></i></div>
            </div>
            
            <div class="kpi-card border-success">
                <div>
                    <h4>ACTIVE PRODUCTS</h4>
                    <h2 class="text-success"><?= $total_products ?></h2>
                </div>
                <div class="kpi-icon"><i class="fas fa-boxes-stacked"></i></div>
            </div>

            <div class="kpi-card border-warning">
                <div>
                    <h4>BELOW REORDER POINT</h4>
                    <h2 class="text-warning"><?= $below_reorder_count ?></h2>
                </div>
                <div class="kpi-icon"><i class="fas fa-boxes-packing"></i></div>
            </div>

            <div class="kpi-card border-danger">
                <div>
                    <h4>CRITICAL ALERTS (Total)</h4>
                    <h2 class="text-danger"><?= $below_reorder_count?></h2>
                </div>
                <div class="kpi-icon"><i class="fas fa-bell-slash"></i></div>
            </div>
        </div>

        <div class="info-box">
            <p style="color: #777; font-size: 1.1em;">
                <i class="fas fa-chart-simple fa-2x" style="color: #95a5a6; margin-bottom: 15px;"></i><br>
                This Dashboard provides an **Executive Summary** based on Key Performance Indicators (KPIs). <br>
                For detailed alerts and necessary actions, please navigate to the **Alerts** page.
            </p>
        </div>
        
    </div>

</body>
</html>