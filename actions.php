<?php
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'servers';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$user_prefix = $_SESSION['prefix'];
$apikey = $_SESSION['apikey'];
$actions = [];

if ($_SESSION['is_admin'] ?? false) {
$api_url = "https://api.clouding.io/v1/actions?pageSize=20";

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
$actions = $data['actions'] ?? [];
}
?>
    <?php if (!empty($actions)): ?>
	<h2 class="mb-4">Last actions List</h2>
        <table class="table table-striped shadow" style="width:100%" data-loading-template="loadingTemplate" data-toggle="table" data-pagination="true" data-search="true" data-page-size="5">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Status</th>
		    <th>Finish</th>
		    <th class="truncate">ID Source</th>
		    <th class="truncate">Source Type</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($actions as $action): ?>
                    <tr>
                        <td class="truncate"><?= htmlspecialchars($action['id']) ?></td>
                        <td class="truncate"><?= htmlspecialchars($action['type'] ?? 'N/A') ?></td>
                        <td class="truncate"><?= htmlspecialchars($action['status'] ?? 'N/A') ?></td>
			<td class="truncate"><?= htmlspecialchars($action['completedAt'] ?? 'N/A') ?></td>
			<td class="truncate"><?= htmlspecialchars($action['resourceId'] ?? 'N/A') ?></td>
			<td class="truncate"><?= htmlspecialchars($action['resourceType'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
<?php else: ?>
<div class="card text-white bg-secondary mb-3 shadow-sm text-center">No information available or you do not have permissions.</div>
<?php endif; ?>
