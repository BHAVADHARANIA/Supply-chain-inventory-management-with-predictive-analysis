<?php
session_start();
// Assumes db.php includes your database connection ($conn)
include 'db.php'; 

// --- Configuration & Initialization ---
$current_user = $_SESSION['username'] ?? 'SCM_Admin'; // Set current user or default
$success_message = '';
$error_message = '';

// --- Handle New Post Submission ---
if (isset($_POST['submit_post'])) {
    $post_type = $_POST['post_type'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    // Basic validation
    if (empty($subject) || empty($content) || empty($post_type)) {
        $error_message = '<i class="fas fa-exclamation-triangle"></i> Error: All fields are required to submit a post.';
    } else {
        try {
            // Prepare and execute the insert statement (requires collaboration_posts table)
            $stmt = $conn->prepare("INSERT INTO collaboration_posts (author_username, post_type, subject, content) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $current_user, $post_type, $subject, $content);
            
            if ($stmt->execute()) {
                $success_message = '<i class="fas fa-check-circle"></i> New post submitted successfully! Category: ' . htmlspecialchars($post_type);
                // Clear inputs to prevent resubmission on refresh
                unset($_POST['subject'], $_POST['content'], $_POST['post_type']); 
            } else {
                $error_message = '<i class="fas fa-exclamation-triangle"></i> Database error: ' . $conn->error;
            }
            $stmt->close();
            
        } catch (Exception $e) {
            $error_message = '<i class="fas fa-exclamation-triangle"></i> An unexpected error occurred: ' . $e->getMessage();
        }
    }
}

// --- Fetch Collaboration Posts ---
$posts = [];
$posts_sql = "SELECT * FROM collaboration_posts ORDER BY created_at DESC";
$posts_res = $conn->query($posts_sql);

if ($posts_res) {
    while($row = $posts_res->fetch_assoc()){
        $posts[] = $row;
    }
}

// Get counts for categorization display
$internal_count = count(array_filter($posts, fn($p) => $p['post_type'] == 'Internal'));
$supplier_count = count(array_filter($posts, fn($p) => $p['post_type'] == 'Supplier Query'));
$customer_count = count(array_filter($posts, fn($p) => $p['post_type'] == 'Customer Issue'));

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>SCM Collaboration & Discussion</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* General Status/Message Styling */
        .status-alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; display: flex; align-items: center; }
        .status-alert i { margin-right: 10px; }
        .success-alert { background-color: #e6ffee; color: #1e8449; border-left: 5px solid #2ecc71; }
        .error-alert { background-color: #fff0f0; color: #e74c3c; border-left: 5px solid #e74c3c; }
        
        /* KPI/Status Grid */
        .collab-status-container { display: flex; gap: 20px; margin-bottom: 25px; }
        .collab-status-card { 
            flex: 1; 
            padding: 15px; 
            border-radius: 8px; 
            text-align: center; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
            background: white; 
            border: 1px solid #eee; 
        }
        .collab-status-card h4 { font-size: 24px; font-weight: 700; }
        .collab-status-card p { font-size: 14px; color: #777; margin-top: 5px; }
        
        /* Discussion Board Styles */
        .discussion-grid { 
            display: grid; 
            grid-template-columns: 1fr 2.5fr; /* Increased feed area */
            gap: 25px; 
        }
        
        /* Sticky Form (Left Column) */
        .new-post-form-card { 
            padding: 20px; 
            background: #ffffff; 
            border-radius: 8px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            height: fit-content;
            /* Enhanced Alignment */
            position: sticky; /* Makes the form stick */
            top: 20px; /* Distance from the top of the viewport */
            align-self: flex-start; /* Ensures it starts at the top of its grid cell */
        }
        
        /* Form Element Spacing */
        .new-post-form-card .form-group {
            margin-bottom: 15px;
        }
        .new-post-form-card label {
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
            color: #34495e;
        }
        .new-post-form-card input[type="text"], 
        .new-post-form-card select,
        .new-post-form-card textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }

        /* Post Feed Area (Right Column) */
        .post-list-area { 
            display: flex; 
            flex-direction: column; 
            gap: 15px;
        }
        .post-item {
            padding: 18px;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #eef;
            transition: all 0.2s;
            border-left: 5px solid;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); /* Softer shadow */
        }
        .post-item:hover {
            border-color: #ddd;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 1px dashed #f0f0f0; /* Separator */
            margin-bottom: 10px;
        }
        .post-subject {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-left: 10px;
        }
        .post-meta {
            font-size: 12px;
            color: #95a5a6;
            white-space: nowrap; /* Keep time on one line */
        }
        .post-author {
            font-weight: 600;
            color: #3498db;
            padding-left: 5px;
        }
        .post-content {
            font-size: 14px;
            line-height: 1.6;
            color: #444;
            white-space: pre-wrap; /* Preserves line breaks */
        }
        .post-badge-container {
            display: flex;
            align-items: center;
        }
        .post-badge {
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* Type Colors */
        .type-Internal { background: #d9e9f6; color: #2980b9; border-left-color: #3498db; }
        .type-SupplierQuery { background: #fef7e9; color: #e67e22; border-left-color: #f39c12; }
        .type-CustomerIssue { background: #ffe6e6; color: #c0392b; border-left-color: #e74c3c; }

    </style>
</head>
<body>
    
    <div class="sidebar">
        <h2>SCM Pro</h2>
        <a href="dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="inventory.php"><i class="fas fa-warehouse"></i> Inventory</a>
        <a href="analytics.php"><i class="fas fa-chart-line"></i> Predictive Analytics</a>
        <a href="alerts.php"><i class="fas fa-bell"></i> Alerts</a>
        <a href="collaboration.php" style="background:#34495e; color:white;"><i class="fas fa-handshake"></i> Collaboration</a>
        <a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <h1>Supply Chain Collaboration Board 🤝</h1>
        <p style="color:#777; margin-bottom: 20px;">Use this board to share updates, submit queries, and track issues across internal teams, suppliers, and customers.</p>

        <?php if ($success_message): ?>
            <div class="status-alert success-alert">
                <?= $success_message ?>
            </div>
        <?php elseif ($error_message): ?>
            <div class="status-alert error-alert">
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <div class="collab-status-container">
            <div class="collab-status-card">
                <p>Total Discussions</p>
                <h4 style="color:#34495e;"><?= count($posts) ?></h4>
            </div>
            <div class="collab-status-card">
                <p style="color: #2980b9;">Internal Updates</p>
                <h4 style="color: #3498db;"><?= $internal_count ?></h4>
            </div>
            <div class="collab-status-card">
                <p style="color: #e67e22;">Supplier Queries</p>
                <h4 style="color: #f39c12;"><?= $supplier_count ?></h4>
            </div>
            <div class="collab-status-card">
                <p style="color: #c0392b;">Customer Issues</p>
                <h4 style="color: #e74c3c;"><?= $customer_count ?></h4>
            </div>
        </div>
        
        <hr style="border: 0; border-top: 1px solid #ecf0f1; margin: 20px 0;">

        <div class="discussion-grid">
            
            <div class="new-post-form-card">
                <h3><i class="fas fa-plus-circle"></i> Create New Post</h3>
                <p style="font-size: 13px; color:#777; margin-bottom: 15px;">Posting as: <span class="post-author"><?= htmlspecialchars($current_user) ?></span></p>

                <form method="post" action="collaboration.php">
                    
                    <div class="form-group">
                        <label for="post_type">Post Category</label>
                        <select id="post_type" name="post_type" required>
                            <option value="">-- Select Category --</option>
                            <option value="Internal" <?= (($_POST['post_type'] ?? '') == 'Internal') ? 'selected' : '' ?>>Internal (Team Update)</option>
                            <option value="Supplier Query" <?= (($_POST['post_type'] ?? '') == 'Supplier Query') ? 'selected' : '' ?>>Supplier Query (PO, Status)</option>
                            <option value="Customer Issue" <?= (($_POST['post_type'] ?? '') == 'Customer Issue') ? 'selected' : '' ?>>Customer Issue (Delay, Quality)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject/Title</label>
                        <input type="text" id="subject" name="subject" required placeholder="Brief, clear title..." value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="content">Details/Message</label>
                        <textarea id="content" name="content" rows="6" required placeholder="Provide full details of the issue or update..."><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" name="submit_post" class="btn btn-primary" style="background: #3498db; width: 100%; margin-top: 10px;">
                        <i class="fas fa-paper-plane"></i> Submit Post
                    </button>
                </form>
            </div>

            <div class="post-list-area">
                <h3><i class="fas fa-list-ul"></i> Latest Activity Feed</h3>
                
                <?php if (empty($posts)): ?>
                    <div class="post-item" style="text-align: center; border-left: 5px solid #2ecc71; background: #f0fff0;">
                        <p style="color: #2ecc71; margin: 0;">No collaboration posts found. Be the first to start a discussion! 🚀</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): 
                        $type_class = 'type-' . str_replace(' ', '', $post['post_type']);
                    ?>
                        <div class="post-item <?= $type_class ?>">
                            <div class="post-header">
                                <div class="post-badge-container">
                                    <span class="post-badge <?= $type_class ?>"><?= htmlspecialchars($post['post_type']) ?></span>
                                    <span class="post-subject"><?= htmlspecialchars($post['subject']) ?></span>
                                </div>
                                <div class="post-meta">
                                    by <span class="post-author"><?= htmlspecialchars($post['author_username']) ?></span>
                                </div>
                            </div>
                            <div class="post-content">
                                <?= nl2br(htmlspecialchars(substr($post['content'], 0, 300))) ?>
                                <?php if (strlen($post['content']) > 300): ?>
                                    <span style="color: #7f8c8d; font-style: italic;">... (content truncated)</span>
                                <?php endif; ?>
                            </div>
                            <div class="post-meta" style="text-align: right; margin-top: 10px;">
                                Posted: <?= date('M d, Y H:i', strtotime($post['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</body>
</html>