can you copy and paste to me the code here in differetn files:

mainweb.php:
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
    $reason      = mysqli_real_escape_string($conn, $_POST['reason']);
    $details     = mysqli_real_escape_string($conn, $_POST['details']);

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
                        <a href="marketplace.php" class="promo-btn">Browse Sale</a>
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

                <label for="reportType">Who do you want to report?</label>
                <input type="text" id="reportedUserId" name="reported_user_id" placeholder="Enter the name of the user you want to report" required>

                <label for="reportType">Reason for Report</label>
                <select id="reportType" required>
                    <option value="">Select a reason...</option>
                    <option value="scam">Potential Scam / Fraud</option>
                    <option value="inappropriate">Inappropriate Content</option>
                    <option value="false_info">False Information</option>
                    <option value="spam">Spam</option>
                    <option value="sexual_activity">Sexual Activity</option>
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
                    <option value="">Select Category</option>
                    <option value="Dorm Essentials">Dorm Essentials</option>
                    <option value="Arki Mats">Arki Mats</option>
                    <option value="Lab Essentials">Lab Essentials</option>
                    <option value="Fashion">Fashion</option>
                    <option value="Books">Books</option>
                    <option value="Services">Services</option>
                    <option value="Foods">Foods</option>
                    <option value="School Supplies">School Supplies</option>
                    <option value="Art Materials">Art Materials</option>
                    <option value="Others">Others</option>
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

wishlist_controller.php:
 <?php
session_start();
include '../db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$action = $_REQUEST['action'] ?? '';

// --- ACTION: ADD A NEW WISH ---
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $user_id = $_SESSION['user_id'];
    
    // Get the name from the <select>
    $category_name = $_POST['category'];

    // --- CATEGORY LOOKUP MAP ---
    $category_map = [
        "Dorm Essentials" => 1,
        "Arki Mats"       => 2,
        "Lab Essentials"  => 3,
        "Fashion"         => 4,
        "Books"           => 5,
        "Services"        => 6,
        "Foods"           => 7,
        "School Supplies" => 8,
        "Art Materials"   => 9,
        "Others"          => 10
    ];

    // Convert the name to an ID. Default to 10 (Others) if not found.
    $category_id = $category_map[$category_name] ?? 10;

    // --- INSERT INTO DATABASE ---
    $stmt = $conn->prepare("INSERT INTO wishlist (user_id, item_name, category_id) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $user_id, $item_name, $category_id);

    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Error: " . $conn->error;
    }
    exit();
}

// --- ACTION: FETCH WISHES ---
if ($action === 'fetch') {
    $query = "SELECT w.*, u.full_name FROM wishlist w 
              JOIN users u ON w.user_id = u.user_id 
              ORDER BY w.created_at DESC LIMIT 10";
    
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $is_mine = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id']);
            $card_style = $is_mine ? 'style="border: 1px solid maroon; background: #fff9f9;"' : '';

            echo '
            <div class="wish-card" ' . $card_style . '>
                <div class="wish-info">
                    <span class="category-tag">' . htmlspecialchars($row['category']) . '</span>
                    <h4>' . htmlspecialchars($row['item_name']) . '</h4>
                    <p>Requested by: <strong>' . ($is_mine ? "Me" : htmlspecialchars($row['full_name'])) . '</strong></p>
                </div>
                ' . (!$is_mine ? '<button class="match-btn" onclick="handleMatch(' . $row['wish_id'] . ')">I have this!</button>' : '<small>Your Wish</small>') . '
            </div>';
        }
    } else {
        echo '<p style="padding: 20px; color: #888;">No wishlist matches yet.</p>';
    }
    exit();
}

// --- ACTION: MATCH A WISH (COMBINED & SECURE) ---
if ($action === 'match_wish') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Login required']);
        exit();
    }

    $wish_id = intval($_POST['wish_id']);
    $seller_id = $_SESSION['user_id'];
    $seller_name = $_SESSION['full_name'];

    // 1. Fetch wish details to find the owner/requester
    $query = "SELECT w.user_id, w.item_name, u.full_name 
              FROM wishlist w 
              JOIN users u ON w.user_id = u.user_id 
              WHERE w.wish_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $wish_id);
    $stmt->execute();
    $wish = $stmt->get_result()->fetch_assoc();

    if ($wish) {
        $requester_id = $wish['user_id'];
        $requester_name = $wish['full_name'];
        $item_name = $wish['item_name'];
        
        // 2. Insert notification for the Requester
        $notif_msg = "<b>$seller_name</b> has the item you are looking for: '$item_name'!";
        
        $notif_sql = "INSERT INTO notifications (user_id, sender_id, message, is_read) 
                      VALUES (?, ?, ?, 0)";
        $notif_stmt = $conn->prepare($notif_sql);
        $notif_stmt->bind_param("iis", $requester_id, $seller_id, $notif_msg);
        
        if ($notif_stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'requester_id' => $requester_id, 
                'requester_name' => $requester_name,
                'item_name' => $item_name
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Wish not found.']);
    }
    exit();
}
?>

script.js(for the marketplace):
/**
 * UPMart Marketplace — Main Logic
 */
document.addEventListener('click', e => {
    const btn = e.target.closest('.buy-btn');
    if (!btn) return;

    e.preventDefault();

    const productId = btn.dataset.productId;
    const sellerId = btn.dataset.sellerId;
    const productName = btn.dataset.productName;

    if (window.openChatUI) {
        window.openChatUI(productId, sellerId, productName);
    }
});

// ─────────────────────────────────────────────
// 1. GLOBAL: fillEditForm (called from inline onclick)
// ─────────────────────────────────────────────
function fillEditForm(product) {
    const form = document.getElementById('product-form');
    if (!form) return;

    form.querySelector('#f-title').value         = product.title       || '';
    form.querySelector('#f-price').value         = product.price       || '';
    form.querySelector('#f-desc').value          = product.description || '';
    form.querySelector('#category-select').value = product.category_id || '';

    // Switch to Edit mode
    const submitBtn = document.getElementById('form-submit-btn');
    const formTitle = document.getElementById('form-title');
    submitBtn.textContent = 'Update Post';
    submitBtn.name        = 'update_post';
    formTitle.textContent = 'Edit Post';

    let idInput = form.querySelector('[name="product_id"]');
    if (!idInput) {
        idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'product_id';
        form.appendChild(idInput);
    }
    idInput.value = product.product_id;

    document.querySelector('.form-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}


// ─────────────────────────────────────────────
// 2. DOM READY
// ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    // ── Selectors ──────────────────────────────
    const el = {
        btnBuyer: document.getElementById('mode-buyer'),
        btnSeller: document.getElementById('mode-seller'),
        viewBuyer: document.getElementById('view-buyer'),
        viewSeller: document.getElementById('view-seller'),
        viewCart: document.getElementById('view-cart'),
        viewTxn: document.getElementById('view-transactions'),
        dynamicLink: document.getElementById('dynamic-link'),
        navMarketplace: document.querySelector('#nav-marketplace'),
        navLinks: document.querySelectorAll('.nav-links li'),
        productSearch: document.getElementById('product-search'),
        socialFeed: document.querySelector('.social-feed'),
        greet: document.getElementById('greeting'),
        subgreet: document.getElementById('sub-greeting'),
        categoryNav: document.getElementById('category-nav'),
        welcomeSection: document.querySelector('.welcome'),
        categorySelect: document.getElementById('category-select'),
        otherCat: document.getElementById('other-category-container'),
        uploadArea: document.getElementById('dropzone-area'),
        fileInput: document.getElementById('file-input'),
        previewCont: document.getElementById('image-preview-container'),
        overlay: document.getElementById('image-overlay'),
        overlayImg: document.getElementById('overlay-img'),
        closeOverlay: document.querySelector('.close-overlay'),
        // Messaging
        msgFloat: document.getElementById('msg-float-btn'),
        msgModal: document.getElementById('msg-modal'),
        closeMsg: document.getElementById('close-msg'),
        msgTitle: document.getElementById('msg-title'),
        msgConvos: document.getElementById('msg-conversations'),
        msgThreadView: document.getElementById('msg-thread-view'),
        msgThread: document.getElementById('msg-thread'),
        msgInput: document.getElementById('msg-input'),
        msgSend: document.getElementById('msg-send'),
        msgBack: document.getElementById('msg-back'),
    };

    // Active messaging context
    let activeProductId = null;
    let activeSellerId = null;
    let msgPollTimer = null;

    // ─────────────────────────────────────────────
    // 3. VIEW MANAGEMENT
    // ─────────────────────────────────────────────
    function hideAllViews() {
        [el.viewBuyer, el.viewSeller, el.viewCart, el.viewTxn,
        el.welcomeSection, el.categoryNav].forEach(v => { if (v) v.style.display = 'none'; });
        el.navLinks.forEach(li => li.classList.remove('active'));
    }

    function switchMode(isSeller) {
        hideAllViews();
        if (el.welcomeSection) el.welcomeSection.style.display = 'block';
        if (el.navMarketplace) el.navMarketplace.classList.add('active');

        if (isSeller) {
            if (el.viewSeller) el.viewSeller.style.display = 'block';
            if (el.categoryNav) el.categoryNav.style.display = 'none';
            // el.greet.innerText    = "Seller Mode";
            el.subgreet.innerText = "Manage your shop and list new products.";
            el.dynamicLink.innerHTML = '<span class="icon">📈</span> My Transactions';
            el.btnSeller.classList.add('active');
            el.btnBuyer.classList.remove('active');
        } else {
            if (el.viewBuyer) el.viewBuyer.style.display = 'block';
            if (el.categoryNav) el.categoryNav.style.display = 'flex';
            // el.greet.innerText    = "Buyer Mode";
            el.subgreet.innerText = "Browse and find the products you need!";
            el.dynamicLink.innerHTML = '<span class="icon">🛍️</span> My Orders';
            el.btnBuyer.classList.add('active');
            el.btnSeller.classList.remove('active');
        }
    }

    // ─────────────────────────────────────────────
    // 4. LIVE FEED (search + filter)
    // ─────────────────────────────────────────────
    function updateFeed() {
        const activePill = document.querySelector('.filter-pill.active');
        const category = activePill ? activePill.getAttribute('data-category') : 'all';
        const search = el.productSearch ? el.productSearch.value : '';

        fetch(`fetch_products.php?category=${category}&search=${encodeURIComponent(search)}`)
            .then(r => r.text())
            .then(html => { if (el.socialFeed) el.socialFeed.innerHTML = html; });
    }

    document.querySelectorAll('.filter-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            updateFeed();
        });
    });

    if (el.productSearch) el.productSearch.addEventListener('input', updateFeed);

    // ─────────────────────────────────────────────
    // 5. BUY ITEM BUTTON (delegated — works after AJAX reload)
    // ─────────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.buy-btn');
        if (!btn) return;

        const productId = btn.dataset.productId;
        const sellerId = btn.dataset.sellerId;
        const productName = btn.dataset.productName;

        // Place the order via AJAX
        const fd = new FormData();
        fd.append('place_order', '1');
        fd.append('product_id', productId);
        fd.append('seller_id', sellerId);

        fetch('handle_actions.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Order placed! Chat with the seller below.');
                } else {
                    showToast(data.message || 'Could not place order.');
                }
                // Open messaging popup regardless
                openChat(productId, sellerId, productName);
            })
            .catch(() => {
                // Still open chat even on network error
                openChat(productId, sellerId, productName);
            });
    });

    // ─────────────────────────────────────────────
    // 6. CONFIRM DEAL BUTTON (seller)
    // ─────────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.confirm-deal-btn');
        if (!btn) return;

        if (!confirm('Confirm this deal? The product will be marked as Sold.')) return;

        const fd = new FormData();
        fd.append('confirm_deal', '1');
        fd.append('order_id', btn.dataset.orderId);

        fetch('handle_actions.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Deal confirmed!');
                    btn.closest('tr').querySelector('.status-pill').textContent = 'Completed';
                    btn.closest('tr').querySelector('.status-pill').className = 'status-pill success';
                    btn.replaceWith(Object.assign(document.createElement('span'),
                        { textContent: '✓ Done', style: 'color:#27ae60; font-size:0.8rem;' }));
                }
            });
    });

    // --- MESSAGING LOGIC ---
    function openChat(productId, otherUserId, productName) {
        activeProductId = productId;
        activeSellerId = otherUserId;

        el.msgTitle.textContent = productName;
        el.msgConvos.style.display = 'none';
        el.msgThreadView.style.display = 'flex';
        el.msgModal.classList.add('open');

        loadThread();
        clearInterval(msgPollTimer);
        msgPollTimer = setInterval(loadThread, 3000);
    }

    window.openChatUI = openChat;

    function loadThread() {
        if (!activeProductId || !activeSellerId) return;
        fetch(`handle_actions.php?get_messages=1&product_id=${activeProductId}&other_user=${activeSellerId}`)
            .then(r => r.json())
            .then(msgs => {
                const atBottom = el.msgThread.scrollHeight - el.msgThread.scrollTop <= el.msgThread.clientHeight + 50;
                el.msgThread.innerHTML = '';
                if (!msgs.length) {
                    el.msgThread.innerHTML = '<div class="msg-empty">No messages yet. Say hello!</div>';
                    return;
                }
                msgs.forEach(m => {
                    const bubble = document.createElement('div');
                    bubble.className = `msg-bubble ${m.is_mine ? 'mine' : 'theirs'}`;
                    const time = new Date(m.sent_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    bubble.innerHTML = `${escapeHtml(m.message)}<span class="msg-time">${time}</span>`;
                    el.msgThread.appendChild(bubble);
                });
                if (atBottom) el.msgThread.scrollTop = el.msgThread.scrollHeight;
            });
    }

    function loadConversations() {
        el.msgConvos.innerHTML = '<div style="padding:20px; text-align:center; color:#aaa; font-size:0.85rem;">Loading...</div>';
        el.msgConvos.style.display = 'block';
        el.msgThreadView.style.display = 'none';

        fetch('handle_actions.php?get_conversations=1')
            .then(r => r.json())
            .then(convos => {
                if (!convos.length) {
                    el.msgConvos.innerHTML = '<div style="padding:20px; text-align:center; color:#aaa; font-size:0.85rem;">No conversations yet.</div>';
                    return;
                }
                el.msgConvos.innerHTML = '';
                convos.forEach(c => {
                    const div = document.createElement('div');
                    div.className = 'convo-item';
                    div.innerHTML = `
                        <div class="convo-avatar" style="background-image: url('${c.profile_pic}');"></div>
                        <div class="convo-details">
                            <strong>${c.other_user_name}</strong>
                            <span class="convo-product">${c.product_name}</span>
                            <span class="convo-last-msg">${c.last_message}</span>
                        </div>`;
                    div.addEventListener('click', () => openChat(c.product_id, c.other_id, c.product_name));
                    el.msgConvos.appendChild(div);
                });
            });
    }

    function sendMessage() {
        const text = el.msgInput.value.trim();
        if (!text || !activeProductId || !activeSellerId) return;
        const fd = new FormData();
        fd.append('send_message', '1');
        fd.append('product_id', activeProductId);
        fd.append('receiver_id', activeSellerId);
        fd.append('message', text);
        el.msgInput.value = '';
        fetch('handle_actions.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => { if (data.success) loadThread(); });
    }

    // --- EVENT ATTACHMENTS ---
    el.msgSend.addEventListener('click', sendMessage);
    el.msgInput.addEventListener('keydown', e => { if (e.key === 'Enter') sendMessage(); });
    el.msgFloat.addEventListener('click', () => {
        el.msgModal.classList.toggle('open');
        if (el.msgModal.classList.contains('open')) loadConversations();
    });
    el.closeMsg.addEventListener('click', () => {
        el.msgModal.classList.remove('open');
        clearInterval(msgPollTimer);
    });
    el.msgBack.addEventListener('click', () => {
        el.msgThreadView.style.display = 'none';
        el.msgConvos.style.display = 'block';
        el.msgTitle.textContent = 'Messages';
        clearInterval(msgPollTimer);
        activeProductId = null;
        activeSellerId = null;
        loadConversations();
    });

    // ─────────────────────────────────────────────
    // 8. SIDEBAR NAVIGATION
    // ─────────────────────────────────────────────
    if (el.dynamicLink) {
        el.dynamicLink.addEventListener('click', e => {
            e.preventDefault();
            hideAllViews();
            const isSeller = el.btnSeller.classList.contains('active');
            if (isSeller) {
                el.viewTxn.style.display = 'block';
            } else {
                el.viewCart.style.display = 'block';
            }
            el.dynamicLink.parentElement.classList.add('active');
        });
    }

    if (el.navMarketplace) {
        el.navMarketplace.querySelector('a').addEventListener('click', e => {
            e.preventDefault();
            switchMode(el.btnSeller.classList.contains('active'));
        });
    }

    el.btnBuyer.addEventListener('click', () => switchMode(false));
    el.btnSeller.addEventListener('click', () => switchMode(true));

    // ─────────────────────────────────────────────
    // 9. FORM HELPERS
    // ─────────────────────────────────────────────
    if (el.categorySelect) {
        el.categorySelect.addEventListener('change', function () {
            el.otherCat.style.display = (this.value === '4') ? 'block' : 'none';
        });
    }

    if (el.uploadArea) {
        el.uploadArea.addEventListener('click', () => el.fileInput.click());
        el.fileInput.addEventListener('change', e => {
            el.previewCont.innerHTML = '';
            Array.from(e.target.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = ev => {
                    const img = document.createElement('img');
                    img.src = ev.target.result;
                    img.className = 'preview-img';
                    el.previewCont.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // ─────────────────────────────────────────────
    // 10. LIGHTBOX
    // ─────────────────────────────────────────────
    document.addEventListener('click', e => {
        if (e.target.classList.contains('clickable-img')) {
            el.overlayImg.src = e.target.src;
            el.overlay.style.display = 'flex';
        }
    });
    if (el.closeOverlay) el.closeOverlay.addEventListener('click', () => el.overlay.style.display = 'none');
    if (el.overlay) el.overlay.addEventListener('click', e => {
        if (e.target === el.overlay) el.overlay.style.display = 'none';
    });

    // ─────────────────────────────────────────────
    // 11. HELPERS
    // ─────────────────────────────────────────────
    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function showToast(msg) {
        const t = document.createElement('div');
        t.textContent = msg;
        Object.assign(t.style, {
            position: 'fixed', bottom: '100px', right: '30px', background: '#1a1a2e', color: 'white',
            padding: '12px 20px', borderRadius: '12px', fontSize: '0.85rem', fontWeight: '600',
            zIndex: '9999', boxShadow: '0 4px 15px rgba(0,0,0,0.2)', transition: 'opacity 0.4s'
        });
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 3000);
    }

    const postReportModal = document.getElementById('postReportModal');
    const closePostReport = document.getElementById('closePostReportModal');
    const cancelPostReport = document.getElementById('cancelPostReportBtn');
    const postReportForm = document.getElementById('postReportForm');

    window.openPostReportModal = function (productId, productName) {
        document.getElementById('postReportLabel').textContent = 'Listing: ' + productName;
        document.getElementById('postReportType').value = '';
        document.getElementById('postReportDetails').value = '';
        postReportForm.dataset.productId = productId;
        if (postReportModal) postReportModal.style.display = 'flex';
    };

    if (closePostReport) closePostReport.onclick = () => postReportModal.style.display = 'none';
    if (cancelPostReport) cancelPostReport.onclick = () => postReportModal.style.display = 'none';

    if (postReportForm) {
        postReportForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('product_id', postReportForm.dataset.productId);
            formData.append('type', document.getElementById('postReportType').value);
            formData.append('details', document.getElementById('postReportDetails').value);

            fetch('report_handler.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(data => {
                    if (data.trim() === 'Success') {
                        alert('Thank you. Your report has been submitted.');
                        postReportForm.reset();
                        postReportModal.style.display = 'none';
                    } else {
                        alert('Submission failed: ' + data);
                    }
                })
                .catch(err => console.error('Report Error:', err));
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target === postReportModal) postReportModal.style.display = 'none';
    });


});

mandash.js(for the mainweb.hp):
document.addEventListener('DOMContentLoaded', () => {
    // 1. Element Selectors
    const logoutBtn = document.querySelector('.logout-btn');
    const postBtn = document.getElementById('postBtn');
    const bulletinInput = document.getElementById('bulletinText');
    const bulletinList = document.getElementById('bulletinList');
    const notifDrawer = document.getElementById('notifDrawer');
    const notifTrigger = document.getElementById('notifTrigger');
    const closeNotifBtn = document.getElementById('closeNotifBtn');
    
    // Modals
    const reportModal = document.getElementById('reportModal');
    const reportClose = document.getElementById('closeModal');
    const reportCancel = document.getElementById('cancelBtn');

    const wishModal = document.getElementById('wishModal');
    const addWishBtn = document.querySelector('.add-wish-btn');
    const closeWishModal = document.getElementById('closeWishModal');
    const cancelWishBtn = document.getElementById('cancelWishBtn');
    const wishForm = document.getElementById('wishForm');

    const reportForm = document.getElementById('reportForm');

    // 3. Navigation & Logout
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm("Are you sure you want to logout of UPMart?")) {
                window.location.href = "logout.php"; 
            }
        });
    }

    // Notification Toggle
    if (notifTrigger && notifDrawer) {
        notifTrigger.addEventListener('click', () => notifDrawer.classList.toggle('open'));
        if (closeNotifBtn) closeNotifBtn.addEventListener('click', () => notifDrawer.classList.remove('open'));
        
        document.addEventListener('click', (e) => {
            if (!notifDrawer.contains(e.target) && !notifTrigger.contains(e.target)) {
                notifDrawer.classList.remove('open');
            }
        });
    }

    // 4. Bulletin Logic
    function loadBulletin() {
        // Changed back to root path
        fetch('bulletin_controller.php?action=fetch') 
            .then(res => res.text())
            .then(data => { if(bulletinList) bulletinList.innerHTML = data; })
            .catch(err => console.error("Bulletin Error:", err));
    }

    if (postBtn) {
        postBtn.addEventListener('click', () => {
            const message = bulletinInput.value.trim();
            if (!message) return;

            const forbiddenWords = ["spam", "fuck", "nigga", "sex", "tangina", "bobo"];
            if (forbiddenWords.some(word => message.toLowerCase().includes(word))) {
                alert("Your post contains restricted language.");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'post');
            formData.append('message', message);

            fetch('bulletin_controller.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(data => {
                    if (data.trim() === "Success") {
                        bulletinInput.value = "";
                        loadBulletin();
                    }
                });
        });
    }

    // 5. Wishlist Logic
    function loadWishes() { 
        // Changed back to root path
        fetch('wishlist_controller.php?action=fetch')
            .then(res => res.text())
            .then(data => {
                const wishGrid = document.querySelector('.wish-grid');
                if (wishGrid) wishGrid.innerHTML = data;
            })
            .catch(err => console.error("Wishlist Error:", err));
    }

    window.handleMatch = function(wishId) {
        const formData = new FormData();
        formData.append('action', 'match_wish');
        formData.append('wish_id', wishId);

        fetch('wishlist_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json()) // Change .text() to .json()
        .then(data => {
            if (data.success) {
                alert(`Match noted for ${data.item_name}! Natasha Christine has been notified.`);
                // Optional: Disable the button so you don't spam her
                // event.target.innerText = "Notified";
                // event.target.disabled = true;
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            console.error("Match Error:", err);
            alert("Could not send match. Please try again.");
        });
    };

    if (addWishBtn) {
        addWishBtn.addEventListener('click', () => { wishModal.style.display = 'flex'; });
    }

    const hideWishModal = () => { 
        wishModal.style.display = 'none'; 
        if(wishForm) wishForm.reset(); 
    };

    if (closeWishModal) closeWishModal.onclick = hideWishModal;
    if (cancelWishBtn) cancelWishBtn.onclick = hideWishModal;

    if (wishForm) {
        wishForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(wishForm);
            formData.append('action', 'add');

            fetch('wishlist_controller.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(data => {
                if (data.trim() === "Success") {
                    hideWishModal();
                    loadWishes();
                } else {
                    alert("Error: " + data);
                }
            });
        });
    }

    // 6. Report Modal Logic
    window.openReportModal = function () {
        if (reportModal) reportModal.style.display = 'flex';
    };

    if (reportClose) reportClose.onclick = () => reportModal.style.display = 'none';
    if (reportCancel) reportCancel.onclick = () => reportModal.style.display = 'none';

    if (reportForm) {
        reportForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(reportForm);
            // Ensuring fields match report_handler.php
            formData.append('type', document.getElementById('reportType').value);
            formData.append('details', document.getElementById('reportDetails').value);

            fetch('report_handler.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(data => {
                    if (data.trim() === "Success") {
                        alert("Thank you. Your report has been submitted.");
                        reportForm.reset();
                        reportModal.style.display = 'none';
                    } else {
                        alert("Submission failed: " + data);
                    }
                })
                .catch(err => console.error("Report Error:", err));
        });
    }

    // Global click handler to close modals
    window.onclick = (event) => {
        if (event.target === reportModal) reportModal.style.display = 'none';
        if (event.target === wishModal) hideWishModal();
    };

    // 7. Initial Loads
    loadBulletin();
    loadWishes();
    setInterval(loadBulletin, 5000);
    setInterval(loadWishes, 30000);

    // New function to load notifications
    function loadNotifications() {
        const notifContainer = document.getElementById('notif-list-container');
        if (!notifContainer) return;

        fetch('notif_controller.php?action=fetch')
            .then(res => res.text())
            .then(data => {
                notifContainer.innerHTML = data;
                
                // Update the "Recent updates" text if there are notifications
                const statusText = document.getElementById('notif-status-text');
                if (data.includes('notif-item')) {
                    statusText.innerText = "You have new updates";
                }
            })
            .catch(err => console.error("Notification Error:", err));
    }

    // Initial load
    loadNotifications();
    // Refresh every 30 seconds
    setInterval(loadNotifications, 30000);
});

window.handleNotifClick = function(type, targetId, senderName) {
    if (type === 'message') {
        // Example: Open the chat modal or redirect to marketplace
        alert("Opening message from " + senderName);
        // window.location.href = "marketplace.php?chat_with=" + targetId;
    } else if (type === 'order') {
        window.location.href = "marketplace.php?view=orders";
    }
};