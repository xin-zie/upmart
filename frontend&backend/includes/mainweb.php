<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- REPORT SUBMISSION LOGIC (AJAX ONLY) ---
if (isset($_POST['submit_report'])) {
    header('Content-Type: application/json');
    
    $reporter_id = $_SESSION['user_id'];
    $reported_id = intval($_POST['reported_user_id']); // Offender ID passed from JS
    $reason      = mysqli_real_escape_string($conn, $_POST['reason']);
    $details     = mysqli_real_escape_string($conn, $_POST['details']);

    // Insert into reports table
    $sql = "INSERT INTO reports (user_id, reported_user_id, reason, details, status) 
            VALUES ($reporter_id, $reported_id, '$reason', '$details', 'Pending')";

    $ok = $conn->query($sql);
    echo json_encode(['success' => (bool)$ok]);
    exit(); // Stop further execution to keep the response clean
}

// Fetch User Data
$query = "SELECT full_name, profile_pic FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$profile_img = (!empty($user['profile_pic']) && file_exists($user['profile_pic'])) 
               ? $user['profile_pic'] 
               : "../images/profile.jpg"; 

// Fetch Wishlist Items
$wish_query = "SELECT w.*, u.full_name FROM wishlist w 
               JOIN users u ON w.user_id = u.user_id 
               ORDER BY w.created_at DESC LIMIT 6";
$wish_results = $conn->query($wish_query);

// Count unread notifications
$notif_count_query = "SELECT COUNT(*) as total FROM notifications WHERE user_id = $user_id AND is_read = 0";
$notif_count_res = $conn->query($notif_count_query);
$unread_count = $notif_count_res->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPMart | Dashboard</title>
    <link rel="stylesheet" href="mainpanel.css">
    <link rel="icon" href="favicon.png" type="image/png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <img src="../images/logo.png" class="logo-img sidebar-logo" alt="UPMart Logo">
        </div>

        <img src="<?= $profile_img ?>" alt="Profile" class="profile-img">
        
        <div class="profile-info">
            <span class="profile-name"><?= htmlspecialchars($user['full_name']) ?></span>
        </div>

        <ul class="nav-links">
            <li class="active">
                <a href="mainweb.php"><span>🏠︎</span>Dashboard</a>
            </li>
            <li><a href="../marketplace/marketplace.php"><span>🛒</span>Marketplace</a></li>
            <div class="logout-container">
                <a href="logout.php" class="logout-btn" style="text-decoration: none; display: block;">Logout</a>
            </div>
        </ul>
    </div>

    <div class="main-content">
        <div class="dash-header">
            <h1 style="font-size: 1.4rem; margin-top: 10px;"><span>🏠︎</span> Dashboard</h1>

            <div class="status-indicators">
                <button class="icon-btn" id="notifTrigger" style="position: relative;">
                    <span class="material-icons">notifications</span>
                    <?php if ($unread_count > 0): ?>
                        <span class="notif-badge" style="background: #9a0000; color: white; position: absolute; top: -2px; right: -2px; border-radius: 50%; padding: 2px 5px; font-size: 0.65rem; font-weight: bold; border: 2px solid white;">
                            <?= $unread_count ?>
                        </span>
                    <?php endif; ?>
                </button>
                <!-- General report button (defaults to system report if no specific user) -->
                <button class="icon-btn" onclick="openReportModal(0)"><span class="material-icons">report</span></button>
            </div>
        </div>

        <div class="content-row">
            <div class="about-text">
                <h1 style="color: maroon;">Welcome back, <?= htmlspecialchars($user['full_name']) ?>!</h1>
                <p>Start exploring our marketplace and discover amazing products!</p>
            </div>
        </div>

        <section class="dashboard-grid">
            <div class="left-column">
                <section class="wishlist-section">
                    <div class="section-header">
                        <h3>Wishlist Matches</h3>
                        <button class="add-wish-btn" id="addWishBtn">+ Add My Wish</button>
                    </div>
                    <div class="wish-grid">
                        <?php if ($wish_results->num_rows > 0): ?>
                            <?php while($wish = $wish_results->fetch_assoc()): ?>
                                <div class="wish-card">
                                    <div class="wish-info">
                                        <span class="category-tag"><?= htmlspecialchars($wish['category']) ?></span>
                                        <h4><?= htmlspecialchars($wish['item_name']) ?></h4>
                                        <p>Requested by: <strong><?= htmlspecialchars($wish['full_name']) ?></strong></p>
                                    </div>
                                    <!-- Here we can add a report icon specifically for a wish owner if needed -->
                                   <button class="match-btn" onclick="handleMatch(<?= $wish['wish_id'] ?>)">I have this!</button>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="padding: 20px; color: #888;">No wishes found.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <div class="promo-banner">
                    <div class="promo-text">
                        <span class="tag">UPMart Featured</span>
                        <h2>End of Sem Clearance!</h2>
                        <p>Dormers leaving campus are selling essentials at 50% off.</p>
                        <button class="promo-btn">Browse Sale</button>
                    </div>
                    <div class="promo-icon">
                        <span class="material-icons">local_fire_department</span>
                    </div>
                </div>
            </div>

            <div class="bulletin-board">
                <div class="bulletin-header">
                    <h3>UPMart Bulletin</h3>
                    <span class="limit-info">Max 100 chars</span>
                </div>
                <div class="bulletin-input">
                    <textarea id="bulletinText" placeholder="Post a quick update..." maxlength="100"></textarea>
                    <button id="postBtn">Post</button>
                </div>
                <div id="bulletinList" class="bulletin-list"></div>
            </div>
        </section>

        <div class="footer">
            <p>&copy;2026 UPMart. All rights reserved.</p>
        </div>
    </div>
   
    <!-- REPORT MODAL -->
    <div id="reportModal" class="modal-overlay">
        <div class="modal-content"> 
            <div class="modal-header">
                <h3>Report Issue</h3>
                <span class="close-modal" id="closeModal">&times;</span>
            </div>

            <form id="reportForm">
                <!-- Hidden ID of the seller/user being reported -->
                <input type="hidden" id="reportedUserId" name="reported_user_id" value="0">

                <label for="reportType">Unsername</label>           
                <input type="text" id="reportedUserId" name="reported_user_id">

                <label for="reportType">Reason for Report</label>
                <select id="reportType" name="reason" required>
                    <option value="">Select a reason...</option>
                    <option value="scam">Potential Scam / Fraud</option>
                    <option value="inappropriate">Inappropriate Content</option>
                    <option value="misleading">Misleading Description</option>
                    <option value="other">Other</option>
                </select>

                <label for="reportDetails">Details</label>
                <textarea id="reportDetails" name="details" placeholder="Please describe the issue in detail..." required></textarea>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn-submit">Submit Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- WISH MODAL -->
    <div id="wishModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Make a Wish</h3>
                <span class="close-modal" id="closeWishModal">&times;</span>
            </div>
            <form id="wishForm">
                <label for="wishItem">What are you looking for?</label>
                <input type="text" id="wishItem" name="item_name" placeholder="e.g. Math 21 Reviewer" required maxlength="100">

                <label for="wishCategory">Category</label>
                <select id="wishCategory" name="category" required>
                    <option value="Books">Books</option>
                    <option value="Dorm">Dorm Essentials</option>
                    <option value="Food">Food</option>
                    <option value="Electronics">Electronics</option>
                    <option value="Other">Other</option>
                </select>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancelWishBtn">Cancel</button>
                    <button type="submit" class="btn-submit">Post Wish</button>
                </div>
            </form>
        </div>
    </div>

    <!-- NOTIFICATION DRAWER -->
    <div class="notif-drawer" id="notifDrawer">
        <div class="drawer-header">
            <div class="header-left">
                <h2 style="margin:0; font-size:1.2rem;">Notifications</h2>
                <span id="notif-status-text" class="update-count" style="font-size:0.8rem; color:#888;">Recent updates</span>
            </div>
            <button class="close-drawer" id="closeNotifBtn" style="cursor:pointer; font-size: 24px;">&times;</button>
        </div>
        <div class="drawer-body" id="notif-list-container"></div>
    </div>

    <script src="maindash.js"></script>
</body>
</html>
