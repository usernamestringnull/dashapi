<?php
if (file_exists(__DIR__ . '/../db.php')) {
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
                <h4 class="alert-heading">⚠️ Setup Wizard Disabled</h4>
                <p>The configuration wizard has already been completed and is no longer available.</p>
                <hr>
                <a href="../index.php" class="btn btn-outline-primary">Go to Application</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<?php
$host      = $_POST['host'];
$User      = $_POST['user'];
$Pass      = $_POST['pass'];
$dbName    = $_POST['db_name'];
$dbUser    = $_POST['db_user'];
$dbPass    = $_POST['db_pass'];

try {
    $pdo = new PDO("mysql:host=$host", $User, $Pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("CREATE USER IF NOT EXISTS '$dbUser'@'localhost' IDENTIFIED BY '$dbPass';");
    $pdo->exec("GRANT ALL PRIVILEGES ON `$dbName`.* TO '$dbUser'@'localhost';");
    $pdo->exec("FLUSH PRIVILEGES;");

    $appPDO = new PDO("mysql:host=$host;dbname=$dbName", $dbUser, $dbPass);
    $appPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $appPDO->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            mailbox VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            prefix VARCHAR(50),
            apikey VARCHAR(255) NOT NULL,
            is_admin TINYINT(1) DEFAULT 0,
            is_superadmin TINYINT(1) DEFAULT 0
        );
    ");

    $appPDO->exec("
        CREATE TABLE IF NOT EXISTS logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_user INT NOT NULL,
            ip VARCHAR(45) NOT NULL,
            action VARCHAR(255) NOT NULL,
            date_time DATETIME NOT NULL
        );
    ");

    $config = <<<PHP
<?php
if (basename(__FILE__) == basename(\$_SERVER['PHP_SELF'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

\$db_host = "$host";
\$db_user = "$dbUser";
\$db_password = "$dbPass";
\$db_name = "$dbName";

\$conn = new mysqli(\$db_host, \$db_user, \$db_password, \$db_name);

if (\$conn->connect_error) {
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <title>Connection Error</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    </head>
    <body class="d-flex vh-100 align-items-center justify-content-center bg-light">
      <div class="alert alert-danger text-center w-50" role="alert">
        <h4 class="alert-heading">Connection Failed</h4>
        <p>Unable to connect to the database.</p>
        <hr />
        <p class="mb-0"><strong>Error details:</strong> ' . htmlspecialchars($conn->connect_error) . '</p>
      </div>
    </body>
    </html>
    ');
}
?>
PHP;

file_put_contents(__DIR__ . '/../db.php', $config);

    header("Location: superadmin.php");
    exit;
} catch (PDOException $e) {
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <title>Connection Error</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
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
    <body class="d-flex vh-100 align-items-center justify-content-center bg-light">
      <div class="alert alert-danger text-center w-50" role="alert">
        <h4 class="alert-heading">Connection Failed</h4>
        <p>Unable to connect to the database.</p>
        <hr />
        <p class="mb-0"><strong>Error details:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
      </div>
    </body>
    </html>
    ');	
}
