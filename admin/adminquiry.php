<?php
include '../db_connect.php'; // Corrected path
// Add admin session check here later!

$result = $conn->query("SELECT * FROM inquiries ORDER BY created_at DESC");
?>

<h2>User Reports & Inquiries</h2>
<table border="1">
    <tr>
        <th>Date</th>
        <th>From</th>
        <th>Email</th>
        <th>Message</th>
        <th>Status</th> 
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo $row['created_at']; ?></td>
        <td><?php echo $row['name']; ?></td>
        <td><?php echo $row['email']; ?></td>
        <td><?php echo $row['message']; ?></td>
        <td><?php echo $row['status']; ?></td>
    </tr>
    <?php endwhile; ?>
</table>