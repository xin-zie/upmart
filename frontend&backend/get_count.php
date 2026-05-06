<?php
include '../db_connect.php';

// 1. Count posts that need admin approval
$pending_post_query = "SELECT COUNT(*) as total FROM products WHERE approval_status = 'Pending'";
$pending_post_res = $conn->query($pending_post_query);
$pending_post_count = $pending_post_res->fetch_assoc()['total'] ?? 0;

// 2. Count reports that are still 'Pending'
$pending_report_query = "SELECT COUNT(*) as total FROM reports WHERE status = 'Pending'";
$pending_report_res = $conn->query($pending_report_query);
$pending_report_count = $pending_report_res->fetch_assoc()['total'] ?? 0;

// 3. Return the JSON response
echo json_encode([
    'posts' => (int)$pending_post_count,
    'reports' => (int)$pending_report_count,
    'total' => (int)($pending_post_count + $pending_report_count)
]);
?>