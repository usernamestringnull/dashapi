<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'images';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['refresh'])) {
    header("Location: images.php");
    exit();
}

include 'db.php';

$user_prefix = $_SESSION['prefix'];
$apikey = $_SESSION['apikey'];
$endpoint = "https://api.clouding.io/v1/images?pageSize=200";
$headers = [
    "Content-type: application/json",
    "X-API-KEY: $apikey"
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_URL            => $endpoint,
]);

$response = curl_exec($curl);
$decoderesponse = json_decode($response, true);
curl_close($curl);

$images = [];
$images = $decoderesponse['images'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Images</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content effectup">
        <div class="container mt-4">
            <h1><i class="bi bi-filetype-raw"></i> Images</h1>
            <table class="table table-striped shadow" data-toggle="table" data-pagination="true" data-search="true" data-page-size="5">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th class="truncate">Minimum</th>
			<th class="truncate">Password</th>
			<th>Key</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($images as $image): ?>
                        <tr>
                            <td class="truncate"><?php echo htmlspecialchars($image['id']); ?></td>
                            <td class="truncate"><?php echo htmlspecialchars($image['name']); ?></td>
                            <td class="truncate"><?php echo htmlspecialchars($image['minimumSizeGb']); ?>GB</td>
			    <td class="truncate"><?php echo htmlspecialchars($image['accessMethods']['password']); ?></td>
			    <td class="truncate"><?php echo htmlspecialchars($image['accessMethods']['sshKey']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
