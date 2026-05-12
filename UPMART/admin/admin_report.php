<?php
include '../db_connect.php';
session_start();

// 1. Guard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Check which view the admin wants
$view = isset($_GET['view']) ? $_GET['view'] : 'active';

// 2. Fetch Reports with Error Catching
$query = "SELECT 
            r.*, 
            p.title AS prod_title, 
            p.description AS prod_desc, 
            p.price,
            p.product_id,
            (SELECT GROUP_CONCAT(image_path) FROM media WHERE product_id = r.product_id) as all_images, 
            c.category_name AS cat_name,
            -- FIXED: Prioritize seller for product reports, direct user for general reports
            CASE 
                WHEN p.product_id IS NOT NULL AND p.seller_id IS NOT NULL THEN u_seller.full_name
                ELSE COALESCE(u_direct.full_name, 'Unknown User')
            END AS seller_name,
            CASE 
                WHEN p.product_id IS NOT NULL AND p.seller_id IS NOT NULL THEN u_seller.profile_pic
                ELSE COALESCE(u_direct.profile_pic, 'profile.jpg')
            END AS seller_img,
            CASE 
                WHEN p.product_id IS NOT NULL AND p.seller_id IS NOT NULL THEN u_seller.user_id
                ELSE COALESCE(u_direct.user_id, 0)
            END AS seller_id,
            CASE 
                WHEN p.product_id IS NOT NULL AND p.seller_id IS NOT NULL THEN u_seller.warning_count
                ELSE COALESCE(u_direct.warning_count, 0)
            END AS warning_count
          FROM reports r
          LEFT JOIN products p ON r.product_id = p.product_id
          LEFT JOIN users u_seller ON p.seller_id = u_seller.user_id
          LEFT JOIN users u_direct ON r.reported_user_id = u_direct.user_id
          LEFT JOIN categories c ON p.category_id = c.category_id";
if ($view === 'banned') {
    $query .= " WHERE r.decision = 'banned'";
} else {
    // Ensuring we capture 'none' or empty strings
    $query .= " WHERE r.status = 'Pending' AND (r.decision = 'none' OR r.decision = '' OR r.decision IS NULL)";
}

$query .= " ORDER BY r.created_at DESC";
$reports = $conn->query($query);

if (!$reports) {
    // This will tell you if your table name or column names are wrong
    die("Query Failed: " . $conn->error);
}

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

$report_img = !empty($first_image) ? '../' . $first_image : '../images/default_product.png';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
                <a href="admin_post.php"><span>📮</span> Posts</a>
            </li>
            <li>
                <a href="admin_report.php" style="background: #e1f5da; color: black;"><span>🔔</span> Reports</a>
            </li>
            <div class="logout-container">
                <a href="../dashboard/logout.php" class="logout-btn" style="text-decoration:none; display:block; text-align:center;">Logout</a>
            </div>
        </ul>
    </div>

    <div class="main-content">
        <nav class="top-nav">
            <h1 style="font-size: 1.4rem; margin-top: 10px;">🔔 Reports</h1>
            <div class="status-indicators">
                <!-- Added onclick="toggleNotifSidebar()" to the button -->
                <button class="icon-btn" onclick="toggleNotifSidebar()" style="position: relative; margin-left: 25px;">
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
                <p>Review and take action on reported content or users.</p>
            </div>
        </div>

        <section class="reports-container">
            <div class="reports-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="header-text">
                    <h3>List of Reports</h3>
                </div>

                <div style="display: flex; gap: 10px; align-items: center;">
                    <div id="reportFilter">
                        <select class="reportType">
                            <option value="all">All Reports</option>
                            <option value="scam">Potential Scam / Fraud</option>
                            <option value="inappropriate">Inappropriate Content</option>
                            <option value="false_info">False Information</option>
                            <option value="spam">Spam</option>
                            <option value="sexual_activity">Sexual Activity</option>
                            <op tion value="other">Other</option>
                        </select>
                    </div>
                    <a href="admin_report.php?view=banned" class="view-banned-btn" style="
                        background-color: #9a0000; 
                        color: white; 
                        text-decoration: none; 
                        padding: 8px 15px; 
                        border-radius: 5px; 
                        font-size: 0.85rem; 
                        display: flex; 
                        align-items: center; 
                        gap: 5px;
                        font-weight: bold;">
                        <span class="material-icons" style="font-size: 1.1rem;">block</span> Banned Posts
                    </a>
                </div>
            </div>

            <div class="reports-list">
                <?php if ($reports->num_rows > 0): ?>
                    <?php while ($row = $reports->fetch_assoc()): 
                       $img_list = explode(',', $row['all_images'] ?? ''); 
                        $first_file = trim($img_list[0]);

                        // 2. Build the relative path for the Admin folder
                        // Since your DB stores "uploads/filename.jpg", we just add "../"
                        $report_img = !empty($first_file) ? '../' . $first_file : '../images/default_product.png';
                    ?>
                        <div class="report-card">
                            <div class="report-main">
                                <div class="report-type">
                                    <span class="material-icons warning-icon">report_problem</span>
                                    <div>
                                        <h4><?= htmlspecialchars($row['reason']) ?></h4>
                                        <span class="report-date">
                                            Reported <?= date('M d, g:i A', strtotime($row['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="report-parties">
                                    <div class="party">
                                        <small>Status: <?= htmlspecialchars($row['status']) ?></small><br>
                                        <small>Details:</small>
                                        <span><?= htmlspecialchars($row['details']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="report-actions">
                                <button class="view-btn" onclick="openInvestigate(
                                    '<?= addslashes(str_replace(["\r", "\n"], ' ', $row['prod_title'] ?? 'General Report')) ?>',
                                    '<?= addslashes(str_replace(["\r", "\n"], ' ', $row['prod_desc'] ?? '')) ?>',
                                    '<?= $report_img ?>',
                                    '<?= addslashes($row['reason'] ?? 'Other') ?>',
                                    '<?= addslashes(str_replace(["\r", "\n"], ' ', $row['details'] ?? '')) ?>',
                                    '<?= $row['report_id'] ?>',
                                    '<?= $row['seller_id'] ?? 0 ?>',
                                    '<?= $row['product_id'] ?? 0 ?>',
                                    '<?= number_format($row['price'] ?? 0, 2) ?>',
                                    '<?= addslashes($row['cat_name'] ?? 'General') ?>',
                                    '<?= addslashes($row['seller_name'] ) ?>',
                                    '<?= $row['seller_img'] ?? '../images/profile.jpg' ?>',
                                    '<?= $row['warning_count'] ?? 0 ?>'
                                )">Investigate</button>

                                <button type="button" 
                                        class="dismiss-btn" 
                                        style="cursor:pointer; padding: 10px; font-size: 0.8rem; border:none; background:none; color:#666;"
                                        onclick="dismissReport('<?= $row['report_id'] ?>', this.closest('.report-card'))">
                                    Dismiss
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #888; padding: 20px;">No pending reports found.</p>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 10px; align-items: center; justify-content: flex-end; margin-top: 15px;">
                <?php if ($view === 'banned'): ?>
                    <a href="admin_report.php" style="
                        background-color: #0c7507; 
                        color: white; 
                        text-decoration: none; 
                        padding: 4px 12px   ; 
                        border-radius: 15px; 
                        font-size: 0.7rem; 
                        display: flex; 
                        align-items: right; 

                        gap: 5px;
                        font-weight: bold;">
                        <span class="material-icons" style="font-size: 1.1rem;">arrow_back</span> Back to Pending
                    </a>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <div id="investigationSidebar" class="investigation-sidebar">
        <div class="sidebar-header">
            <h3>Investigate Post</h3>
            <button onclick="closeSidebar()" class="close-btn">&times;</button>
        </div>
        <div class="sidebar-body">
            <div class="side-card-header" style="background: #f8f9fa; padding: 15px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <img id="side-seller-img" src="" class="side-avatar" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;">
                    <div>
                        <strong id="side-seller-name" style="font-size: 0.95rem; display: block;"></strong>
                        <div style="font-size: 0.7rem; color: #e65100; background: #fff3e0; padding: 1px 6px; border-radius: 4px; display: inline-block; border: 1px solid #ffe0b2;">
                            Warning: <span id="side-warning-count">0</span>/3
                        </div>
                    </div>
                </div>
                <div id="side-price-container" style="text-align: right;">
                    <div style="font-size: 0.7rem; color: #666; text-transform: uppercase;">Price</div>
                    <div style="font-size: 1.2rem; font-weight: 800; color: #2e7d32;">
                        ₱<span id="side-price"></span>
                    </div>
                </div>
            </div>

            <div style="padding: 0 5px;">
                <span id="side-category" style="color: #9a0000; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;"></span>
                <h2 id="side-title" style="margin: 5px 0 15px 0; font-size: 1.3rem;"></h2>

                <img id="side-img" src="" alt="Product Image" class="side-main-img" style="width: 100%; border-radius: 10px; margin-bottom: 15px; object-fit: cover; max-height: 250px; border: 1px solid #eee; display: none;">

                <div style="color: #555; font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px;" id="side-desc"></div>

                <div style="background: #fff5f5; border-left: 4px solid #9a0000; padding: 12px; border-radius: 4px;">
                    <strong style="font-size: 0.8rem; color: #9a0000;">REPORT REASON: <span id="side-reason"></span></strong>
                    <p id="side-details" style="margin: 5px 0 0 0; font-size: 0.85rem; color: #444;"></p>
                </div>
            </div>
            <div class="sidebar-footer" style="padding: 20px; background: #fff; border-top: 1px solid #eee;">
                <button type="button" class="warning-btn-large" onclick="adminAction('warning')" style="background: #ff9800; width: 100%; padding: 14px; border: none; border-radius: 10px; color: white; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.3s; margin-bottom: 12px;">
                    <span class="material-icons">warning</span> Issue Warning 
                </button>

                <button type="button" class="ban-btn-large" onclick="adminAction('ban')" style="background: #1a1a2e; width: 100%; padding: 14px; border: none; border-radius: 10px; color: white; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.3s;">
                    <span class="material-icons">block</span> Ban Seller & Delete Post
                </button>
            </div>
        </div>
    </div>
    <script src="admin-panel.js"></script>
</body>

</html>