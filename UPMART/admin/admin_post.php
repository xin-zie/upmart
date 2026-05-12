<?php
session_start();
include '../db_connect.php';

// 1. Guard: Only allow Admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// 2. Handle Actions (Approve or Delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $p_id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'approve') {
        // 1. Fetch product details
        $res = $conn->query("SELECT seller_id, title FROM products WHERE product_id = $p_id");
        $product = $res->fetch_assoc();

        if ($product) {
            // 2. Update post status
            $conn->query("UPDATE products SET 
                        status = 'Available', 
                        approval_status = 'Approved' 
                        WHERE product_id = $p_id");

            // 3. Prepare the notification text
            $sender_name = "System Admin";
            $raw_msg = "<b>$sender_name</b>: Your post '" . $product['title'] . "' has been approved!";


            $safe_msg = mysqli_real_escape_string($conn, $raw_msg);


            $admin_id = $_SESSION['user_id'];
            $seller_id = $product['seller_id'];

            if ($seller_id > 0) {
                $notif_query = "INSERT INTO notifications (user_id, sender_id, message, is_read) 
                                VALUES ($seller_id, $admin_id, '$safe_msg', 0)";
                $conn->query($notif_query);
            }

            // 4. Insert into notifications using the escaped $safe_msg
            $notif_query = "INSERT INTO notifications (user_id, sender_id, message, is_read) 
                        VALUES ($seller_id, $admin_id, '$safe_msg', 0)";

            if ($conn->query($notif_query)) {
                header("Location: admin_post.php?msg=approved");
                exit();
            } else {
                die("Notification Error: " . $conn->error);
            }
        }
    } elseif ($action === 'delete') {
        $conn->query("DELETE FROM products WHERE product_id = $p_id");
        header("Location: admin_post.php?msg=deleted");
        exit();
    }
}

// 3. Fetch Posts (Join with users for seller names and categories for the tag)
$query = "SELECT 
            p.*, 
            u.full_name, 
            u.profile_pic AS seller_img, 
            c.category_name, 
            (SELECT GROUP_CONCAT(image_path) FROM media m WHERE m.product_id = p.product_id) as all_images 
          FROM products p 
          JOIN users u ON p.seller_id = u.user_id 
          JOIN categories c ON p.category_id = c.category_id 
          WHERE p.approval_status = 'Pending'    
          ORDER BY p.created_at DESC";
$posts = $conn->query($query);

// Count posts that need admin approval
$pending_post_query = "SELECT COUNT(*) as total FROM products WHERE approval_status = 'Pending'";
$pending_post_res = $conn->query($pending_post_query);
$pending_post_count = $pending_post_res->fetch_assoc()['total'] ?? 0;

// Count reports that are still 'Pending'
$pending_report_query = "SELECT COUNT(*) as total FROM reports WHERE status = 'Pending'";
$pending_report_res = $conn->query($pending_report_query);
$pending_report_count = $pending_report_res->fetch_assoc()['total'] ?? 0;

// Total combined notifications
$total_admin_notifs = $pending_post_count + $pending_report_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | UPMart</title>
    <link rel="stylesheet" href="admin-panel.css">
    <link rel="icon" href="../images/favicon.png" type="image/png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <img src="../images/logo.png" class="logo-img" alt="UPMart Logo">
        </div>

        <img src="../images/profile.jpg" alt="Profile" class="profile-img">
        <div class="profile-info">
            <span class="profile-name">Admin</span>
        </div>

        <ul class="nav-links">
            <li class="active">
                <a href="admin_main.php"><span>🏠︎</span> Dashboard</a>
            </li>
            <li>
                <a href="admin_post.php" style="background: #e1f5da; color: black;"><span>📮</span> Posts</a>
            </li>
            <li>
                <a href="admin_report.php"><span>🔔</span> Reports</a>
            </li>
            <div class="logout-container">
                <a href="../dashboard/logout.php" class="logout-btn" style="text-decoration:none; display:block; text-align:center;">Logout</a>
            </div>
        </ul>
    </div>

    <div class="main-content">
        <nav class="top-nav">
            <h1 style="font-size: 1.4rem; margin-top: 10px;">📮 Posts</h1>
            <div class="status-indicators">
                <!-- Added onclick="toggleNotifSidebar()" to the button -->
                <button class="icon-btn" onclick="toggleNotifSidebar()" style="position: relative; margin-left: 50px;">
                    <span class="material-icons">notifications</span>
                    <?php if ($total_admin_notifs > 0): ?>
                        <span class="notif-badge" id="adminNotifBadge" style="
                            background: #9a0000; 
                            color: white; 
                            position: absolute; 
                            top: -2px; 
                            right: -2px; 
                            border-radius: 50%; 
                            padding: 2px 6px; 
                            font-size: 0.7rem; 
                            font-weight: bold;
                            border: 2px solid white;
                        ">
                            <?= $total_admin_notifs ?>
                        </span>
                    <?php endif; ?>
                </button>
            </div>
        </nav>

        <!-- Admin Notification Drawer -->
        <div class="notif-drawer" id="notifDrawer">
            <div class="drawer-header">
                <div class="header-left">
                    <h2 style="margin:0; font-size:1.2rem;">Notifications</h2>
                    <span id="notif-status-text" class="update-count" style="font-size:0.8rem; color:#888;">Recent updates</span>
                </div>
                <button class="close-drawer" id="closeNotifBtn" style="cursor:pointer; font-size: 24px;">&times;</button>
            </div>
            <div class="drawer-body" id="notif-list-container">
                <!-- JS will inject notifications here -->
            </div>
        </div>

        <div class="content-row">
            <div class="about-text">
                <p>Scan through pending posts from sellers across the campus.</p>
            </div>
        </div>

        <section class="dashboard-grid admin-review-grid">
            <div class="posts-review-container">
                <div class="review-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <h3>Pending Post</h3>
                    <?php if ($pending_post_count > 0): ?>
                        <span class="count-badge" style="background: maroon; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;">
                            <?= $pending_post_count ?> Pending
                        </span>
                    <?php else: ?>
                        <span class="count-badge" style="background: #e1f5da; color: #2e7d32; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem;">
                            All Caught Up!
                        </span>
                    <?php endif; ?>
                </div>

                <div class="posts-list" id="pendingPosts">
                    <?php if ($posts->num_rows > 0): ?>
                        <script>
                            console.log("DEBUG: admin_post.php has loaded correctly.");
                        </script>
                        <?php while ($row = $posts->fetch_assoc()): 
                            // Handle Product Image
                            $all_images = explode(',', $row['all_images'] ?? '');
                            $first_image = trim($all_images[0]);
                            $product_img = !empty($first_image) ? '../dashboard/' . $first_image : '../dashboard/uploads/default.jpg';

                            // Handle Seller Profile Image
                            $s_img = $row['seller_img'];
                            $clean_s_img = str_replace('uploads/', '', $s_img);
                            $seller_display_img = ($clean_s_img === 'profile.jpg' || empty($clean_s_img)) 
                                ? '../images/profile.jpg' 
                                : '../dashboard/uploads/' . $clean_s_img;
                        ?>
                            <!-- CORRECTED: One opening div that wraps the entire card -->
                            <div class="post-item" onclick='showPreview(
                                    "<?= addslashes(str_replace(["\r", "\n"], " ", $row["title"])) ?>", 
                                    "<?= addslashes($row["full_name"]) ?>", 
                                    "₱<?= number_format($row["price"], 2) ?>", 
                                    "<?= addslashes(str_replace(["\r", "\n"], " ", $row["description"])) ?>", 
                                    "<?= addslashes($product_img) ?>",
                                    "<?= addslashes($row["category_name"]) ?>", 
                                    "<?= addslashes($seller_display_img) ?>"
                                )'>

                                <div class="post-details">
                                    <!-- Ensure this image in the list card is also sized correctly -->
                                    <img src="<?= $product_img ?>" alt="Product" class="item-img" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                    <div class="item-info">
                                        <h4><?= htmlspecialchars($row['title']) ?></h4>
                                        <p>Seller: <strong><?= htmlspecialchars($row['full_name']) ?></strong> • ₱<?= number_format($row['price'], 2) ?></p>
                                        <span class="category-tag"><?= htmlspecialchars($row['category_name']) ?></span>
                                    </div>
                                </div>
                                <div class="post-actions">
                                    <a href="admin_post.php?action=approve&id=<?= $row['product_id'] ?>" class="approve-btn" style="text-decoration:none;">
                                        <span class="material-icons">check</span> Approve
                                    </a>
                                    <a href="admin_post.php?action=delete&id=<?= $row['product_id'] ?>" class="delete-btn" style="text-decoration:none;" onclick="return confirm('Delete this post?')">
                                        <span class="material-icons">delete_outline</span> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px;">
                            <span class="material-icons" style="font-size: 48px; color: #ccc;">done_all</span>
                            <p style="color:#888;">No pending posts to review.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="preview-panel" id="previewPanel">
                <div class="empty-state" id="emptyState">
                    <span class="material-icons">manage_search</span>
                    <p>Select a post to inspect details</p>
                </div>

                <!-- The actual card-style content -->
                <div class="preview-content card-format" id="previewContent" style="display: none; scale: 0.95">
                    <div class="card-header">
                        <div class="user-meta">
                            <img id="prevUserImg" src="../images/profile.jpg" class="user-avatar">
                            <div class="user-info">
                                <h4 id="prevSeller">Seller Name</h4>
                                <span id="prevCategory" class="category-text">Category</span>
                            </div>
                        </div>
                        <div class="price-badge" id="prevPrice">₱0.00</div>
                    </div>

                    <h3 id="prevTitle" class="item-title-text">Item Title</h3>

                    <div class="media-container">
                        <img id="prevImg" src="" alt="Product Large">
                    </div>

                    <div class="description-area">
                        <h5>Description</h5>
                        <p id="prevDesc">Full description goes here...</p>
                    </div>

                    <button class="buy-product-btn">
                        <span class="material-icons">shopping_bag</span> Buy Product
                    </button>
                </div>
            </div>
        </section>
    </div>

    <script src="admin-panel.js"></script>
</body>

</html>