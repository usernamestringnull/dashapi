<?php
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'index';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$user_prefix = $_SESSION['prefix'];
$apikey = $_SESSION['apikey'];
$limits = [];

if ($_SESSION['is_admin'] ?? false) {
$api_url = "https://api.clouding.io/v1/account/limits";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "X-API-KEY: $apikey"
]);

$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);
$limits = $data['values'] ?? [];
}
?>

    <?php if (!empty($limits)): ?>
	<h2 class="mb-4">Account Limits</h2>
        <div class="table-responsive">
            <table class="table table-striped shadow" data-toggle="table" data-pagination="true" data-search="true" data-page-size="5">
                <thead>
		    <tr>
			<th>Name</th>
			<th>Limit</th>
                        <th>Usage</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($limits as $limit): ?>
			<tr>
			    <td class="truncate"><?= htmlspecialchars($limit['name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($limit['limit'] ?? 'N/A') ?></td>
			    <td><?= htmlspecialchars($limit['usage'] ?? 'N/A') ?></td>
                            <td class="truncate"><?= htmlspecialchars($limit['description'] ?? 'N/A') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="card text-white bg-secondary mb-3 shadow-sm text-center">No information available or you do not have permissions.</div>
    <?php endif; ?>
