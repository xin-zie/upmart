<?php
session_start();
include '../db_connect.php';

// 1. Guard: Only allow Admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 2. Handle Actions (Approve or Delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $p_id = intval($_GET['id']);
  
    if (isset($_GET['action']) && $_GET['action'] === 'approve') {
        $p_id = intval($_GET['id']);
        
        // 1. Get the seller_id and title first
        $res = $conn->query("SELECT seller_id, title FROM products WHERE product_id = $p_id");
        $product = $res->fetch_assoc();
        
        if ($product) {
            // 2. Update post status
            $conn->query("UPDATE products SET status = 'Available' WHERE product_id = $p_id");
            
            // 3. Send notification to the seller
            $msg = "Your post '" . $product['title'] . "' has been approved and is now live!";
            addNotification($conn, $product['seller_id'], $msg);
        }
    }
}

// 3. Fetch Posts (Join with users for seller names and categories for the tag)
$query = "SELECT p.*, u.full_name, c.category_name, 
          (SELECT image_path FROM media WHERE product_id = p.product_id LIMIT 1) as product_img 
          FROM products p 
          JOIN users u ON p.seller_id = u.user_id 
          JOIN categories c ON p.category_id = c.category_id 
          ORDER BY p.created_at DESC";
$posts = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin-panel.css">
    <link rel="icon" href="favicon.png" type="image/png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <img src="logo.png" class="logo-img" alt="UPMart Logo">
        </div>

        <img src="profile.jpg" alt="Profile" class="profile-img">
        <div class="profile-info">
            <span class="profile-name">Admin</span>
        </div>

        <ul class="nav-links">
            <li class="active">
                <a href="admin_main.php"><span>🏠︎</span> Dashboard</a>
            </li>
            <li style="background: #e1f5da; color: black" ><a href="admin_post.php"><span>📮</span> Posts</a></li>
            <li><a href="admin_report.php"><span>🔔</span> Reports</a></li>

            <div class="logout-container">
                <a href="../includes/logout.php" class="logout-btn" style="text-decoration:none; display:block; text-align:center;">Logout</a>
            </div>
        </ul>
    </div>

    <div class="main-content">
        <nav class="top-nav">
            <h1 style="font-size: 1.4rem; margin-top: 10px;">📮 Posts</h1>
            <div class="status-indicators">
                <button class="icon-btn" id="notifTrigger" style="margin-left: 50px;"><span class="material-icons">notifications</span><span
                        class="notif-badge"></span></button>
            </div>
        </nav>

        <div class="content-row">
            <div class="about-text">
                <p>Scan through pending posts from sellers.</p>
            </div>
        </div>

        <section class="dashboard-grid admin-review-grid">
            <div class="posts-review-container">
                <div class="review-header">
                    <h3>Pending Posts</h3>
                    <span class="count-badge">3 Pending</span>
                </div>

                <div class="posts-list" id="pendingPosts">
                    <?php if ($posts->num_rows > 0): ?>
                        <?php while($row = $posts->fetch_assoc()): 
                            $img = !empty($row['product_img']) ? $row['product_img'] : '../uploads/default.jpg';
                        ?>
                            <div class="post-item" 
                                onclick="showPreview('<?= addslashes($row['title']) ?>', '<?= addslashes($row['full_name']) ?>', '₱<?= number_format($row['price'], 2) ?>', '<?= addslashes($row['description']) ?>', '<?= $img ?>')">
                                <div class="post-details">
                                    <img src="<?= $img ?>" alt="Product" class="item-img">
                                    <div class="item-info">
                                        <h4><?= htmlspecialchars($row['title']) ?></h4>
                                        <p>Seller: <strong><?= htmlspecialchars($row['full_name']) ?></strong> • ₱<?= number_format($row['price'], 2) ?></p>
                                        <span class="category-tag"><?= htmlspecialchars($row['category_name']) ?></span>
                                    </div>
                                </div>
                                <div class="post-actions">
                                    <a href="admin-posts.php?action=approve&id=<?= $row['product_id'] ?>" class="approve-btn" style="text-decoration:none;">
                                        <span class="material-icons">check</span> Approve
                                    </a>
                                    <a href="admin-posts.php?action=delete&id=<?= $row['product_id'] ?>" class="delete-btn" style="text-decoration:none;" onclick="return confirm('Delete this post?')">
                                        <span class="material-icons">delete_outline</span> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="padding:20px; color:#888;">No posts found in the database.</p>
                    <?php endif; ?>
                </div>
                    
            </div>

            <div class="preview-panel" id="previewPanel">
                <div class="empty-state" id="emptyState">
                    <span class="material-icons">manage_search</span>
                    <p>Select a post to inspect details</p>
                </div>

                <div class="preview-content" id="previewContent" style="display: none;">
                    <img id="prevImg" src="" alt="Product Large">
                    <div class="preview-text">
                        <h2 id="prevTitle">Item Title</h2>
                        <div class="prev-meta">
                            <span id="prevSeller">Seller</span> • <span id="prevPrice">Price</span>
                        </div>
                        <hr>
                        <h5>Description</h5>
                        <p id="prevDesc">Full description...</p>
                    </div>

                    <div class="preview-actions">
                        <button class="approve-btn-large">Confirm Approval</button>
                        <button class="delete-btn-large">Reject & Notify Seller</button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script src="admin-dash.js"></script>
</body>

</html>