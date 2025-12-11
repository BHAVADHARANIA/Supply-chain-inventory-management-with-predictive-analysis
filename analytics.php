<?php
session_start();
// NOTE: db.php MUST define DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME and $conn
require_once 'db.php'; 

// =========================================================
//           *** AD-HOC PREDICTIVE ANALYTICS HELPER ***
// =========================================================

/**
 * Simulates running an ad-hoc prediction based on product data.
 *
 * @param int $product_id The ID of the product.
 * @param object $conn The database connection object.
 * @return int The calculated predicted monthly demand.
 */
function run_adhoc_prediction($product_id, $conn) {
    if (!$conn) return 0;
    
    // 1. Fetch current data for the product
    $stmt = $conn->prepare("SELECT category, stock_level FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        return 0;
    }
    
    // Use ?? operator for safety if category is missing
    $category = strtolower($product['category'] ?? 'default');
    $stock_level = $product['stock_level'] ?? 0;
    
    // 2. Complexified Prediction Logic (Simulated)
    $base_demand = 500;
    switch ($category) {
        case 'electronics': $base_demand = 1500; break;
        case 'apparel': $base_demand = 800; break;
        case 'consumables': $base_demand = 2200; break;
    }
    
    // Adjust demand based on stock performance relative to base demand
    $stock_influence_factor = 0;
    if ($stock_level > $base_demand * 1.5) {
        $stock_influence_factor = -0.20;
    } elseif ($stock_level < $base_demand * 0.2) {
        $stock_influence_factor = 0.15;
    }
    
    $new_prediction = round($base_demand * (1 + $stock_influence_factor));
    
    return max(100, $new_prediction); // Minimum demand of 100 units
}

/**
 * Updates the inventory table with the new prediction result.
 */
function save_prediction_result($product_id, $predicted_demand, $conn) {
    if (!$conn) return;
    // CRITICAL: Assumes 'predicted_demand' column exists in the 'inventory' table
    $update_sql = $conn->prepare("UPDATE inventory SET predicted_demand = ? WHERE id = ?");
    $update_sql->bind_param("ii", $predicted_demand, $product_id);
    $update_sql->execute();
    $update_sql->close();
}


// =========================================================
//                *** MAIN LOGIC START ***
// =========================================================

// --- Handle Ad-Hoc Prediction Submission ---
if (isset($_POST['run_prediction']) && isset($conn) && $conn) {
    $product_id_to_predict = intval($_POST['product_id_to_predict'] ?? 0);

    if ($product_id_to_predict > 0) {
        $new_demand = run_adhoc_prediction($product_id_to_predict, $conn);
        save_prediction_result($product_id_to_predict, $new_demand, $conn);
        
        // Fetch product name for the success message
        $stmt = $conn->prepare("SELECT product_name FROM inventory WHERE id = ?");
        $stmt->bind_param("i", $product_id_to_predict);
        $stmt->execute();
        $product_name = $stmt->get_result()->fetch_assoc()['product_name'] ?? 'Product';
        $stmt->close();

        $_SESSION['analytics_success'] = "Demand forecast successfully generated for '" . htmlspecialchars($product_name) . "'. New predicted demand: " . number_format($new_demand);
        
        // Redirect to clear POST data and show the updated chart
        header("Location: analytics.php");
        exit;
    } else {
        $_SESSION['analytics_error'] = "Error: Invalid product selected for prediction.";
        header("Location: analytics.php");
        exit;
    }
}

// Check for and display messages
$success_message = $_SESSION['analytics_success'] ?? '';
$error_message = $_SESSION['analytics_error'] ?? '';
unset($_SESSION['analytics_success'], $_SESSION['analytics_error']);


// --- FETCHING DATA & ANALYSIS ---
$inventory_data = [];
$product_dropdown_list = [];

if (isset($conn) && $conn) {
    // CRITICAL: Querying predicted_demand from the inventory table
    $inventory_sql = "SELECT id, product_name, category, stock_level, predicted_demand FROM inventory ORDER BY predicted_demand DESC";
    $inventory_res = $conn->query($inventory_sql);

    if ($inventory_res) {
        while($row = $inventory_res->fetch_assoc()){
            $inventory_data[] = $row;
            $product_dropdown_list[$row['id']] = $row['product_name'];
        }
    }
}


// --- Performance Analysis ---
$total_products = count($inventory_data);
$total_predicted_demand = array_sum(array_column($inventory_data, 'predicted_demand'));
$avg_predicted_demand = $total_products > 0 ? $total_predicted_demand / $total_products : 0;

$performance_counts = [
    'Critical Risk' => 0,
    'Moderate Risk' => 0,
    'Good Performance' => 0
];

foreach ($inventory_data as &$item) {
    // Use ?? to handle cases where a column might be null/missing (though it shouldn't be now)
    $predicted = $item['predicted_demand'] ?? 0;
    $stock = $item['stock_level'] ?? 0;
    $risk_factor = $predicted > 0 ? $stock / $predicted : 0; // Stock to Demand Ratio

    if ($predicted === 0) {
        $item['performance_level'] = 'NoForecast';
        continue;
    }   
    
    // Risk categorization logic
    if ($risk_factor < 0.2) {
        $item['performance_level'] = 'Critical Risk';
        $performance_counts['Critical Risk']++;
    } elseif ($risk_factor < 0.5) {
        $item['performance_level'] = 'Moderate Risk';
        $performance_counts['Moderate Risk']++;
    } else {
        $item['performance_level'] = 'Good Performance';
        $performance_counts['Good Performance']++;
    }
}
unset($item);

$high_risk_count = $performance_counts['Critical Risk'];

// =========================================================
//            *** CRITICAL ALERT GENERATION LOGIC ***
// =========================================================

$alert_generation_count = 0;

if (isset($conn) && $conn) {
    // Iterate through the processed inventory data to generate alerts for Critical Risk items
    foreach ($inventory_data as $item) {
        if (($item['performance_level'] ?? '') === 'Critical Risk') {
            $product_id = $item['id'];
            $product_name = $item['product_name'];
            $stock = $item['stock_level'];
            $predicted = $item['predicted_demand'];
            
            $alert_type = 'Critical';
            $alert_message_base = "CRITICAL STOCK-OUT RISK for " . $product_name . ". Stock ({$stock}) is too low relative to Predicted Demand.";
            $final_message = $alert_message_base . " Action Required: Immediately review reorder quantities.";
            
            // 1. Check if a recent, identical Critical Alert already exists for this product
            $check_sql = "SELECT id FROM alerts WHERE product_id = ? AND type = 'Critical' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $product_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                // 2. No existing alert found, insert a new one
                $insert_sql = "INSERT INTO alerts (product_id, type, message, created_at) VALUES (?, ?, ?, NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iss", $product_id, $alert_type, $final_message);
                
                if ($insert_stmt->execute()) {
                    $alert_generation_count++;
                }
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
    }

    // Add a success message if new alerts were generated
    if ($alert_generation_count > 0) {
        $_SESSION['analytics_success'] = ($success_message ?? '') . " **({$alert_generation_count} new Critical Alerts generated and logged in Alerts Center.)**";
    }

    // Close connection after all processing
    $conn->close(); 
}

// --- DATA PREPARATION FOR CHART ---
// Note: array_filter logic uses fn() which requires PHP 7.4+
$performance_labels = array_keys(array_filter($performance_counts, fn($count) => $count > 0));
$performance_data = array_values(array_filter($performance_counts, fn($count) => $count > 0));

$chart_labels_json = json_encode($performance_labels);
$chart_data_json = json_encode($performance_data);

$chart_colors = [
    'Critical Risk' => 'rgba(231, 76, 60, 0.9)', 
    'Moderate Risk' => 'rgba(243, 156, 18, 0.9)', 
    'Good Performance' => 'rgba(46, 204, 113, 0.9)' 
];

$background_colors = array_map(function($label) use ($chart_colors) {
    return $chart_colors[$label] ?? 'rgba(150, 150, 150, 0.7)';
}, $performance_labels);

$colors_json = json_encode($background_colors);

?>
<!DOCTYPE html>
<html>
<head>
    <title>SCM Predictive Analytics</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <style>
        /* CSS styles remain the same */
        .analytics-kpi-container { display: flex; gap: 20px; margin-bottom: 25px; }
        .analytics-kpi-card { flex: 1; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); background: white; border: 1px solid #eee; }
        .analytics-kpi-card h4 { font-size: 24px; font-weight: 700; }
        .analytics-kpi-card p { font-size: 14px; color: #777; margin-top: 5px; }
        
        .analysis-table th, .analysis-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .analysis-table th {
            background-color: #f4f7f6;
            font-weight: 600;
            color: #34495e;
            text-transform: uppercase;
            font-size: 12px;
        }
        .insight-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 4px;
        }
        .performance-CriticalRisk { background-color: #f8d7da; color: #721c24; }
        .performance-ModerateRisk { background-color: #fff3cd; color: #856404; }
        .performance-GoodPerformance { background-color: #d4edda; color: #155724; }
        .performance-NoForecast { background-color: #e2e3e5; color: #495057; }

        .chart-container {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            padding: 10px;
        }
        .chart-box {
            flex: 1;
            max-width: 400px;
            height: 350px;
        }
        .chart-summary {
            flex: 2;
            padding: 20px;
            border-left: 1px solid #eee;
        }
        .ad-hoc-prediction-card {
            background-color: #fcfcfc;
            border: 1px solid #e1e1e1;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .ad-hoc-prediction-card select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 250px;
            margin-right: 10px;
        }
        .success-alert { background-color: #e6ffee; color: #1e8449; padding: 15px 20px; border-radius: 8px; border-left: 5px solid #2ecc71; margin-bottom: 25px; font-weight: 600; display: flex; align-items: center; }
        .error-alert { background-color: #fff0f0; color: #e74c3c; padding: 15px 20px; border-radius: 8px; border-left: 5px solid #e74c3c; margin-bottom: 25px; font-weight: 600; display: flex; align-items: center; }
        .alert-icon { margin-right: 10px; }
    </style>
</head>
<body>
    
    <div class="sidebar">
        <h2>SCM Pro</h2>
        <a href="dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="inventory.php"><i class="fas fa-warehouse"></i> Inventory</a>
        <a href="analytics.php" style="background:#34495e; color:white;"><i class="fas fa-chart-line"></i> Predictive Analytics</a>
        <a href="alerts.php"><i class="fas fa-bell"></i> Alerts</a>
        <a href="collaboration.php"><i class="fas fa-handshake"></i> Collaboration</a>
        <a href="index.php?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <h1>Predictive Analytics & Forecasting 📈</h1>
        <p style="color:#777; margin-bottom: 25px;">View demand forecasts and receive risk recommendations based on initial item setup and historical patterns.</p>
        
        <?php if ($success_message): ?>
            <div class="success-alert"><i class="fas fa-check-circle alert-icon"></i><?= $success_message ?></div>
        <?php elseif ($error_message): ?>
            <div class="error-alert"><i class="fas fa-exclamation-triangle alert-icon"></i><?= $error_message ?></div>
        <?php endif; ?>

        <div class="analytics-kpi-container">
            <div class="analytics-kpi-card" style="border-left: 5px solid #3498db;">
                <p>Total Products Analyzed</p>
                <h4 style="color: #3498db;"><?= $total_products ?></h4>
            </div>
            <div class="analytics-kpi-card" style="border-left: 5px solid #2ecc71;">
                <p>Avg. Predicted Demand (Units)</p>
                <h4 style="color: #2ecc71;"><?= number_format($avg_predicted_demand, 0) ?></h4>
            </div>
            <div class="analytics-kpi-card" style="border-left: 5px solid #e74c3c;">
                <p>Critical Stock-Out Risk Items</p>
                <h4 style="color: #e74c3c;"><?= $high_risk_count ?></h4>
            </div>
        </div>
        
        <div class="ad-hoc-prediction-card">
            <h3 style="margin-top: 0;"><i class="fas fa-calculator"></i> Run Ad-Hoc Prediction</h3>
            <p style="color:#777; font-size: 14px; margin-bottom: 15px;">Select an item and generate an updated demand forecast based on its current stock level and category.</p>
            <form method="post" action="analytics.php">
                <label for="product_id_to_predict" style="font-weight: 600;">Product:</label>
                <select id="product_id_to_predict" name="product_id_to_predict" required>
                    <option value="">-- Select Product --</option>
                    <?php foreach($product_dropdown_list as $id => $name): ?>
                        <option value="<?= $id ?>"><?= htmlspecialchars($name) ?> (ID: <?= $id ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="run_prediction" class="btn btn-primary" style="background: #3498db; padding: 8px 15px;">
                    <i class="fas fa-chart-line"></i> Generate Forecast
                </button>
            </form>
        </div>


        <div class="card" style="margin-top: 25px;">
            <h3><i class="fas fa-chart-pie"></i> Inventory Performance Scorecard</h3>
            <?php if ($total_products === 0): ?>
                <p style="text-align: center; color: #777;">No inventory data to analyze. Add items to your inventory first.</p>
            <?php else: ?>
                <div class="chart-container">
                    <div class="chart-box">
                        <canvas id="performanceChart"></canvas>
                    </div>
                    <div class="chart-summary">
                        <h4>Performance Breakdown:</h4>
                        <p style="color: #777;">Categorization based on Stock Level relative to Predicted Demand.</p>
                        
                        <div style="margin-top: 15px;">
                            <p style="font-weight: 600; color: #2ecc71;"><i class="fas fa-check-circle"></i> Good Performance: <span style="font-weight: 400; font-size: 14px;">(<?= $performance_counts['Good Performance'] ?> items)</span></p>
                        </div>
                        <div style="margin-top: 10px;">
                            <p style="font-weight: 600; color: #f39c12;"><i class="fas fa-exclamation-triangle"></i> Moderate Risk: <span style="font-weight: 400; font-size: 14px;">(<?= $performance_counts['Moderate Risk'] ?> items)</span></p>
                        </div>
                        <div style="margin-top: 10px;">
                            <p style="font-weight: 600; color: #e74c3c;"><i class="fas fa-skull-crossbones"></i> Critical Risk: <span style="font-weight: 400; font-size: 14px;">(<?= $performance_counts['Critical Risk'] ?> items)</span></p>
                        </div>
                        <?php if ($total_products - array_sum($performance_counts) > 0): ?>
                            <div style="margin-top: 10px;">
                                <p style="font-weight: 600; color: #7f8c8d;"><i class="fas fa-question-circle"></i> No Forecast: <span style="font-weight: 400; font-size: 14px;">(<?= $total_products - array_sum($performance_counts) ?> items)</span></p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card" style="margin-top: 25px;">
            <h3><i class="fas fa-table"></i> Detailed Forecast Analysis</h3>
            
            <?php if (empty($inventory_data)): ?>
                <p style="text-align: center; color: #777;">No inventory data to analyze. Add items to your inventory first.</p>
            <?php else: ?>
                <table class="analysis-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Predicted Demand (Units/Mo)</th>
                            <th>Risk Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($inventory_data as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($item['category'] ?? 'N/A') ?></td>
                            <td><?= number_format($item['stock_level'] ?? 0) ?></td>
                            <td><?= number_format($item['predicted_demand'] ?? 0) ?></td>
                            <td><span class="insight-badge performance-<?= str_replace(' ', '', $item['performance_level'] ?? 'NoForecast') ?>">
                                <?= $item['performance_level'] ?? 'No Forecast' ?>
                            </span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

<script>
    // Chart.js initialization for performanceChart
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('performanceChart');
        
        // Only initialize if there is data to display
        if (ctx && <?= json_encode(!empty($performance_labels)) ?>) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?= $chart_labels_json ?>,
                    datasets: [{
                        data: <?= $chart_data_json ?>,
                        backgroundColor: <?= $colors_json ?>,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Inventory Risk Distribution'
                        }
                    }
                }
            });
        }
    });
</script>
</body>
</html>