<?php
include '../db_connect.php';

$q = $_GET['q'] ?? '';
$users = [];

if (!empty($q)) {
    $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE full_name LIKE ? LIMIT 5");
    $searchTerm = "%$q%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'user_id' => $row['user_id'],
            'full_name' => $row['full_name']
        ];
    }
}
echo json_encode($users);
exit()
?>