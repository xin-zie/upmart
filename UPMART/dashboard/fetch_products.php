<?php
include '../db_connect.php'; // Ensure path is correct

$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';

// Start building the query with spaces at the end of lines
$query = "SELECT p.*, u.full_name, u.profile_pic, c.category_name, 
          (SELECT image_path FROM media WHERE product_id = p.product_id LIMIT 1) as product_img 
          FROM products p 
          JOIN users u ON p.seller_id = u.user_id 
          JOIN categories c ON p.category_id = c.category_id 
          WHERE p.status = 'Available' 
          AND p.approval_status = 'Approved'"; // Note the space after 'Available'

if ($category !== 'all') {
    $cat_id = mysqli_real_escape_string($conn, $category);
    $query .= " AND p.category_id = '$cat_id' "; // Space after '$cat_id'
}

if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    $query .= " AND (p.title LIKE '%$s%' OR p.description LIKE '%$s%' OR u.full_name LIKE '%$s%') "; // Space after closing paren
}

$query .= " ORDER BY p.created_at DESC";

$result = $conn->query($query);

// Debugging tip: If it fails again, uncomment the line below to see the exact SQL
// if (!$result) { die("Query Failed: " . $conn->error . "<br>SQL: " . $query); }

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
$imgs_q = $conn->query("SELECT image_path FROM media WHERE product_id = {$row['product_id']}");
$images = [];
while ($image_row = $imgs_q->fetch_assoc()) { 
    $path=$image_row['image_path'];
    $path=preg_replace('/^(\.\.\/)+/', '../', $path);
    $images[] = $path;
    }
if (empty($images)) $images[] = '../images/default.jpg';

$count = count($images);
$gallery_class = $count === 1 ? 'single' : ($count === 2 ? 'two' : 'multi');
$seller_pic = !empty($row['profile_pic']) ? 'uploads/' . $row['profile_pic'] : 'uploads/profile.jpg';
        echo '
        <article class="post-card">
            <div class="post-header">
                <div class="seller-meta">
                    <div class="mini-avatar" style="background-image: url(\''.$seller_pic.'\');"></div>
                    <div class="seller-details">
                        <strong>'.htmlspecialchars($row['full_name']).' 
                            <span style="color:#888; font-weight:normal; font-size:0.85em;">(ID: '.$row['seller_id'].')</span>
                        </strong>
                        <span class="post-time"><span class="cat-tag">'.htmlspecialchars($row['category_name']).'</span></span>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:6px;">
                    <div class="post-price">&#8369;' . number_format($row['price'], 2) . '</div>
                    
                    <button class="icon-btn post-report-btn"
                        title="Report Post"
                        onclick="openReportModal(\''.$row['seller_id'].'\', \''.addslashes($row['full_name']).'\', \''.$row['product_id'].'\')">
                        <span class="material-icons" style="font-size:16px;">report</span>
                    </button>
                </div>
            </div>
            <p class="product-description">'.htmlspecialchars($row['title']).'</p>
            <div class="post-gallery ' . $gallery_class . '">';
            foreach ($images as $img) {
                echo '<img src="' . htmlspecialchars($img) . '" class="clickable-img" alt="Product">';
            }
            echo '</div>';
            echo '
            <button type="button" 
                    class="buy-btn" 
                    data-product-id="'.$row['product_id'].'" 
                    data-seller-id="'.$row['seller_id'].'" 
                    data-product-name="'.htmlspecialchars($row['title'], ENT_QUOTES).'">
                <i class="fas fa-paper-plane"></i> Message Seller
            </button>
        </article>';
    }
} else {
    echo '<div class="no-results" style="padding: 20px; color: #888;">No matches found.</div>';
}
?>