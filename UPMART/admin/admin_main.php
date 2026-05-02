<?php
session_start();
include '../db_connect.php';

// 1. Guard: Only allow Admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 2. Fetch Dynamic Stats for your format
// Get counts from products table
$sold_res = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'Sold'");
$sold_count = $sold_res->fetch_assoc()['total'];

$avail_res = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'Available'");
$avail_count = $avail_res->fetch_assoc()['total'];

$trans_res = $conn->query("SELECT COUNT(*) as total FROM transactions");
$trans_count = $trans_res->fetch_assoc()['total'];

// 3. Calculation for the progress rings (Progress ring circumference is approx 220)
$ring_total = $sold_count + $avail_count;

// Sold vs Total Progress (left ring)
$sold_percent = ($ring_total > 0) ? round(($sold_count / $ring_total) * 100) : 0;
$offset_sold = 220 - (220 * ($sold_percent / 100));

// Available vs Total Progress (right ring)
$avail_percent = ($ring_total > 0) ? round(($avail_count / $ring_total) * 100) : 0;
$offset_avail = 220 - (220 * ($avail_percent / 100));

// 4. Fetch your static database data for the lists
$recent_users = $conn->query("SELECT full_name, role FROM users WHERE role != 'admin' ORDER BY created_at DESC LIMIT 4");

// Fetch Top Categories by Inquiry Count
$cat_query = "SELECT c.category_name, COUNT(t.inquiry_id) as sales_count 
              FROM transactions t 
              JOIN products p ON t.product_id = p.product_id 
              JOIN categories c ON p.category_id = c.category_id 
              GROUP BY c.category_id 
              ORDER BY sales_count DESC 
              LIMIT 5";
$cat_result = $conn->query($cat_query);

$cat_labels = [];
$cat_data = [];

if ($cat_result && $cat_result->num_rows > 0) {
    while ($row = $cat_result->fetch_assoc()) {
        $cat_labels[] = $row['category_name'];
        $cat_data[] = $row['sales_count'];
    }
} else {
    // Default placeholder so the chart doesn't break if table is empty
    $cat_labels = ['No Inquiries Yet'];
    $cat_data = [1];
}

// 5. Fetch Top Sellers (Users with the most product listings)
$seller_query = "SELECT u.full_name, COUNT(p.product_id) as item_count 
                 FROM users u 
                 JOIN products p ON u.user_id = p.seller_id 
                 GROUP BY u.user_id 
                 ORDER BY item_count DESC 
                 LIMIT 3";
$top_sellers = $conn->query($seller_query);

// 6. Fetch Top Buyers (Users with the most inquiries/transactions)
$buyer_query = "SELECT u.full_name, COUNT(t.inquiry_id) as total_buys 
                FROM users u 
                JOIN transactions t ON u.user_id = t.buyer_id 
                GROUP BY u.user_id 
                ORDER BY total_buys DESC 
                LIMIT 3";
$top_buyers = $conn->query($buyer_query);

// Fetch Top 5 Categories based on product count
$top_cats_query = "SELECT c.category_name, COUNT(p.product_id) as item_count 
                   FROM categories c 
                   LEFT JOIN products p ON c.category_id = p.category_id 
                   GROUP BY c.category_id 
                   ORDER BY item_count DESC 
                   LIMIT 5";
$top_cats_result = $conn->query($top_cats_query);

// Prepare data for the Doughnut Chart
$chart_labels = [];
$chart_data = [];

if ($top_cats_result && $top_cats_result->num_rows > 0) {
    while ($cat = $top_cats_result->fetch_assoc()) {
        $chart_labels[] = $cat['category_name'];
        $chart_data[] = $cat['item_count'];
    }
}

// 1. Count Total Successful Transactions (Bought Items)
$bought_res = $conn->query("SELECT COUNT(*) as total FROM transactions");
$bought_count = $bought_res->fetch_assoc()['total'];

// 2. Count Total Items Marked as 'Sold' (Sold Items)
$sold_res = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'Sold'");
$sold_count = $sold_res->fetch_assoc()['total'];

// 3. Get Total Items (Available + Sold) to calculate percentages
$total_res = $conn->query("SELECT COUNT(*) as total FROM products");
$total_items = $total_res->fetch_assoc()['total'];

// 4. Calculate Percentages for the Rings
// (We use 220 as the max stroke-dashoffset for a full circle)
$bought_percent = ($total_items > 0) ? round(($bought_count / $total_items) * 100) : 0;
$sold_percent = ($total_items > 0) ? round(($sold_count / $total_items) * 100) : 0;

$offset_bought = 220 - (220 * ($bought_percent / 100));
$offset_sold = 220 - (220 * ($sold_percent / 100));

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
                <a href="admin_main.php" style="background: #e1f5da; color: black;"><span>🏠︎</span> Dashboard</a>
            </li>
            <li>
                <a href="admin_post.php"><span>📮</span> Posts</a>
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
            <h1 style="font-size: 1.4rem; margin-top: 10px;">🏠︎ Dashboard</h1>
            <div class="status-indicators">
                <button class="icon-btn" id="notifTrigger"><span class="material-icons">notifications</span><span
                        class="notif-badge"></span></button>
            </div>
        </nav>

        <div class="content-row">
            <div class="about-text">
                <h1 style="color: maroon; margin-top: 30px;">Welcome, Admin!</h1>
                <p>Check what's currently happening.</p>
            </div>
        </div>

        <section class="dashboard-grid">
            <div class="left-column">
                <section class="stat-card">
                    <div class="progress-container">
                        <svg class="progress-ring" width="80" height="80">
                            <circle class="ring-bg" cx="40" cy="40" r="35" style="stroke: #f0f0f0; stroke-width: 5; fill: none;"></circle>
                            <circle class="ring-fill" cx="40" cy="40" r="35"
                                style="stroke: #2e7d32; stroke-width: 5; fill: none; stroke-linecap: round; transition: stroke-dashoffset 1s ease; 
                                    stroke-dasharray: 220; stroke-dashoffset: <?= $offset_bought ?>;">
                            </circle>
                        </svg>
                        <div class="percentage-label" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold;">
                            <?= $bought_percent ?>%
                        </div>
                    </div>
                    <div class="stat-details">
                        <span class="stat-title" style="color: #888; font-size: 0.8rem;">Bought Items</span>
                        <h2 class="stat-number" style="margin: 0; font-size: 1.8rem;"><?= $bought_count ?></h2>
                    </div>

                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div class="progress-container" style="position: relative;">
                            <svg class="progress-ring" width="80" height="80">
                                <circle class="ring-bg" cx="40" cy="40" r="35" style="stroke: #f0f0f0; stroke-width: 5; fill: none;"></circle>
                                <circle class="ring-fill" cx="40" cy="40" r="35"
                                    style="stroke: maroon; stroke-width: 5; fill: none; stroke-linecap: round; transition: stroke-dashoffset 1s ease; 
                                    stroke-dasharray: 220; stroke-dashoffset: <?= $offset_sold ?>;">
                                </circle>
                            </svg>
                            <div class="percentage-label" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold;">
                                <?= $sold_percent ?>%
                            </div>
                        </div>
                        <div class="stat-details">
                            <span class="stat-title" style="color: #888; font-size: 0.8rem;">Sold Items</span>
                            <h2 class="stat-number" style="margin: 0; font-size: 1.8rem;"><?= $sold_count ?></h2>
                        </div>
                    </div>

                </section>

                <div class="tops-container" style="background: white; padding: 15px; border-radius: 15px; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <span style="font-weight: 800; color: #1a1a2e; display: block; margin-bottom: 10px;">🏆 Top Sellers</span>
                    <?php if ($top_sellers && $top_sellers->num_rows > 0): ?>
                        <?php while ($seller = $top_sellers->fetch_assoc()): ?>
                            <div style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #f9f9f9;">
                                <span style="font-size: 0.9rem;"><?= htmlspecialchars($seller['full_name']) ?></span>
                                <span style="font-weight: bold; color: maroon;"><?= $seller['item_count'] ?> Posts</span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <small style="color: #888;">No sellers found.</small>
                    <?php endif; ?>
                </div>

                <div class="topb-container" style="background: white; padding: 15px; border-radius: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <span style="font-weight: 800; color: maroon; display: block; margin-bottom: 10px;">🛍️ Top Buyers</span>
                    <?php if ($top_buyers && $top_buyers->num_rows > 0): ?>
                        <?php while ($buyer = $top_buyers->fetch_assoc()): ?>
                            <div style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #f9f9f9;">
                                <span style="font-size: 0.9rem;"><?= htmlspecialchars($buyer['full_name']) ?></span>
                                <span style="font-weight: bold; color: #1a1a2e;"><?= $buyer['total_buys'] ?> Orders</span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <small style="color: #888;">No buyers found.</small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-main-grid">
                <div class="admin-left-col">
                    <div class="users" style="background: white; padding: 20px; border-radius: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                        <h3 style="margin-bottom: 15px; color: #1a1a2e;">Users</h3>

                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="text-align: left; border-bottom: 2px solid #f0f0f0;"></tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_users && $recent_users->num_rows > 0): ?>
                                    <?php while ($user = $recent_users->fetch_assoc()): ?>
                                        <tr style="border-bottom: 1px solid #f9f9f9;">
                                            <td style="padding: 12px 10px; font-size: 0.9rem; font-weight: 600; color: #333;">
                                                <?= htmlspecialchars($user['full_name']) ?>
                                            </td>
                                            <td style="padding: 12px 10px; text-align: right;">
                                                <span style="font-size: 0.75rem; padding: 4px 8px; border-radius: 4px; background: <?= ($user['role'] === 'admin') ? '#ffebee' : '#e1f5da'; ?>; color: <?= ($user['role'] === 'admin') ? 'maroon' : '#2e7d32'; ?>; font-weight: bold;">
                                                    <?= strtoupper(htmlspecialchars($user['role'])) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" style="padding: 20px; text-align: center; color: #888;">No users found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="admin-right-col">
                    <div class="chart-container" style="background: white; padding: 20px; border-radius: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                        <h3 style="margin-bottom: 15px; color: #1a1a2e;">Top 5 Categories</h3>

                        <div style="height: 200px; margin-bottom: 20px;">
                            <canvas id="myChart"></canvas>
                        </div>

                        <div class="ranking-list">
                            <?php
                            // Check if we actually have data
                            if ($top_cats_result && $top_cats_result->num_rows > 0):
                                // Reset pointer to loop through the results again
                                $top_cats_result->data_seek(0);
                                $rank = 1;
                                while ($cat = $top_cats_result->fetch_assoc()): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f0f0;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span style="background: maroon; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold;">
                                                <?= $rank++ ?>
                                            </span>
                                            <span style="font-size: 0.9rem; font-weight: 600;"><?= htmlspecialchars($cat['category_name']) ?></span>
                                        </div>
                                        <span style="font-size: 0.85rem; color: #666;"><?= $cat['item_count'] ?> items</span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 30px; color: #888;">
                                    <span class="material-icons" style="font-size: 3rem; color: #ccc;">category</span>
                                    <p style="margin-top: 10px; font-weight: 600;">No categories posted yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="footer">
            <p style="margin-top: 68px; text-align: right; margin-right: 2vh; color:grey">&copy;2026 UPMart. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="admin-dash.js"></script>
</body>

</html>