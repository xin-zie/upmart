<?php
session_start();
include '../db_connect.php'; 

// 1. Guard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 2. Fetch Reports with Error Catching
$query = "SELECT * FROM reports ORDER BY created_at DESC";
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
    <link rel="icon" href="favicon.png" type="image/png">
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
            <h1 style="font-size: 1.4rem; margin-top: 10px;">🏠︎ Dashboard</h1>
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

        <div class="content-row">
            <div class="about-text">
                <p>Review and take action on reported content or users.</p>
            </div>
        </div>

        <section class="reports-container">
            <div class="reports-header">
                <div class="header-text">
                    <h3>List of Reports</h3>
                </div>
                <div class="filter-options">
                    <select id="reportFilter">
                        <option value="all">All Reports</option>
                        <option value="scam">Scams</option>
                        <option value="harassment">Harassment</option>
                    </select>
                </div>
            </div>

            <div class="reports-list">
                <?php if ($reports->num_rows > 0): ?>
                    <?php while($row = $reports->fetch_assoc()): ?>
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
                                <button class="view-btn">Investigate</button>
                                <a href="admin_report.php?action=dismiss&id=<?= $row['report_id'] ?>" 
                                onclick="return confirm('Dismiss this report?')" 
                                class="dismiss-btn" style="text-decoration:none; padding: 10px; font-size: 0.8rem;">
                                Dismiss
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #888; padding: 20px;">No pending reports found.</p>
                <?php endif; ?>
            </div>
        </section>  
    </div>

    <script src="admin-panel.js"></script>
</body>

</html>