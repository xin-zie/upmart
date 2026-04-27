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
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
</head>
<body>
    <div class="main-panel-container"> <nav class="sidebar">
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
                    <a href="marketplace.html"><span class="icon">🛒</span> Marketplace</a>
                </li>
                <li id="nav-dynamic">
                    <a href="#" id="dynamic-link"><span class="icon">🛍️</span> My Orders</a>
                </li>
            </ul>

            <div class="logout-container">
                <button class="logout-btn">Logout</button>
            </div>
        </nav>

        <div class="main-content">
            <header class="wall-header">
                <div class="header-top-row" style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 10px;">
                    <h1 style="font-size: 1.2rem; font-weight: 800;"><span class="icon">🛒</span> Marketplace</h1>
                <!--<div class="status-indicators" style="display: flex; gap: 10px;">
                        <button class="icon-btn" id="notifBtn"><span class="material-icons">notifications</span></button>
                        <button class="icon-btn" id="helpBtn"><span class="material-icons">help_outline</span></button>
                    </div>-->
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

                <div class="welcome" style="margin-top: 10px;">
                    <h2 id="greeting">Buyer Mode</h2>
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

        <div id="view-buyer" class="view-content active">
            <div class="social-feed">
                <?php
                // Updated query: Removed profile_picture and added media subquery
                $query = "SELECT p.*, u.full_name, c.category_name, 
                        (SELECT image_path FROM media WHERE product_id = p.product_id LIMIT 1) as product_img 
                        FROM products p 
                        JOIN users u ON p.seller_id = u.user_id 
                        JOIN categories c ON p.category_id = c.category_id 
                        WHERE p.status = 'Available' 
                        ORDER BY p.created_at DESC";

                $products = $conn->query($query);
                while($row = $products->fetch_assoc()): 
                    // Standardize image path logic
                    $img = !empty($row['product_img']) ? $row['product_img'] : 'uploads/default.jpg';
                    $profile= !empty($row['profile_pic']) ? $row['profile_pic'] : 'uploads/user.jpg';
                ?>
                    <article class="post-card">
                        <div class="post-header">
                            <div class="seller-meta">
                                <div class="mini-avatar" style="background-image: url('<?php echo $profile; ?>');"></div>
                                <div class="seller-details">
                                    <strong><?php echo $row['full_name']; ?></strong>
                                    <span class="post-time"><span class="cat-tag"><?php echo $row['category_name']; ?></span></span>
                                </div>
                            </div>
                            <div class="post-price">₱<?php echo number_format($row['price'], 2); ?></div>
                        </div>
                        <p class="product-description"><?php echo $row['title']; ?></p>
                        <div class="post-gallery single">
                            <img src="<?php echo $img; ?>" class="clickable-img" alt="Product">
                        </div>
                        <form action="handle_actions.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                            <button type="submit" name="add_to_cart" class="message-btn">
                                <i class="fas fa-paper-plane"></i> Message Seller
                            </button>
                        </form>
                    </article>
                <?php endwhile; ?>
            </div>
        </div>
            <div id="image-overlay" class="image-overlay">
                <span class="close-overlay">&times;</span>
                <img id="overlay-img" src="" alt="Full View">
            </div>

            <div id="view-seller" class="view-content">
                <div class="seller-dashboard-grid">
                    <section class="bento-card inventory-section">
                        <h3>Your Active Listings</h3>
                        <?php 
                        $my_id = $_SESSION['user_id'];
                        $my_items = $conn->query("SELECT * FROM products WHERE seller_id = $my_id");
                        while($item = $my_items->fetch_assoc()): ?>
                            <div class="inventory-item">
                                <strong><?php echo $item['title']; ?></strong> - ₱<?php echo $item['price']; ?>
                                <div class="action-cell">
                                    <i class="fas fa-edit" onclick='fillEditForm(<?php echo json_encode($item); ?>)'></i>
                                    <a href="handle_actions.php?delete_id=<?php echo $item['product_id']; ?>" 
                                    onclick="return confirm('Delete listing?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </section>

                    <section class="bento-card form-section">
                        <h3>Create New Listing</h3>
                        <form action="handle_actions.php" method="POST" enctype="multipart/form-data">
                            <input type="text" name="title" placeholder="Product Name" class="input-modern" required>
                            <input type="number" name="price" placeholder="Price" class="input-modern" required>
                            <textarea name="description" placeholder="Description" class="input-modern textarea"></textarea>
                            <select name="category_id" id="category-select" class="input-modern" required>
                                <option value="">Select Category</option>
                                <option value="1">Dorm Essentials</option>
                                <option value="2">Arki Mats</option>
                                <option value="3">Lab Essentials</option>
                                <option value="4">Others</option> </select>
                                <div id="other-category-container" style="display: none; margin-top: -10px;">
                                    <input type="text" name="custom_category" placeholder="What kind of item is this?" class="input-modern">
                                </div>
                            <select item_condition="condition" name="condition" class="input-modern" required>
                                <option value="">Select Condition</option>
                                <option value="New">New</option>
                                <option value="Like New">Like New</option>
                                <option value="Used">Used</option>
                            </select>
                            <select status="status" name="status" class="input-modern" required>
                                <option value="">Select Status</option>
                                <option value="Available">Available</option>
                                <option value="Sold">Sold</option>
                            </select>
                            <div class="upload-area" id="dropzone-area">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Drag & Drop or Click to upload images</p>
                                <input type="file" name="product_image" id="file-input" multiple hidden>
                            </div>
                            <button type="submit" name="create_post" class="post-btn">Publish Listing</button>
                        </form>
                    </section>
                </div>
            </div>
            <div id="view-cart" class="view-content">
                <div class="bento-card">
                    <h3>My Cart</h3>
                    <table class="modern-table">
                        <?php
                        $cart = $conn->query("SELECT t.*, p.title, p.price FROM transactions t JOIN products p ON t.product_id = p.product_id WHERE t.buyer_id = $my_id");
                        while($c = $cart->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $c['title']; ?></td>
                                <td>₱<?php echo $c['price']; ?></td>
                                <td><span class="status-pill pending"><?php echo $c['status']; ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            </div>

            <div id="view-transactions" class="view-content">
                <div class="bento-card">
                    <h3>Recent Sales</h3>
                    <table class="transaction-table">
                        <?php
                        $sales = $conn->query("SELECT t.*, p.title, u.full_name FROM transactions t JOIN products p ON t.product_id = p.product_id JOIN users u ON t.buyer_id = u.user_id WHERE p.seller_id = $my_id");
                        while($s = $sales->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $s['title']; ?></td>
                                <td><?php echo $s['full_name']; ?></td>
                                <td><span class="status-pill success"><?php echo $s['status']; ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            </div>
        </div>
    </div> <button class="msg-float"><i class="fas fa-comment-dots"></i> Messages</button>
    <script src="script.js"></script>
</body>

</html>
