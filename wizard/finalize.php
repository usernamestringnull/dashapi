<?php
if (file_exists(__DIR__ . '/../db.php')) {
    include '../db.php';
    if (!isset($conn) || $conn->connect_error) {
        http_response_code(500);
        echo "<h2 style='color:red;'>❌ Failed to connect to the database.</h2>";
        exit;
    }
    $result = $conn->query("SELECT COUNT(*) AS total FROM users");
    if ($result && $row = $result->fetch_assoc()) {
        if ((int)$row['total'] > 0) {
            http_response_code(403);
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Wizard Locked</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            overflow: hidden;
            background: linear-gradient(140deg, #ffffff, #4a90e2);
            background-size: 300% 300%;
            animation: waveGradient 8s ease-in-out infinite;
        }
        @keyframes waveGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
            </head>
            <body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
                <div class="text-center">
                    <div class="alert alert-warning shadow p-4 rounded" role="alert">
                        <h4 class="alert-heading">  Setup Already Completed</h4>
                        <p>A user already exists in the system. Setup cannot continue.</p>
                        <hr>
                        <a href="../index.php" class="btn btn-outline-primary">Go to Application</a>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    } else {
        http_response_code(500);
        echo "<h2 style='color:red;'>❌ Failed to check users table.</h2>";
        exit;
    }
}
?>
<?php

$mailbox = $_POST['mailbox'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$prefix = $_POST['prefix'] ?? '';
$apikey = bin2hex(random_bytes(16));

$conn->prepare("
    INSERT INTO users (mailbox, password, prefix, apikey, is_admin, is_superadmin)
    VALUES (?, ?, ?, ?, 1, 1)
")->execute([$mailbox, $password, $prefix, $apikey]);

array_map('unlink', glob(__DIR__ . '/*.php'));
rmdir(__DIR__);

echo "<h2>✅ Complete...</h2>";
echo '<a href="../index.php">Go to login</a>';
