<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPMart</title>
    <link rel="stylesheet" href="marketplace.css">
    <link rel="icon" href="favicon.png" type="image/png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<div class="main-panel-container">

    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            <img src="logo.png" class="logo-img" alt="UPMart Logo">
        </div>
        <img src="uploads/<?php echo $_SESSION['user_id']; ?>.jpg" alt="Profile" class="profile-img">
        <div class="profile-info">
            <span class="profile-name"><?php echo $_SESSION['user_name']; ?></span>
        </div>
        <ul class="nav-links">
            <li id="nav-dashboard">
                <a href="main.html"><span class="icon">🏠︎</span> Dashboard</a>
            </li>
            <li id="nav-marketplace" class="active">
                <a href="marketplace.php"><span class="icon">🛒</span> Marketplace</a>
            </li>
            <li id="nav-dynamic">
                <a href="#" id="dynamic-link"><span class="icon">🛍️</span> My Orders</a>
            </li>
        </ul>
        <div class="logout-container">
            <button class="logout-btn">Logout</button>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <header class="wall-header">
            <div class="header-top-row">
                <h1 style="font-size:1.2rem; font-weight:800;"><span class="icon">🛒</span> Marketplace</h1>
            </div>
            <div class="header-actions">
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search products..." id="product-search">
                </div>
                <div class="role-switcher">
                    <button id="mode-buyer" class="role-btn active">Buyer</button>
                    <button id="mode-seller" class="role-btn">Seller</button>
                </div>
            </div>
            <div class="welcome" style="margin-top:10px;">
                <!--<h2 id="greeting">Buyer Mode</h2>-->
                <p id="sub-greeting">Browse and find the products you need!</p>
            </div>
        </header>

        <div class="category-wrapper" id="category-nav">
            <button class="filter-pill active" data-category="all">All Posts</button>
            <button class="filter-pill" data-category="1">Dorm Essentials</button>
            <button class="filter-pill" data-category="2">Arki Mats</button>
            <button class="filter-pill" data-category="3">Lab Essentials</button>
            <button class="filter-pill" data-category="4">Others</button>
        </div>

        <!-- BUYER VIEW -->
        <div id="view-buyer" class="view-content active">
            <div class="social-feed">
                <?php
                $my_id   = (int)$_SESSION['user_id'];
                $query   = "SELECT p.*, u.full_name, u.profile_pic, c.category_name,
                            (SELECT image_path FROM media WHERE product_id = p.product_id LIMIT 1) as product_img
                            FROM products p
                            JOIN users u ON p.seller_id = u.user_id
                            JOIN categories c ON p.category_id = c.category_id
                            WHERE p.status = 'Available' AND p.approval_status = 'Approved'
                            ORDER BY p.created_at DESC";
                $products = $conn->query($query);
                while ($row = $products->fetch_assoc()):
                    $img     = !empty($row['product_img']) ? $row['product_img'] : 'uploads/default.jpg';
                    $profile = !empty($row['profile_pic']) ? $row['profile_pic'] : 'uploads/user.jpg';
                ?>
                <article class="post-card">
                    <div class="post-header">
                        <div class="seller-meta">
                            <div class="mini-avatar" style="background-image:url('<?php echo $profile; ?>');"></div>
                            <div class="seller-details">
                                <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                <span class="post-time"><span class="cat-tag"><?php echo $row['category_name']; ?></span></span>
                            </div>
                        </div>
                        <div class="post-price">&#8369;<?php echo number_format($row['price'], 2); ?></div>
                    </div>
                    <p class="product-description"><?php echo htmlspecialchars($row['title']); ?></p>
                    <div class="post-gallery single">
                        <img src="<?php echo $img; ?>" class="clickable-img" alt="Product">
                    </div>
                    <button class="buy-btn"
                        data-product-id="<?php echo $row['product_id']; ?>"
                        data-seller-id="<?php echo $row['seller_id']; ?>"
                        data-product-name="<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>">
                        <i class="fas fa-shopping-bag"></i> Buy Item
                    </button>
                </article>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- LIGHTBOX -->
        <div id="image-overlay" class="image-overlay">
            <span class="close-overlay">&times;</span>
            <img id="overlay-img" src="" alt="Full View">
        </div>

        <!-- SELLER VIEW -->
        <div id="view-seller" class="view-content">
            <div class="seller-dashboard-grid">
                <section class="bento-card inventory-section">
                    <h3>Your Active Listings</h3>
                    <?php
                    $my_items = $conn->query("SELECT * FROM products WHERE seller_id = $my_id ORDER BY created_at DESC");
                    while ($item = $my_items->fetch_assoc()):
                        $approval = strtolower($item['approval_status'] ?? 'pending');
                    ?>
                    <div class="inventory-item">
                        <div style="flex:1;">
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                            <span style="color:#888; font-size:0.85rem;"> — &#8369;<?php echo number_format($item['price'],2); ?></span>
                        </div>
                        <span class="status-pill <?php echo $approval; ?>">
                            <?php echo ucfirst($approval); ?>
                        </span>
                        <div class="action-cell" style="margin-left:10px;">
                            <i class="fas fa-edit" onclick='fillEditForm(<?php echo json_encode($item); ?>)' title="Edit"></i>
                            <a href="handle_actions.php?delete_id=<?php echo $item['product_id']; ?>"
                               onclick="return confirm('Delete this listing?')" style="color:#aaa;">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </section>

                <section class="bento-card form-section">
                    <h3 id="form-title">Create New Listing</h3>
                    <form action="handle_actions.php" method="POST" enctype="multipart/form-data">
                        <input type="text" name="title" placeholder="Product Name" class="input-modern" required>
                        <input type="number" name="price" placeholder="Price" class="input-modern" step="0.01" required>
                        <textarea name="description" placeholder="Description" class="input-modern textarea"></textarea>
                        <select name="category_id" id="category-select" class="input-modern" required>
                            <option value="">Select Category</option>
                            <option value="1">Dorm Essentials</option>
                            <option value="2">Arki Mats</option>
                            <option value="3">Lab Essentials</option>
                            <option value="4">Others</option>
                        </select>
                        <div id="other-category-container" style="display:none; margin-top:-10px;">
                            <input type="text" name="custom_category" placeholder="What kind of item is this?" class="input-modern">
                        </div>
                        <select name="condition" class="input-modern" required>
                            <option value="">Select Condition</option>
                            <option value="New">New</option>
                            <option value="Like New">Like New</option>
                            <option value="Used">Used</option>
                        </select>
                        <div class="upload-area" id="dropzone-area">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Drag & Drop or Click to upload image</p>
                            <input type="file" name="product_image" id="file-input" hidden accept="image/*">
                        </div>
                        <div id="image-preview-container" class="preview-grid"></div>
                        <button type="submit" name="create_post" class="post-btn">Publish Listing</button>
                    </form>
                </section>
            </div>
        </div>

        <!-- MY ORDERS VIEW (Buyer) -->
        <div id="view-cart" class="view-content">
            <div class="bento-card">
                <h3>My Orders</h3>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $orders = $conn->query("SELECT o.*, p.title, p.price 
                                           FROM orders o 
                                           JOIN products p ON o.product_id = p.product_id 
                                           WHERE o.buyer_id = $my_id 
                                           ORDER BY o.created_at DESC");
                    if ($orders && $orders->num_rows > 0):
                        while ($c = $orders->fetch_assoc()):
                            $pill = strtolower($c['status']) === 'completed' ? 'success' : 'pending';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c['title']); ?></td>
                        <td>&#8369;<?php echo number_format($c['price'], 2); ?></td>
                        <td><span class="status-pill <?php echo $pill; ?>"><?php echo $c['status']; ?></span></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="3" style="text-align:center; color:#aaa; padding:30px;">No orders yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MY TRANSACTIONS VIEW (Seller) -->
        <div id="view-transactions" class="view-content">
            <div class="bento-card">
                <h3>Recent Sales</h3>
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Buyer</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $sales = $conn->query("SELECT o.*, p.title, u.full_name 
                                          FROM orders o 
                                          JOIN products p ON o.product_id = p.product_id 
                                          JOIN users u ON o.buyer_id = u.user_id 
                                          WHERE o.seller_id = $my_id 
                                          ORDER BY o.created_at DESC");
                    if ($sales && $sales->num_rows > 0):
                        while ($s = $sales->fetch_assoc()):
                            $pill = strtolower($s['status']) === 'completed' ? 'success' : 'pending';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['title']); ?></td>
                        <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                        <td><span class="status-pill <?php echo $pill; ?>"><?php echo $s['status']; ?></span></td>
                        <td>
                            <?php if ($s['status'] === 'Pending'): ?>
                            <button class="confirm-deal-btn" data-order-id="<?php echo $s['order_id']; ?>">
                                Confirm Deal
                            </button>
                            <?php else: ?>
                            <span style="color:#27ae60; font-size:0.8rem;">&#10003; Done</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4" style="text-align:center; color:#aaa; padding:30px;">No sales yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- end main-content -->
</div><!-- end main-panel-container -->

<!-- FLOATING MESSAGE BUTTON -->
<button class="msg-float" id="msg-float-btn">
    <i class="fas fa-comment-dots"></i> Messages
</button>

<!-- MESSAGING MODAL -->
<div id="msg-modal">
    <div id="msg-modal-header">
        <strong id="msg-title">Messages</strong>
        <span id="close-msg">&times;</span>
    </div>
    <div id="msg-body">
        <!-- Conversation list shown by default -->
        <div id="msg-conversations"></div>
        <!-- Thread view shown when a conversation is open -->
        <div id="msg-thread-view" style="display:none; flex-direction:column; flex:1; overflow:hidden;">
            <div id="msg-back" style="padding:10px 14px; cursor:pointer; font-size:0.8rem; color:#9a0000; border-bottom:1px solid #f0f0f0;">
                &#8592; Back
            </div>
            <div id="msg-thread"></div>
            <div id="msg-input-row">
                <input type="text" id="msg-input" placeholder="Type a message...">
                <button id="msg-send"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>