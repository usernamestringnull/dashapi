<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'snapshots';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['refresh'])) {
    header("Location: snapshots.php");
    exit();
}

include 'db.php';

$user_prefix = $_SESSION['prefix'];
$apikey = $_SESSION['apikey'];
$endpoint = "https://api.clouding.io/v1/snapshots";
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

$snapshots = [];
if ($user_prefix) {
    foreach ($decoderesponse['snapshots'] as $snapshot) {
        if (strpos($snapshot['sourceServerName'], $user_prefix) === 0) {
                $snapshots[] = $snapshot;
        }
    }
} else {
    $snapshots = $decoderesponse['snapshots'];
}

if (isset($_POST['create_snapshot'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $server = $_POST['Source'];
    $url = "https://api.clouding.io/v1/servers/$server/snapshot";
    $data = array(
        'name' => $name,
        'description' => $description
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
        if (isset($_SESSION['is_impresonation'])) {
                $action = "create snapshot server " . htmlspecialchars($server) . " (impersonation). Real user: " . htmlspecialchars($_SESSION['is_impresonation']);
        } else {
                $action = "create snapshot server " . htmlspecialchars($server);
        }
        if (!function_exists('logAction')) {
                include 'functions.php';
        }
        logAction($action, $conn);
    
    echo '
<!-- Modal -->
<div class="modal fade show" id="snapshotModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Snapshot</h5>
      </div>
      <div class="modal-body">
        New Snapshot in progress.
      </div>
      <div class="modal-footer">
        <form action="snapshots.php" method="POST">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-arrow-clockwise"></i> Close
          </button>
        </form>
      </div>
    </div>
  </div>
</div>';
   } else {
        echo '
    	<!-- Error Modal -->
<div class="modal fade show" id="errorModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Error</h5>
      </div>
      <div class="modal-body">
        Error: ' . htmlspecialchars($response) . '
      </div>
      <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
	</div>
    </div>
  </div>
</div>';
	}
}

if (isset($_POST['delete_snapshot'])) {
    $snap_id = $_POST['snapshot_id'];
    if (empty($snap_id)) {
        echo "<div class='alert alert-danger text-center'>Error: No Snapshot ID provided</div>";
        exit();
    }

    $url = "https://api.clouding.io/v1/snapshots/$snap_id";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-API-KEY: ' . $apikey
    ));

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
    echo '
<!-- Delete Error Modal -->
<div class="modal fade show" id="deleteErrorModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Delete Error</h5>
      </div>
      <div class="modal-body">
        Error: Unable to delete Snapshot.<br>
        ' . htmlspecialchars($error) . '
      </div>
      <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
	</div>
    </div>
  </div>
</div>';
    } else {
        if (isset($_SESSION['is_impresonation'])) {
                $action = "delete snapshot " . htmlspecialchars($snap_id) . " (impersonation). Real user: " . htmlspecialchars($_SESSION['is_impresonation']);
        } else {
                $action = "delete snapshot " . htmlspecialchars($snap_id);
        }
        if (!function_exists('logAction')) {
                include 'functions.php';
        }
        logAction($action, $conn);
	echo '
    	<!-- Success Modal -->
<div class="modal fade show" id="deleteSuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Snapshot Deleted</h5>
      </div>
      <div class="modal-body">
        Snapshot deleted successfully.
      </div>
      <div class="modal-footer">
        <form action="snapshots.php" method="POST">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-arrow-clockwise"></i> Close
          </button>
        </form>
      </div>
    </div>
  </div>
</div>';
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Snapshots</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content effectup" id="content">
        <div class="container mt-4">
	    <h1><i class="bi bi-camera"></i> Snapshots</h1>
	    <button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#createSnapModal"><i class="bi bi-plus-lg"></i> Add Snapshot</button>
	    <button class="btn btn-secondary mb-3 shadow" onclick="window.location.href = window.location.href;"><i class="bi bi-arrow-clockwise"></i></button>
	    <table class="table table-striped shadow" data-toggle="table" data-pagination="true" data-search="true" data-page-size="10">
                <thead>
                    <tr>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><th class="truncate">ID</th><?php endif; ?>
                        <th class="truncate">Name</th>
                        <th class="truncate">Description</th>
			<th class="truncate">Source</th>
			<th class="truncate">Date</th>
			<?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($snapshots as $snapshot): ?>
                        <tr>
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><td><?php echo htmlspecialchars($snapshot['id']); ?></td><?php endif; ?>
                            <td><?php echo htmlspecialchars($snapshot['name']); ?></td>
                            <td class="truncate"><?php echo htmlspecialchars($snapshot['description']); ?></td>
			    <td><?php echo htmlspecialchars($snapshot['sourceServerName']); ?></td>
			    <td class="truncate"><?php echo htmlspecialchars($snapshot['createdAt']); ?></td>
			    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
			    <td>
                                <div class="dropdown">
                                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton_<?php echo $snapshot['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false" data-bs-container="body">Actions</button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton_<?php echo $snapshot['id']; ?>">
					<li>
                                            <button class="dropdown-item text-primary" data-bs-toggle="modal" data-bs-target="#restoreSnapModal" data-id="<?php echo $snapshot['id']; ?>"><i class="bi bi-save"></i> Restore</button>
					</li>
					<li>
                                            <button class="dropdown-item text-info" data-bs-toggle="modal" data-bs-target="#cloneSnapModal" data-id="<?php echo $snapshot['id']; ?>"><i class="bi bi-copy"></i> Clone</button>
                                        </li>
					<li>
                                            <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteSnapModal" data-id="<?php echo $snapshot['id']; ?>"><i class="bi bi-trash"></i> Delete</button>
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

    <!-- Modal for creating a new Snap -->
    <div class="modal fade" id="createSnapModal" tabindex="-1" aria-labelledby="createSnapModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createSnapModalLabel">Create New Snapshot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="snapshots.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description">
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
                        <button type="submit" class="btn btn-primary" name="create_snapshot">Create Snapshot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for deleting a Snap -->
    <div class="modal fade" id="deleteSnapModal" tabindex="-1" aria-labelledby="deleteSnapModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSnapModalLabel">Delete Snapshot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="snapshots.php" method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this Snapshot?</p>
                        <input type="hidden" id="snapshot_id" name="snapshot_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_snapshot">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('.table').addEventListener('click', function (e) {
        if (e.target.matches('[data-bs-target="#deleteSnapModal"]')) {
            const button = e.target;
            const snapshotId = button.getAttribute('data-id');
            document.getElementById('snapshot_id').value = snapshotId;
            console.log("Snapshot ID:", snapshotId);
        }
    });
});

</script>
</body>
</html>
