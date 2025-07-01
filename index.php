<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'index';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content effectup">
        <div class="container mt-4">
            <h1>Dashboard</h1>
            <p>Welcome to the admin panel!</p>
            <ul class="nav nav-pills nav-fill" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active shadow fw-bold" id="limits-tab" data-bs-toggle="tab" href="#limits" role="tab" aria-controls="limits" aria-selected="true">Limits</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link shadow fw-bold ms-2" id="actions-tab" data-bs-toggle="tab" href="#actions" role="tab" aria-controls="actions" aria-selected="false">Actions</a>
                </li>
            </ul>
            <div class="tab-content mt-3" id="myTabContent">
                <div class="tab-pane fade show active" id="limits" role="tabpanel" aria-labelledby="limits-tab">
                    <?php include 'limits.php'; ?>
                </div>
                <div class="tab-pane fade" id="actions" role="tabpanel" aria-labelledby="actions-tab">
                    <?php include 'actions.php'; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
