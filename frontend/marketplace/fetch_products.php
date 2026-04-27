<?php
include 'db_connect.php';

$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "SELECT p.*, u.full_name, c.category_name, 
          (SELECT image_path FROM media WHERE product_id = p.product_id LIMIT 1) as product_img 
          FROM products p 
          JOIN users u ON p.seller_id = u.user_id 
          JOIN categories c ON p.category_id = c.category_id 
          WHERE p.status = 'Available'";

if ($category !== 'all') {
    $cat_id = mysqli_real_escape_string($conn, $category);
    $query .= " AND p.category_id = '$cat_id'";
}

if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    $query .= " AND (p.title LIKE '%$s%' OR p.description LIKE '%$s%' OR u.full_name LIKE '%$s%')";
}

$query .= " ORDER BY p.created_at DESC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $img = !empty($row['product_img']) ? $row['product_img'] : 'uploads/default.jpg';
        $profile= !empty($row['profile_pic']) ? $row['profile_pic'] : 'uploads/user.jpg';
        echo '
        <article class="post-card">
            <div class="post-header">
                <div class="seller-meta">
                    <div class="mini-avatar" style="background-image: url(\''.$profile.'\');"></div>
                    <div class="seller-details">
                        <strong>'.$row['full_name'].'</strong>
                        <span class="post-time"><span class="cat-tag">'.$row['category_name'].'</span></span>
                    </div>
                </div>
                <div class="post-price">₱'.number_format($row['price'], 2).'</div>
            </div>
            <p class="product-description">'.$row['title'].'</p>
            <div class="post-gallery single">
                <img src="'.$img.'" class="clickable-img" alt="Product">
            </div>
            <form action="handle_actions.php" method="POST">
                <input type="hidden" name="product_id" value="'.$row['product_id'].'">
                <button type="submit" name="add_to_cart" class="message-btn">
                    <i class="fas fa-paper-plane"></i> Message Seller
                </button>
            </form>
        </article>';
    }
} else {
    echo '<div class="no-results">No matches found.</div>';
}
?>