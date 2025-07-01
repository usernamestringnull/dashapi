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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Wizard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
	    min-height: 100vh;
	    display: flex;
	    overflow: hidden;
            justify-content: center;
            align-items: center;
            margin: 0;
            background: linear-gradient(140deg, #ffffff, #4a90e2);
            background-size: 300% 300%;
            animation: waveGradient 8s ease-in-out infinite;
        }
        @keyframes waveGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
	}

        .card {
            width: 500px;
            max-width: 500px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="card shadow p-4">
        <h2 class="mb-4">🔧 Panel Admin</h2>
        <form method="POST" action="setup.php">
            <div class="mb-3">
                <label class="form-label">MySQL Host</label>
                <input type="text" class="form-control" name="host" value="localhost" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Superuser</label>
                <input type="text" class="form-control" name="user" value="root" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Superuser Pass</label>
                <input type="password" class="form-control" name="pass" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Name DB panel</label>
                <input type="text" class="form-control" name="db_name" value="panel_db" required>
            </div>
            <div class="mb-3">
                <label class="form-label">User DB panel</label>
                <input type="text" class="form-control" name="db_user" value="panel_user" required>
            </div>
            <div class="mb-3">
                <label class="form-label">User DB Pass</label>
                <input type="password" class="form-control" name="db_pass" required>
            </div>
            <button type="submit" class="btn btn-primary">Create DB</button>
        </form>
    </div>
</body>
</html>
