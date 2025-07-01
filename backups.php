<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'backups';

if (isset($_POST['refresh'])) {
    header("Location: backups.php");
    exit();
}

include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_prefix = $_SESSION['prefix'];
$apikey = $_SESSION['apikey'];
$endpoint = "https://api.clouding.io/v1/backups?pageSize=200";
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

$backups = [];
if ($user_prefix) {
    foreach ($decoderesponse['backups'] as $backup) {
        if (strpos($backup['serverName'], $user_prefix) === 0) {
                $backups[] = $backup;
        }
    }
} else {
    $backups = $decoderesponse['backups'];
}

if (isset($_POST['enable_backup'])) {
    $slots = $_POST['slots'];
    $frequency = $_POST['frequency'];
    $server = $_POST['Source'];
    $url = "https://api.clouding.io/v1/servers/$server/backups";
    $data = array(
        'slots' => $slots,
        'frequency' => $frequency
    );
    $jsonData = json_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-API-KEY: ' . $apikey
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);

    if (isset($responseData['startedAt'])) {
        echo "<div class='alert alert-success text-center'>Enable backup complete... <form action='backups.php' method='POST' style='display: inline;'>
    <input type='hidden' name='refresh' value='1'>
    <button type='submit' class='btn btn-primary'>
        <i class='bi bi-arrow-clockwise'>Refresh</i>
    </button>
</form></div>";
    } else {
        echo "<div class='alert alert-danger text-center'>Error: " . htmlspecialchars($response) . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Backups</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content effectup">
        <div class="container mt-4">
	    <h1><i class="bi bi-device-hdd"></i> Backups</h1>
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#enableBackupModal"><i class="bi bi-plus-lg"></i> Add Backups</button>
            <?php endif; ?>
	    <button class="btn btn-secondary mb-3 shadow" onclick="window.location.href = window.location.href;"><i class="bi bi-arrow-clockwise"></i></button>
            <table class="table table-striped shadow" data-toggle="table" data-pagination="true" data-search="true" data-page-size="10">
                <thead>
                    <tr>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><th class="truncate">ID</th><?php endif; ?>
                        <th class="truncate" data-sortable="true">Date</th>
                        <th>Size</th>
			<th data-sortable="true">Source</th>
			<?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
		    <?php foreach ($backups as $backup): ?>
			<tr>
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><td><?php echo htmlspecialchars($backup['id']); ?></td><?php endif; ?>
                            <td><?php echo htmlspecialchars($backup['createdAt']); ?></td>
			    <td><?php echo htmlspecialchars($backup['volumeSizeGb']); ?>GB</td>
			    <td><?php echo htmlspecialchars($backup['serverName']); ?></td>
			    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
			    <td>
                               <div class="dropdown">
                                  <button class="btn btn-secondary btn-sm dropdown-toggle shadow" type="button" id="dropdownMenuButton_<?php echo $backup['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false" data-bs-container="body">Actions</button>
				    <ul class="dropdown-menu shadow" aria-labelledby="dropdownMenuButton_<?php echo $backup['id']; ?>">
				    <li>
                                        <button class="dropdown-item text-primary" data-bs-toggle="modal" data-bs-target="#restoreBackupModal" data-id="<?php echo $backup['id']; ?>"><i class="bi bi-save"></i> Restore</button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item text-info" data-bs-toggle="modal" data-bs-target="#cloneBackupModal" data-id="<?php echo $backup['id']; ?>"><i class="bi bi-copy"></i> Clone</button>
				    </li>
				  </ul>
				</div>
			    </td>
			    <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for enable Backups -->
    <div class="modal fade" id="enableBackupModal" tabindex="-1" aria-labelledby="enableBackupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="enableBackupsModalLabel">Enable Backups</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="backups.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="slots" class="form-label">Slots</label>
                            <input type="number" class="form-control" id="slots" name="slots" min="2" max="30" required>
                        </div>
			<div class="mb-3">
				<label for="frequency" class="form-label">Frequency</label>
				<select class="form-control" id="frequency" name="frequency" required>
					  <option value="oneDay">oneDay</option>
					  <option value="twoDays">twoDays</option>
					  <option value="threeDays">threeDays</option>
					  <option value="fourDays">fourDays</option>
					  <option value="fiveDays">fiveDays</option>
					  <option value="sixDays">sixDays</option>
					  <option value="oneWeek">oneWeek</option>
				</select>
			</div>
                        <div class="mb-3">
                                <?php
                                $endpoint = "https://api.clouding.io/v1/servers?pageSize=200";
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

                                $servers = [];
                                if ($user_prefix) {
                                foreach ($decoderesponse['servers'] as $server) {
                                        if (strpos($server['name'], $user_prefix) === 0) {
                                                $servers[] = $server;
                                        }
                                }
                                } else {
                                        $servers = $decoderesponse['servers'];
                                }
                                ?>
                                <label for="Source" class="form-label">Server:</label>
                                <select id="Source" name="Source" class="form-select">
                                <?php if (!empty($servers)): ?>
                                    <?php foreach ($servers as $server): ?>
                                        <option value="<?php echo $server['id']; ?>" required><?php echo htmlspecialchars($server['name'] ?? ''); ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" required></option>
                                <?php endif; ?>
                                </select>
                                </div>
                        </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="enable_backup">Enable</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>

</script>
</body>
</html>
