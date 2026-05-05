<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
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
$query = "SELECT full_name, profile_pic, is_setup_complete FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$profile_img = (!empty($user['profile_pic'])) 
               ? "uploads/" . $user['profile_pic'] 
               : "../images/profile.jpg";

$show_setup_overlay = (($user['is_setup_complete'] ?? 0) == 0);


// Fetch Wishlist Items
$wish_query = "SELECT w.*, u.full_name FROM wishlist w 
               JOIN users u ON w.user_id = u.user_id 
               ORDER BY w.created_at DESC LIMIT 6";
$wish_results = $conn->query($wish_query);

// Count unread notifications
$notif_count_query = "SELECT COUNT(*) as total FROM notifications WHERE user_id = $user_id AND is_read = 0";
$notif_count_res = $conn->query($notif_count_query);
$unread_count = $notif_count_res->fetch_assoc()['total'] ?? 0;
$notif_list_query = "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5";
$notif_list_res = $conn->query($notif_list_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPMart | Dashboard</title>
    <link rel="stylesheet" href="../dashboard/main-panel.css">
    <link rel="icon" href="../images/favicon.png" type="image/png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body>
    <?php if ($show_setup_overlay): ?>
        <style>
            body {
                overflow: hidden !important;
                height: 100vh !important;
            }

            .setup-full-overlay {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                background: rgba(0, 0, 0, 0.4) !important;
                backdrop-filter: blur(15px) !important;
                -webkit-backdrop-filter: blur(15px) !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
                z-index: 999999 !important;
            }

            .setup-modal {
                background: white !important;
                padding: 35px !important;
                border-radius: 20px !important;
                width: 95% !important;
                max-width: 500px !important;
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3) !important;
            }

            .setup-form {
                display: flex;
                flex-direction: column;
                gap: 15px;
                text-align: left;
            }

            .setup-form label {
                font-weight: 600;
                color: #444;
                margin-bottom: -10px;
            }

            .setup-form input,
            .setup-form textarea {
                padding: 12px;
                border: 1px solid #ddd;
                border-radius: 10px;
            }

            .btn-finish {
                background: maroon !important;
                color: white !important;
                padding: 15px;
                border: none;
                border-radius: 12px;
                font-weight: bold;
                cursor: pointer;
                margin-top: 10px;
            }
        </style>

        <div class="setup-full-overlay">
            <div class="setup-modal">
                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="../images/logo.png" style="width: 100px;">
                    <h2 style="color: maroon; margin: 10px 0 0 0;">Complete Your Profile</h2>
                    <p style="color: #666; font-size: 0.9rem;">Set up your details to start using UPMart.</p>
                </div>

                <form action="../process_setup.php" method="POST" enctype="multipart/form-data" class="setup-form">
                    <label>Profile Picture</label>
                    <input type="file" name="profile_pic" accept="image/*" required>

                    <label>Bio</label>
                    <textarea name="bio" placeholder="Tell us about yourself..." required></textarea>

                    <label>Phone Number</label>
                    <input type="text" name="phone_number" placeholder="e.g. 09123456789" required>

                    <button type="submit" class="btn-finish">Save</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="sidebar">
        <div class="sidebar-brand">
            <img src="../images/logo.png" class="logo-img sidebar-logo" alt="UPMart Logo">
        </div>

        <div class="profile-container">
            <img src="<?= $profile_img ?>" class="profile-img">
        </div>

        <div class="profile-info">
            <span class="profile-name"><?= htmlspecialchars($user['full_name']) ?></span>
        </div>

        <ul class="nav-links">
            <li class="active">
                <a href="mainweb.php"><span>🏠︎</span>Dashboard</a>
            </li>
            <li><a href="marketplace.php"><span>🛒</span>Marketplace</a></li>
            <div class="logout-container">
                <a href="logout.php" class="logout-btn" style="text-decoration: none; display: block; text-align: center;">Logout</a>
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
                            <?php while ($wish = $wish_results->fetch_assoc()): ?>
                                <div class="wish-card">
                                    <div class="wish-info">
                                        <span class="category-tag"><?= htmlspecialchars($wish['category']) ?></span>
                                        <h4><?= htmlspecialchars($wish['item_name']) ?></h4>
                                        <p>Requested by: <strong><?= htmlspecialchars($wish['full_name']) ?></strong></p>
                                    </div>
                                    <button class="match-btn" onclick="handleMatch(<?= $wish['wish_id'] ?>)">I have this!</button>
                                </div>
                            <?php endwhile; ?>  
                        <?php else: ?>
                            <p style="padding: 20px; color: #888;">No wishes found. Be the first to ask for something!</p>
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

    <div id="reportModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Report Issue</h3>
                <span class="close-modal" id="closeModal">&times;</span>
            </div>

            <form id="reportForm">
                <input type="hidden" id="reportedUserId" name="reported_user_id" value="0">

                <label for="reportType">Unsername</label>           
                <input type="text" id="reportedUserId" name="reported_user_id">

                <label for="reportType">Reason for Report</label>
                <select id="reportType" required>
                    <option value="">Select a reason...</option>
                    <option value="scam">Potential Scam / Fraud</option>
                    <option value="inappropriate">Inappropriate Content</option>
                    <option value="misleading">Misleading Description</option>
                    <option value="other">Other</option>
                </select>

                <label for="reportDetails">Details</label>
                <textarea id="reportDetails" placeholder="Please describe the issue in detail..." required></textarea>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn-submit">Submit Report</button>
                </div>
            </form>
        </div>
    </div>
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
    <div class="notif-drawer" id="notifDrawer">
        <div class="drawer-header">
            <div class="header-left">
                <h2 style="margin:0; font-size:1.2rem;">Notifications</h2>
                <span id="notif-status-text" class="update-count" style="font-size:0.8rem; color:#888;">Recent updates</span>
            </div>
            <button class="close-drawer" id="closeNotifBtn" style="cursor:pointer; font-size: 24px;">&times;</button>
        </div>
        <div class="drawer-body" id="notif-list-container">
            <!-- JS will inject .notif-card elements here -->
        </div>
    </div>
    <script src="maindash.js"></script>
</body>

</html>