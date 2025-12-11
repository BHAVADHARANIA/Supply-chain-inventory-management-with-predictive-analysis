<?php
session_start();
require_once 'db.php'; // Use require_once for safer dependency inclusion

// --- 1. Handle Form Submissions (Add/Update/Delete) ---

// ADD Item
if (isset($_POST['add_item'])) {
    // Use ?? 0 for safety, though 'required' in HTML should prevent a null value
    $name = $_POST['product_name'] ?? '';
    $stock = $_POST['stock_level'] ?? 0;
    $reorder = $_POST['reorder_point'] ?? 0; // Ensure consistency
    $price = $_POST['unit_price'] ?? 0.00;

    // Validate connection before preparing statement
    if (isset($conn)) {
        $stmt = $conn->prepare("INSERT INTO inventory (product_name, stock_level, reorder_point, unit_price) VALUES (?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("siid", $name, $stock, $reorder, $price);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    header("Location: inventory.php");
    exit;
}

// DELETE Item
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Validate connection before preparing statement
    if (isset($conn)) {
        $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
        
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    header("Location: inventory.php");
    exit;
}

// --- 2. Fetch Data ---
$inventory_res = null;
if (isset($conn)) {
    $inventory_res = $conn->query("SELECT * FROM inventory ORDER BY product_name ASC");
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SCM Inventory Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    
    <div class="sidebar">
        <h2>SCM Pro</h2>
        <a href="dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="inventory.php" style="background:#34495e; color:white;"><i class="fas fa-warehouse"></i> Inventory</a>
        <a href="analytics.php"><i class="fas fa-chart-line"></i> Predictive Analytics</a>
        <a href="alerts.php"><i class="fas fa-bell"></i> Alerts</a>
        <a href="collaboration.php"><i class="fas fa-handshake"></i> Collaboration</a>
        <a href="index.php?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a> 
    </div>

    <div class="main">
        <h1>Inventory Management 📦</h1>

        <div class="card">
            <h3>Add New Inventory Item</h3>
            <form method="post" style="display:flex; gap: 15px; align-items:flex-end;">
                <input type="text" name="product_name" placeholder="Product Name" required style="padding: 10px; border: 1px solid #ccc; border-radius: 4px; flex: 3;">
                <input type="number" name="stock_level" placeholder="Stock Level" required style="padding: 10px; border: 1px solid #ccc; border-radius: 4px; flex: 1;">
                <input type="number" name="reorder_point" placeholder="Reorder Point" required style="padding: 10px; border: 1px solid #ccc; border-radius: 4px; flex: 1;">
                <input type="number" step="0.01" name="unit_price" placeholder="Unit Price ($)" required style="padding: 10px; border: 1px solid #ccc; border-radius: 4px; flex: 1;">
                <button type="submit" name="add_item" class="btn"><i class="fas fa-plus"></i> Add Item</button>
            </form>
        </div>
        
        <div class="card" style="margin-top: 25px;">
            <h3>Current Stock Levels</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Stock Level</th>
                        <th>Reorder Point</th>
                        <th>Unit Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($inventory_res && $inventory_res->num_rows > 0): ?>
                        <?php while($row = $inventory_res->fetch_assoc()): ?>
                            <?php 
                                $status_text = "OK";
                                $status_class = "text-success";
                                
                                // Check stock_level against reorder_point
                                if ($row['stock_level'] <= $row['reorder_point']) {
                                    $status_text = "LOW / Reorder Needed";
                                    $status_class = "text-danger";
                                }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= $row['stock_level'] ?></td>
                                <td><?= $row['reorder_point'] ?></td>
                                <td>$<?= number_format($row['unit_price'], 2) ?></td>
                                <td><span class="<?= $status_class ?>"><strong><?= $status_text ?></strong></span></td>
                                <td>
                                    <a href="inventory.php?delete=<?= $row['id'] ?>" style="color:red; text-decoration:none;" onclick="return confirm('Are you sure you want to delete this item?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No inventory items found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>