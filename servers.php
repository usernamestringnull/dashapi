<?php
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

function randomPassword() {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < 24; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}

if (isset($_POST['create_server'])) {
    $name = $_POST['server_name'];
    $hostname = explode(' ', $name)[0];
    $hostname = preg_replace('/[^a-zA-Z]/', '', $hostname);
    $hostname = strtolower($hostname);
    $flavorId = $_POST['flavor'];
    $source = $_POST['type_source'];
    if ($source === 'image') {
        $sourceId = $_POST['id_source_image'];
    } elseif ($source === 'server') {
        $sourceId = $_POST['id_source_server'];
    } elseif ($source === 'snapshot') {
        $sourceId = $_POST['id_source_snapshot'];
    } elseif ($source === 'backup') {
        $sourceId = $_POST['id_source_backup'];
    } else {
        die("N/A");
    }
    $sizedisk = $_POST['sizedisk'];
    $sshKeyId = $_POST['ssh_key'];
    $firewallId = $_POST['firewallId'];
    $password = randomPassword();
    $url = "https://api.clouding.io/v1/servers";
    $accessConfiguration = [
    	'password' => $password,
    	'savePassword' => true
    ];

    if (!empty($sshKeyId)) {
    	$accessConfiguration['sshKeyId'] = $sshKeyId;
    }
    $data = array(
            'name' => $name,
            'hostname' => $hostname,
            'flavorId' => $flavorId,
            'accessConfiguration' => $accessConfiguration, 
            'volume' => array(
                'source' => $source,
                'id' => $sourceId,
                'ssdGb' => $sizedisk,
                'shutDownSource' => null
            ),
            'enableStrictAntiDDoSFiltering' => false,
            'userData' => null,
            'backupPreferences' => null,
            'vpcs' => array(),
            'publicPortFirewallIds' => array(
                $firewallId
            )
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

    if (isset($responseData['action']['startedAt'])) {
	if (isset($_SESSION['is_impresonation'])) {
                $action = "Create server " . htmlspecialchars($responseData['id']) . " (impersonation). Real user: " . htmlspecialchars($_SESSION['is_impresonation']);
        } else {
                $action = "Create server " . htmlspecialchars($responseData['id']);
        }
        if (!function_exists('logAction')) {
                include 'functions.php';
        }
	logAction($action, $conn);
        echo '
<!-- Modal Success -->
<div class="modal fade show" id="SuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Server creating</h5>
      </div>
      <div class="modal-body">
        New server in progress.
      </div>
      <div class="modal-footer">
        <form action="servers.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    } else {
	if (!function_exists('showErrorModal')) {
           include_once 'functions.php';
    	}
    	showErrorModal($response);
    }
}

if (isset($_POST['stop_server'])) {
    $server_id = $_POST['server_id'];
    if (empty($server_id)) {
        echo "<div class='alert alert-danger text-center'>Error: No Server ID provided</div>";
        exit();
    }
    if (!function_exists('stopServer')) {
                include 'functions.php';
    }
    $httpCode = stopServer($server_id);
    if ($httpCode == 202) {
	    echo '
<!-- Modal Success -->
<div class="modal fade show" id="SuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Server stopping</h5>
      </div>
      <div class="modal-body">
        Stop server in progress.
      </div>
      <div class="modal-footer">
        <form action="servers.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    } else {
    	showErrorModal($httpCode);
    }
}

if (isset($_POST['start_server'])) {
    $server_id = $_POST['server_id'];
    if (empty($server_id)) {
        echo "<div class='alert alert-danger text-center'>Error: No Server ID provided</div>";
        exit();
    }
    if (!function_exists('startServer')) {
                include 'functions.php';
    }
    $httpCode = startServer($server_id);
    if ($httpCode == 202) {
	   echo '
    <!-- Modal Success -->
<div class="modal fade show" id="SuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Server starting</h5>
      </div>
      <div class="modal-body">
        Start server in progress.
      </div>
      <div class="modal-footer">
        <form action="servers.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    } else {
    	showErrorModal($httpCode);
    }
}

if (isset($_POST['reboot_server'])) {
    $server_id = $_POST['server_id'];
    if (empty($server_id)) {
        echo "<div class='alert alert-danger text-center'>Error: No Server ID provided</div>";
        exit();
    }
    if (!function_exists('rebootServer')) {
                include 'functions.php';
    }
    $httpCode = rebootServer($server_id);
    if ($httpCode == 202) {
    echo '
<!-- Modal Success -->
<div class="modal fade show" id="SuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Server rebooting</h5>
      </div>
      <div class="modal-body">
        Reboot server in progress.
      </div>
      <div class="modal-footer">
        <form action="servers.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    } else {
    	showErrorModal($httpCode);
    }
}

if (isset($_POST['hardreboot_server'])) {
    $server_id = $_POST['server_id'];
    if (empty($server_id)) {
        echo "<div class='alert alert-danger text-center'>Error: No Server ID provided</div>";
        exit();
    }
    if (!function_exists('hardrebootServer')) {
                include 'functions.php';
    }
    $httpCode = hardrebootServer($server_id);
    if ($httpCode == 202) {
        echo '
<!-- Modal Success -->
<div class="modal fade show" id="SuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Server hard rebooting</h5>
      </div>
      <div class="modal-body">
        Hard reboot server in progress.
      </div>
      <div class="modal-footer">
        <form action="servers.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    } else {
    	showErrorModal($httpCode);
    }
}

if (isset($_POST['archive_server'])) {
    $server_id = $_POST['server_id'];
    if (empty($server_id)) {
        echo "<div class='alert alert-danger text-center'>Error: No Server ID provided</div>";
        exit();
    }
    if (!function_exists('archiveServer')) {
                include 'functions.php';
    }
    $httpCode = archiveServer($server_id);
    if ($httpCode == 202) {
        echo '
<!-- Modal Success -->
<div class="modal fade show" id="SuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Server archiving</h5>
      </div>
      <div class="modal-body">
        Archive server in progress.
      </div>
      <div class="modal-footer">
        <form action="servers.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    } else {
    	showErrorModal($httpCode);
    }
}

if (isset($_POST['unarchive_server'])) {
    $server_id = $_POST['server_id'];
    if (empty($server_id)) {
        echo "<div class='alert alert-danger text-center'>Error: No Server ID provided</div>";
        exit();
    }
    if (!function_exists('unarchiveServer')) {
                include 'functions.php';
    }
    $httpCode = unarchiveServer($server_id);
    if ($httpCode == 202) {
        echo '
<!-- Modal Success -->
<div class="modal fade show" id="SuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Server unarchiving/h5>
      </div>
      <div class="modal-body">
        Unarchive server in progress.
      </div>
      <div class="modal-footer">
        <form action="servers.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    } else {
    	showErrorModal($httpCode);	
    }
}

if (isset($_POST['delete_server'])) {
    $server_id = $_POST['server_id'];
    if (empty($server_id)) {
        echo "<div class='alert alert-danger text-center'>Error: No Server ID provided</div>";
        exit();
    }

    $url = "https://api.clouding.io/v1/servers/$server_id";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-API-KEY: ' . $apikey
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);                        
    curl_close($ch);

    if ($httpCode == 202) {
	if (isset($_SESSION['is_impresonation'])) {
                $action = "Delete server " . htmlspecialchars($server_id) . " (impersonation). Real user: " . htmlspecialchars($_SESSION['is_impresonation']);
        } else {
                $action = "Delete server " . htmlspecialchars($server_id);
        }
        if (!function_exists('logAction')) {
                include 'functions.php';
	}
	logAction($action, $conn);
   	echo '
<!-- Modal Success -->
<div class="modal fade show" id="SuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Server deleting</h5>
      </div>
      <div class="modal-body">
        Delete server in progress.
      </div>
      <div class="modal-footer">
        <form action="servers.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    } else {
    	showErrorModal($httpCode);
    }
}

$statusColors = [
    'Creating' => 'warning',
    'Starting' => 'success',
    'Active' => 'success',
    'Stopped' => 'danger',
    'Stopping' => 'danger',
    'Rebooting' => 'primary',
    'Resize' => 'info',
    'Unarchiving' => 'secondary',
    'Archived' => 'secondary',
    'Archiving' => 'secondary',
    'Pending' => 'warning',
    'ResettingPassword' => 'info',
    'RestoringBackup' => 'info',
    'RestoringSnapshot' => 'info',
    'Deleted' => 'danger',
    'Deleting' => 'danger',
    'Error' => 'danger',
    'Unknown' => 'secondary',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servers</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content effectup">
        <div class="container mt-4">
            <h1><i class="bi bi-hdd-rack"></i> Servers</h1>
	    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
		<button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#createServerModal"><i class="bi bi-plus-lg"></i> Add Server</button>
	    <?php endif; ?>
<form action='servers.php' method='GET' style='display: inline;'>
    <input type='hidden' name='refresh' value='1'>
    <button type='submit' class='btn btn-secondary mb-3 shadow'>
        <i class='bi bi-arrow-clockwise'></i>
    </button>
</form>
	    <table class="table table-striped table-borderless table-sm shadow" data-toggle="table" data-pagination="true" data-search="true" data-page-size="20">
                <thead>
                    <tr>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><th class="truncate">ID</th><?php endif; ?>
                        <th data-sortable="true">Name</th>
                        <th data-sortable="true">Status</th>
                        <th class="truncate">IP Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servers as $server): ?>
			<tr>
			    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <td><?php echo htmlspecialchars($server['id']); ?></td>
			    <?php endif; ?>
                            <td class="truncate"><?php echo htmlspecialchars($server['name']); ?></td>
			    <td class="truncate">
				<span class="badge shadow bg-<?php echo $statusColors[$server['status']]; ?> text-white">
				        <?php echo htmlspecialchars($server['status']); ?>
				</span>	
			    </td>
			    <td class="truncate"><?php echo htmlspecialchars($server['publicIp']); ?></td>
			    <td>
				<div class="dropdown">
				   <button class="btn btn-secondary btn-sm dropdown-toggle shadow" type="button" id="dropdownMenuButton_<?php echo $server['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false" data-bs-container="body" data-boundary="viewport">
					Actions
				    </button>
				    <ul class="dropdown-menu shadow" aria-labelledby="dropdownMenuButton_<?php echo $server['id']; ?>">
					<?php if (isset($server['status']) && $server['status'] == "Active"): ?>
					<li>
                                            <button class="dropdown-item text-info" data-bs-toggle="modal" data-bs-target="#stopServerModal" data-id="<?php echo $server['id']; ?>"><i class="bi bi-power"></i> Shutdown</button>
                                        </li>
					<li>
                                            <button class="dropdown-item text-warning" data-bs-toggle="modal" data-bs-target="#rebootServerModal" data-id="<?php echo $server['id']; ?>"><i class="bi bi-arrow-clockwise"></i> Reboot</button>
					</li>
					<li>
                                            <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#hardrebootServerModal" data-id="<?php echo $server['id']; ?>"><i class="bi bi-plug"></i> Hard Reboot</button>
					</li>
					<?php endif; ?>
					<?php if (isset($server['status']) && ($server['status'] == "Active" || $server['status'] == "Stopped")): ?>
					<li>
                                            <button class="dropdown-item text-warning" data-bs-toggle="modal" data-bs-target="#archiveServerModal" data-id="<?php echo $server['id']; ?>"><i class="bi bi-archive"></i> Shelve</button>
					</li>
					<?php endif; ?>
					<?php if (isset($server['status']) && $server['status'] == "Stopped"): ?>
					<li>
                                            <button class="dropdown-item text-primary" data-bs-toggle="modal" data-bs-target="#startServerModal" data-id="<?php echo $server['id']; ?>"><i class="bi bi-power"></i> Power On</button>
                                        </li>
					<?php endif; ?>
					<?php if (isset($server['status']) && $server['status'] == "Archived"): ?>
					<li>
					    <button class="dropdown-item text-warning" data-bs-toggle="modal" data-bs-target="#unarchiveServerModal" data-id="<?php echo $server['id']; ?>"><i class="bi bi-archive"></i> Unshelve</button>
					</li>
					<?php endif; ?>
					<?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
					<?php if (isset($server['status']) && in_array($server['status'], ["Active", "Stopped", "Archived"])): ?>
					<li>
                                            <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteServerModal" data-name="<?php echo $server['name']; ?>" data-id="<?php echo $server['id']; ?>"><i class="bi bi-trash"></i> Delete</button>
					</li>
					<?php endif; ?>
					<?php endif; ?>
					<div class="dropdown-divider"></div>
					<li>
                                                <a class="dropdown-item" href="server.php?id=<?php echo $server['id']; ?>"><i class="bi bi-info-circle"></i> More...</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
	    </table>
        </div>
    </div>

    <!-- Modal for creating a new server -->
    <div class="modal fade" id="createServerModal" tabindex="-1" aria-labelledby="createServerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createServerModalLabel">Create New Server</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="servers.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="server_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="server_name" name="server_name" required>
			</div>
			<div class="mb-3">
				<label for="flavor" class="form-label"><i class="bi bi-cpu"></i> Flavor:</label>
				<select id="flavor" name="flavor" class="form-select">
					<option value="0.5x1">0.5x1</option>
					<option value="1x2">1x2</option>
					<option value="1x4">1x4</option>
					<option value="2x4">2x4</option>
					<option value="2x8">2x8</option>
					<option value="4x8">4x8</option>
					<option value="4x16">4x16</option>
					<option value="6x12">6x12</option>
					<option value="6x24">6x24</option>
					<option value="8x16">8x16</option>
					<option value="8x32">8x32</option>
					<option value="10x20">10x20</option>
					<option value="10x40">10x40</option>
					<option value="12x24">12x24</option>
					<option value="12x48">12x48</option>
					<option value="14x28">14x28</option>
					<option value="14x56">14x56</option>
					<option value="16x32">16x32</option>
					<option value="16x64">16x64</option>
					<option value="20x40">20x40</option>
					<option value="20x80">20x80</option>
					<option value="24x48">24x48</option>
					<option value="24x96">24x96</option>
					<option value="28x56">28x56</option>
					<option value="28x112">28x112</option>
					<option value="32x64">32x64</option>
					<option value="32x128">32x128</option>
					<option value="40x80">40x80</option>
					<option value="40x160">40x160</option>
					<option value="48x96">48x96</option>
					<option value="48x192">48x192</option>
				</select>
			<div class="mb-3">
                                <label for="type_source" class="form-label">Type Source:</label>
				<select id="type_source" name="type_source" class="form-select">
					<option value="image">Image</option>
					<option value="server">Server</option>
					<option value="snapshot">Snapshot</option>
					<option value="backup">Backup</option>
				</select>
			</div>
			<div id="image_label" class="mb-3" style="display: none;">
				<?php
                                $endpoint = "https://api.clouding.io/v1/images?pageSize=200";
                                $curl = curl_init();
                                curl_setopt_array($curl, [
                                    CURLOPT_HTTPHEADER     => $headers,
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_URL            => $endpoint,
                                ]);
                                $response = curl_exec($curl);
                                $decoderesponse = json_decode($response, true);
                                curl_close($curl);
                                $images = $decoderesponse['images'];
                                ?>
                                <label for="id_source_image" class="form-label">Image: </label>
                                <select id="id_source_image" name="id_source_image" class="form-select">
                                        <?php foreach ($images as $image): ?>
                                                <option value="<?php echo $image['id']; ?>"><?php echo htmlspecialchars($image['name']); ?></option>
                                        <?php endforeach; ?>
                                </select>
    			</div>
			<div id="server_label" class="mb-3" style="display: none;">
			        <?php
                                $endpoint = "https://api.clouding.io/v1/servers?pageSize=200";
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
			        <label for="id_source_server" class="form-label">Server: </label>
                                <select id="id_source_server" name="id_source_server" class="form-select">
                                       	<?php foreach ($servers as $server): ?>
                                       		<option value="<?php echo $server['id']; ?>"><?php echo htmlspecialchars($server['name']); ?></option>
                                       	<?php endforeach; ?>
				</select>
			</div>
			
			<div id="snapshot_label" class="mb-3" style="display: none;">
                                <?php
                                $endpoint = "https://api.clouding.io/v1/snapshots?pageSize=200";
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
                                ?>
                                <label for="id_source_snapshot" class="form-label">Snapshot: </label>
                                <select id="id_source_snapshot" name="id_source_snapshot" class="form-select">
                                        <?php foreach ($snapshots as $snapshot): ?>
                                                <option value="<?php echo $snapshot['id']; ?>"><?php echo htmlspecialchars($snapshot['name']); ?></option>
                                        <?php endforeach; ?>
                                </select>
                        </div>

			<div id="backup_label" class="mb-3" style="display: none;">
                                <?php
                                $endpoint = "https://api.clouding.io/v1/backups?pageSize=200";
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
                                        if (strpos($backups['serverName'], $user_prefix) === 0) {
                                                $backups[] = $backup;
                                        }
                                }
                                } else {
                                        $backups = $decoderesponse['backups'];
                                }
                                ?>
                                <label for="id_source_backup" class="form-label">Backup: </label>
                                <select id="id_source_backup" name="id_source_backup" class="form-select">
                                        <?php foreach ($backups as $backup): ?>
                                                <option value="<?php echo $backup['id']; ?>"><?php echo htmlspecialchars($backup['serverName']); ?> - <?php echo htmlspecialchars($backup['createdAt']); ?></option>
                                        <?php endforeach; ?>
                                </select>
                        </div>

			<div class="mb-3">
                            <label for="sizedisk" class="form-label"><i class="bi bi-hdd"></i> Size:</label>
                            <input type="number" class="form-control" name="sizedisk" id="sizedisk" min="5" max="1900" step="5" value="5" required>
			</div>
			<div class="mb-3">
				<?php
				$endpoint = "https://api.clouding.io/v1/keypairs";
				$curl = curl_init();
				curl_setopt_array($curl, [
				    CURLOPT_HTTPHEADER     => $headers,
				    CURLOPT_RETURNTRANSFER => true,
				    CURLOPT_URL            => $endpoint,
				]);

				$response = curl_exec($curl);
				$decoderesponse = json_decode($response, true);
				curl_close($curl);

				$ssh_keys = [];
				if ($user_prefix) {
				foreach ($decoderesponse['values'] as $key) {
					if (strpos($key['name'], $user_prefix) === 0) {
						$ssh_keys[] = $key;
        				}
				}
				} else {
    					$ssh_keys = $decoderesponse['values'];
				}
				?>
			    <label for="ssh_key" class="form-label">SSH Key:</label>
				<select id="ssh_key" name="ssh_key" class="form-select">
					<option value="">-- Do not use any SSH key --</option>
					<?php foreach ($ssh_keys as $key): ?>
					<option value="<?php echo $key['id']; ?>"><?php echo htmlspecialchars($key['name']); ?></option>
					<?php endforeach; ?>
                                </select>
			</div>
			<div class="mb-3">
                                <?php
                                $endpoint = "https://api.clouding.io/v1/firewalls?pageSize=200";
                                $curl = curl_init();
                                curl_setopt_array($curl, [
                                    CURLOPT_HTTPHEADER     => $headers,
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_URL            => $endpoint,
                                ]);

                                $response = curl_exec($curl);
                                $decoderesponse = json_decode($response, true);
                                curl_close($curl);

                                $firewalls = [];
                                if ($user_prefix) {
                                foreach ($decoderesponse['values'] as $firewall) {
                                        if (strpos($firewall['name'], $user_prefix) === 0) {
                                                $firewalls[] = $firewall;
                                        }
                                }
                                } else {
                                        $firewalls = $decoderesponse['values'];
                                }
                                ?>
                            <label for="firewallId" class="form-label">Firewall:</label>
                                <select id="firewallId" name="firewallId" class="form-select">
                                        <?php foreach ($firewalls as $firewall): ?>
                                        <option value="<?php echo $firewall['id']; ?>"><?php echo htmlspecialchars($firewall['name']); ?></option>
                                        <?php endforeach; ?>
                                </select>
                        </div>
			</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="create_server">Create Server</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- Modal for deleting a server -->
<div class="modal fade" id="deleteServerModal" tabindex="-1" aria-labelledby="deleteServerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteServerModalLabel">Delete Server "nombre aqui"</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="servers.php" method="POST">
                <div class="modal-body">
                    <p>Are you sure you want to delete this server? This action cannot be undone.</p>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmDeleteCheck" required>
                        <label class="form-check-label" for="confirmDeleteCheck">
                            I understand this will permanently delete the server
                        </label>
                    </div>
                    <input type="hidden" id="deleteserver_id" name="server_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" name="delete_server" id="deleteServerBtn" disabled>Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Modal for starting a server -->
    <div class="modal fade" id="startServerModal" tabindex="-1" aria-labelledby="startServerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="startServerModalLabel">Start Server</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="servers.php" method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to start this server?</p>
                        <input type="hidden" id="startserver_id" name="server_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="start_server">Start</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for stop a server -->
    <div class="modal fade" id="stopServerModal" tabindex="-1" aria-labelledby="stopServerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stopServerModalLabel">Stop Server</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="servers.php" method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to stop this server?</p>
                        <input type="hidden" id="stopserver_id" name="server_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" name="stop_server">Stop</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for rebooting a server -->
    <div class="modal fade" id="rebootServerModal" tabindex="-1" aria-labelledby="rebootServerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rebootServerModalLabel">Reboot Server</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="servers.php" method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to reboot this server?</p>
                        <input type="hidden" id="rebootserver_id" name="server_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" name="reboot_server">Reboot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for hard-rebooting a server -->
    <div class="modal fade" id="hardrebootServerModal" tabindex="-1" aria-labelledby="hardrebootServerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="hardrebootServerModalLabel">Hard Reboot Server</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="servers.php" method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to hard reboot this server?</p>
                        <input type="hidden" id="hardrebootserver_id" name="server_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="hardreboot_server">Hard Reboot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for archiving a server -->
    <div class="modal fade" id="archiveServerModal" tabindex="-1" aria-labelledby="archiveServerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="archiveServerModalLabel">Archive Server</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="servers.php" method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to archive this server?</p>
                        <input type="hidden" id="archiveserver_id" name="server_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" name="archive_server">Archive</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for unarchiving a server -->
    <div class="modal fade" id="unarchiveServerModal" tabindex="-1" aria-labelledby="unarchiveServerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="unarchiveServerModalLabel">Unarchive Server</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="servers.php" method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to unarchive this server?</p>
                        <input type="hidden" id="unarchiveserver_id" name="server_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" name="unarchive_server">Unarchive</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>

document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('.table').addEventListener('click', function (e) {
        const button = e.target.closest('[data-bs-target]');
        if (button) {
	    const targetModal = button.getAttribute('data-bs-target');
	    const serverName = button.getAttribute('data-name');
            const serverId = button.getAttribute('data-id');
            if (serverId) {
		const modalId = targetModal.substring(1);
		const modalIdr = modalId.toLowerCase().replace('modal', '');
                const inputField = document.getElementById(`${modalIdr}_id`);
                if (inputField) {
			inputField.value = serverId;
			const modalTitle = deleteServerModal.querySelector('.modal-title');
			modalTitle.textContent = `Delete Server "${serverName}"`;
                }
            }
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const confirmCheck = document.getElementById('confirmDeleteCheck');
    const deleteBtn = document.getElementById('deleteServerBtn');
    
    confirmCheck.addEventListener('change', function() {
        deleteBtn.disabled = !this.checked;
    });
});

    document.addEventListener('DOMContentLoaded', function () {
        const typeSource = document.getElementById('type_source');
        const imageLabel = document.getElementById('image_label');
	const serverLabel = document.getElementById('server_label');
	const snapshotLabel = document.getElementById('snapshot_label');
	const backupLabel = document.getElementById('backup_label');

        function toggleSource() {
            imageLabel.style.display = typeSource.value === 'image' ? 'block' : 'none';
	    serverLabel.style.display = typeSource.value === 'server' ? 'block' : 'none';
	    snapshotLabel.style.display = typeSource.value === 'snapshot' ? 'block' : 'none';
	    backupLabel.style.display = typeSource.value === 'backup' ? 'block' : 'none';
        }

        toggleSource();

        typeSource.addEventListener('change', toggleSource);
    });

    </script>
</body>
</html>
