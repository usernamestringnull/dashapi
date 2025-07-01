<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'login';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
if (!file_exists(__DIR__ . '/db.php')) {
    header('Location: wizard/index.php');
    exit;
}
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mailbox = $_POST['mailbox'];
    $password = $_POST['password'];

    if (!empty($mailbox) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE mailbox = ?");
        $stmt->bind_param("s", $mailbox);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['mailbox'] = $user['mailbox'];
                $_SESSION['is_admin'] = $user['is_admin'];
		$_SESSION['is_superadmin'] = $user['is_superadmin'];
		$_SESSION['apikey'] = $user['apikey'];
		$_SESSION['prefix'] = $user['prefix'];
		if (!function_exists('logAction')) {
        		include 'functions.php';
		}
		$action = "session start";
		logAction($action, $conn);
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid user or password";
            }
        } else {
            $error = "Invalid user or password";
        }
    } else {
        $error = "No data";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4a90e2">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
	.container {
            transform: translateY(-100px);
        }
	.card {
            border-radius: 20px;
            overflow: hidden;
            transform: translateY(-30px);
	    animation: slideUp 0.5s ease-out forwards;
	}

	@keyframes slideUp {
    		from {
        		opacity: 0;
        		transform: translateY(50px);
    		}
    		to {
        		opacity: 1;
        		transform: translateY(0);
    		}
	}
	
	.card-title {
            font-weight: bold;
            color: #4a4a4a;
        }
        .btn-primary {
            background-color: #4a90e2;
            border: none;
            transition: background 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #357ABD;
	}
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <h3 class="card-title text-center mb-4">Login</h3>
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger text-center shadow"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="mailbox" class="form-label">User</label>
                                <input type="text" class="form-control" id="mailbox" name="mailbox" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary shadow">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
