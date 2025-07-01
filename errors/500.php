<?php
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>500 Internal Server Error</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            height: 50vh;
            display: flex;
            justify-content: center;
            align-items: center;
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
.error-container {
    max-width: 80%;
    box-sizing: border-box;
}
    </style>
</head>
<body>
    <div class="error-container text-center p-4 shadow rounded bg-white">
        <h1 class="display-4 text-danger">💩 500</h1>
        <h4 class="mb-3">Internal Server Error</h4>
        <p class="text-muted">
            Something went wrong on our end. Please try again later or contact support if the issue persists.
        </p>
        <a href="/" class="btn btn-danger mt-3">Retry</a>
    </div>
</body>
</html>
