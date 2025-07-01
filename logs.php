<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'logs';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_superadmin']) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$sql = "SELECT * FROM logs ORDER BY date_time DESC;";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content effectup">
        <div class="container mt-4">
            <h1><i class="bi bi-cassette"></i> Logs</h1>
            <table class="table table-striped table-borderless table-sm shadow" data-toggle="table" data-pagination="true" data-search="true" data-page-size="5">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>IP</th>
			<th>Action</th>
                        <th data-sortable="true">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id_user']; ?></td>
                            <td class="truncate"><?php echo $row['ip']; ?></td>
                            <td class="truncate"><?php echo $row['action']; ?></td>
                            <td class="truncate"><?php echo $row['date_time']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
