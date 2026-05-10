<?php
include '../db_connect.php';
session_start();

// 1. Guard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
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
            u_seller.full_name AS seller_name, 
            u_seller.profile_pic AS seller_img, 
            u_seller.user_id AS seller_id, -- THIS is your reported_user_id
            c.category_name AS cat_name
          FROM reports r
          LEFT JOIN products p ON r.product_id = p.product_id
          LEFT JOIN users u_seller ON p.seller_id = u_seller.user_id
          LEFT JOIN categories c ON p.category_id = c.category_id";

if ($view === 'banned') {
    // Show only the reports you specifically decided to ban
    $query .= " WHERE r.decision = 'banned'";
} else {
    // Default view shows only pending reports
$query .= " WHERE r.status = 'Pending' AND r.decision = 'none'";
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
                <button class="icon-btn" onclick="toggleNotifSidebar()" style="position: relative;">
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
                            <option value="other">Other</option>
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
                    <?php if($view === 'banned'): ?>
                        <a href="admin_report.php" style="font-size: 0.8rem; color: #666;">Back to Pending</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="reports-list">
                <?php if ($reports->num_rows > 0): ?>
                    <?php while ($row = $reports->fetch_assoc()): ?>
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
                                    '<?= addslashes($row['prod_title'] ?? 'Product Removed') ?>',
                                    '<?= addslashes($row['prod_desc'] ?? '') ?>',
                                    '<?= addslashes($row['reason']) ?>',
                                    '<?= addslashes($row['details']) ?>',
                                    '<?= $row['report_id'] ?>', 
                                    '<?= $row['seller_id'] ?>',
                                    '<?= $row['product_id'] ?>',
                                    '<?= number_format($row['price'], 2) ?>',
                                    '<?= addslashes($row['cat_name'] ?? 'General') ?>',
                                    '<?= addslashes($row['seller_name'] ?? 'Unknown User') ?>',
                                    '<?= $row['seller_img'] ?? '../images/profile.jpg' ?>',
                                    '<?= $row['warning_count'] ?? 0 ?>' // Added this parameter
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
                    <img id="side-seller-img" src="../images/profile.jpg" class="side-avatar" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;">
                    <div>
                        <strong id="side-seller-name" style="font-size: 0.95rem; display: block;"></strong>
                        <div style="font-size: 0.7rem; color: #e65100; background: #fff3e0; padding: 1px 6px; border-radius: 4px; display: inline-block; border: 1px solid #ffe0b2;">
                            Strikes: <span id="side-warning-count">0</span>/3
                        </div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.7rem; color: #666; text-transform: uppercase;">Price</div>
                    <div style="font-size: 1.2rem; font-weight: 800; color: #2e7d32;">
                        ₱<span id="side-price"></span>
                    </div>
                </div>
            </div>

            <div style="padding: 0 5px;">
                <span id="side-category" style="color: #9a0000; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;"></span>
                <h2 id="side-title" style="margin: 5px 0 15px 0; font-size: 1.3rem;"></h2>
                
                <div style="color: #555; font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px;" id="side-desc"></div>

                <div style="background: #fff5f5; border-left: 4px solid #9a0000; padding: 12px; border-radius: 4px;">
                    <strong style="font-size: 0.8rem; color: #9a0000;">REPORT REASON: <span id="side-reason"></span></strong>
                    <p id="side-details" style="margin: 5px 0 0 0; font-size: 0.85rem; color: #444;"></p>
                </div>
            </div>
        </div>

        <div class="sidebar-footer" style="padding: 20px; border-top: 1px solid #eee;">
            <button type="button" onclick="adminAction('warning')" style="background: #ff9800; width: 100%; padding: 12px; border: none; border-radius: 8px; color: white; font-weight: bold; cursor: pointer; margin-bottom: 10px;">
                Issue Warning (Strike)
            </button>
            <button type="button" onclick="adminAction('ban')" style="background: #1a1a2e; width: 100%; padding: 12px; border: none; border-radius: 8px; color: white; font-weight: bold; cursor: pointer;">
                Ban Seller & Delete Post    
            </button>
        </div>  
    </div> 

    <script src="admin-panel.js"></script>
</body>
</html>