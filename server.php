<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'servers';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['refresh'])) {
    header("Location: server.php");
    exit();
}

if (isset($_GET['id'])) {
    $server_id = $_GET['id'];
} else {
    header("Location: servers.php");
    exit();
}

include 'db.php';

$user_prefix = $_SESSION['prefix'];
$apikey = $_SESSION['apikey'];
$endpoint = "https://api.clouding.io/v1/servers/$server_id";
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
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$decoderesponse = json_decode($response, true);
curl_close($curl);

if ($httpCode !== 200) {
    header('Location: servers.php');
    exit();
}

if ($user_prefix) {
        if (strpos($decoderesponse['name'], $user_prefix) === 0) {
	    $server = $decoderesponse;
	}
	else {
		header('Location: servers.php?noallow');
		exit();
	}
} else {
	$server = $decoderesponse;
}
$endpoint = "https://api.clouding.io/v1/servers/$server_id/novnc";
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_URL            => $endpoint,
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$noVNC = json_decode($response, true);

$endpoint = "https://api.clouding.io/v1/servers/$server_id/password";
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_URL            => $endpoint,
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$password = json_decode($response, true);

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

$osIcons = [
    'ubuntu'  => 'fa-brands fa-ubuntu',
    'centos'  => 'fa-brands fa-centos',
    'windows' => 'fa-brands fa-windows',
    'debian'  => 'fa-brands fa-debian',
    'freebsd' => 'fa-brands fa-freebsd'
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Server</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content effectup">
     <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center">
            <h1><i class="bi bi-hdd-network"></i> <?= htmlspecialchars($server['name']) ?></h1>
	    <span class="badge fs-4 text-white shadow bg-<?php echo $statusColors[$server['status']]; ?>">Status: <?= htmlspecialchars($server['status']) ?></span></h1>
        </div>

        <!-- Nav tabs -->
        <ul class="nav nav-pills flex-sm-row nav-fill" id="serverTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active shadow fw-bold ms-1" id="details-tab" data-bs-toggle="tab" href="#details" role="tab" aria-controls="details" aria-selected="true">Info</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link shadow fw-bold ms-1" id="firewalls-tab" data-bs-toggle="tab" href="#firewalls" role="tab" aria-controls="firewalls" aria-selected="false">Firewalls</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link shadow fw-bold ms-1" id="backups-tab" data-bs-toggle="tab" href="#backups" role="tab" aria-controls="backups" aria-selected="false">Backups</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link shadow fw-bold ms-1" id="snapshots-tab" data-bs-toggle="tab" href="#snapshots" role="tab" aria-controls="snapshots" aria-selected="false">Snaps</a>
            </li>
        </ul>

        <!-- Tab content -->
        <div class="tab-content mt-3" id="serverTabsContent">
            <!-- Details Tab -->
            <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
			<h5 class="mb-0"><i class="bi bi-hdd-network me-2"></i>Server Details</h5>
			<div class="dropdown">
                		<button class="btn text-white shadow" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
					<i class="fa-solid fa-bars"></i>
				</button>
		                <ul class="dropdown-menu shadow" aria-labelledby="dropdownMenuButton">
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
                                            <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteServerModal" data-id="<?php echo $server['id']; ?>"><i class="bi bi-trash"></i> Delete</button>
                                        </li>
                                        <?php endif; ?>
                                        <?php endif; ?>
				</ul>
            	    	</div>
		    </div>
                    <div class="card-body p-4">
                        <div class="row mb-3">
                            <div class="col-6">
                                <p class="fw-bold mb-1"><i class="bi bi-globe"></i> Public IP</p>
                                <p class="mb-0"><?= htmlspecialchars($server['publicIp']) ?></p>
                            </div>
                            <div class="col-6">
				<p class="fw-bold mb-1"><i class="bi bi-lock"></i> Private IP</p>
			    	<?php if (!empty($server['vpcPorts']) && is_array($server['vpcPorts'])): ?>
				       <?= htmlspecialchars(implode(', ', array_column($server['vpcPorts'], 'ipAddress'))) ?>
				<?php else: ?>
    					N/A
				<?php endif; ?>
			    </div>
		        <div class="col-12">
                	    <p class="fw-bold mb-1"><i class="bi bi-image"></i> Image</p>
			    <p class="mb-0"><?php $iconClass = 'fa-brands fa-linux';
			    $imageName = $server['image']['name'] ?? '';
			    foreach ($osIcons as $os => $icon) {
				if (stripos($imageName, $os) !== false) { 
				   $iconClass = $icon;
                                   break;
    			        }
			    }
			    echo "<i class='$iconClass'></i> " . htmlspecialchars($server['image']['name']);?></p>
			    </div> 
			    <div class="col-6">
                                <p class="fw-bold mb-1"><i class="fa-solid fa-microchip"></i> vCore</p>
                                <p class="mb-0"><?= htmlspecialchars($server['vCores']) ?> vCore</p>
                            </div>
                            <div class="col-6">
                                <p class="fw-bold mb-1"><i class="fa-solid fa-memory"></i> RAM</p>
                                <p class="mb-0"><?= htmlspecialchars($server['ramGb']) ?> GB</p>
			    </div>
			    <div class="col-12">
                                <p class="fw-bold mb-1"><i class="fa-solid fa-calendar"></i> Date</p>
                                <p class="mb-0"><?= htmlspecialchars($server['createdAt']) ?></p>
			    </div>
			    <div class="col-12">
				    <p class="fw-bold mb-1"><i class="fa-solid fa-lock shadow"></i> Password</p>
				    <p class="mb-0">
					<?php
						$passwordText = (isset($password['password']) && !empty($password['password']))
						? htmlspecialchars($password['password'])
						: 'No available';
						$passwordDots = (isset($password['password']) && !empty($password['password']))
						? str_repeat('•', strlen($password['password']))
						: 'No available';
					?>
					<span id="password-plain" style="display:none;"><?= $passwordText ?></span>
					<span id="passwordInput"><?= $passwordDots ?></span>
					<button type="button" id="togglePassword" class="btn btn-link">
				        	<i class="fa-solid fa-eye"></i>
					</button>
					<button type="button" id="copy-password" class="btn btn-link">
					        <i class="fa-solid fa-copy"></i>
					</button>
					<div class="toast-container position-fixed top-0 end-0 p-3" data-bs-delay='{"show":0,"hide":150}'>
					    <div id="password-toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
						<div class="toast-body">
					            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button> Password copied to clipboard!
					        </div>
					    </div>
					</div>
				   </p>
			    </div>
			    <?php if (isset($server['status']) && $server['status'] == "Active"): ?><div class="col-6">
				<button type="button" class="btn btn-primary shadow" onclick="window.open('<?= htmlspecialchars($noVNC['url']) ?>', '_blank')">Console</button>
			    </div><?php endif; ?>
			</div>
		   </div>
		   <div class="card-footer bg-primary">
			 <small class="text-white" id="last-updated">Last updated just now</small>
                   </div>
		</div>
            </div>

            <!-- Firewalls Tab -->
            <div class="tab-pane fade" id="firewalls" role="tabpanel" aria-labelledby="firewalls-tab">
                <div class="card shadow-lg border-0 rounded-4">
		    <div class="card-header bg-secondary text-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i> Firewalls</h5>
                        <div class="dropdown">
                                <button class="btn text-white shadow" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-bars"></i>
                                </button>
                                <ul class="dropdown-menu shadow" aria-labelledby="dropdownMenuButton">
                                    <li><a class="dropdown-item text-success" href="#"><i class="bi bi-plus-lg"></i> Add VPC</a></li>
                                    <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-envelope-check-fill"></i> Enable SMTP</a></li>
                                </ul>
                        </div>
                    </div>
		    <div class="card-body">
                        <h6>Public</h6>
                        <ul>
                            <?php foreach ($server['publicPorts'] as $port): ?>
                                <?php if (!empty($port['firewalls'])): ?>
                                    <?php foreach ($port['firewalls'] as $firewall): ?>
                                        <li><a class="link-offset-2 link-underline link-underline-opacity-0" href=firewall.php?id=<?= htmlspecialchars($firewall['id']) ?>><?= htmlspecialchars($firewall['name']) ?></a></li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li>No public firewalls</li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                        <h6>Private</h6>
                        <ul>
                            <?php foreach ($server['vpcPorts'] as $port): ?>
                                <?php if (!empty($port['firewalls'])): ?>
                                    <?php foreach ($port['firewalls'] as $firewall): ?>
                                        <li><a class="link-offset-2 link-underline link-underline-opacity-0" href=firewall.php?id=<?= htmlspecialchars($firewall['id']) ?>><?= htmlspecialchars($firewall['name']) ?></a> (<?= htmlspecialchars($port['ipAddress']) ?>)</li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li>No private firewalls</li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
		    </div>
                   <div class="card-footer bg-secondary">
			 <small class="text-white"> 
<?php if (!empty($server['features']) && in_array('AllowSmtpOut', $server['features'])): ?>
    <i class="bi bi-envelope text-success"></i> SMTP Out is enabled.
<?php else: ?>
    <i class="bi bi-envelope status-Stopped"></i> SMTP Out is <strong>not</strong> enabled.
<?php endif; ?>
			</small>
                   </div>
                </div>
            </div>

            <!-- Backups Tab -->
            <div class="tab-pane fade" id="backups" role="tabpanel" aria-labelledby="backups-tab">
                <div class="card border-0 rounded-4 shadow-lg">
                    <div class="card-header bg-success text-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="bi bi-box me-2"></i> Backups</h5>
			<?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>  
			<div class="dropdown">
                                <button class="btn text-white shadow" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-bars"></i>
                                </button>
                                <ul class="dropdown-menu shadow" aria-labelledby="dropdownMenuButton">
                                    <li><a class="dropdown-item text-primary" href="#"><i class="bi bi-pencil-square"></i> Edit Backups</a></li>
                                </ul>
			</div>
                        <?php endif; ?>
                    </div>
		    <div class="card-body">
			<div class="row">
                <?php if (!empty($server['backupPreferences'])): ?>
                    <div class="col-12 mb-3">
                        <p><strong>Backup Preferences:</strong> Slots: <?= htmlspecialchars($server['backupPreferences']['slots'] ?? 'N/A') ?> | Frequency: <?= htmlspecialchars($server['backupPreferences']['frequency'] ?? 'N/A') ?></p>
                    </div>
                <?php else: ?>
                    <div class="col-12 mb-3">
                        <p><span class="badge bg-warning">Backups disabled</span></p>
                    </div>
                <?php endif; ?>



 <?php if (!empty($server['backups'])): ?>
            <?php
            $lastBackup = reset($server['backups']);
            ?>
            <div class="col-md-3 mb-3">
                <div class="p-3 border rounded">
                    <p>ID: <?= htmlspecialchars($lastBackup['id'] ?? 'N/A') ?></p>
                    <p>Status: <?= htmlspecialchars($lastBackup['status'] ?? 'N/A') ?></p>
                    <p><small><?= htmlspecialchars($lastBackup['createdAt'] ?? 'N/A') ?></small></p>
                </div>
            </div>

            <div id="more-list-bck" class="d-none">
                <?php foreach ($server['backups'] as $index => $backup): ?>
                    <?php if ($index !== count($server['backups']) - 1): ?>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 border rounded">
                                <p>ID: <?= htmlspecialchars($backup['id'] ?? 'N/A') ?></p>
                                <p>Status: <?= htmlspecialchars($backup['status'] ?? 'N/A') ?></p>
                                <p><small><?= htmlspecialchars($backup['createdAt'] ?? 'N/A') ?></small></p>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="col-12">
                <button class="btn btn-primary shadow" id="show-more-btn-bck">Show more</button>
            </div>

                            <?php else: ?>
                                <p>No backups available</p>
                            <?php endif; ?>
                        </div>
		    </div>
		   <div class="card-footer bg-success">
			    <?php $backupCount = isset($server['backups']) ? count($server['backups']) : 0;?>
			    <small class="text-white" id="last-updated">Total Backups: <?= htmlspecialchars($backupCount) ?></small>
			</div>
		</div>
            </div>

            <!-- Snapshots Tab -->
            <div class="tab-pane fade" id="snapshots" role="tabpanel" aria-labelledby="snapshots-tab">
                <div class="card border-0 rounded-4 shadow-lg">
                    <div class="card-header bg-info text-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="bi bi-camera me-2"></i> Snapshots</h5>
                        <div class="dropdown">
                                <button class="btn text-white shadow" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-bars"></i>
                                </button>
                                <ul class="dropdown-menu shadow" aria-labelledby="dropdownMenuButton">
                                    <li><a class="dropdown-item text-success" href="#"><i class="bi bi-plus-lg"></i> Add Snapshot</a></li>
                                </ul>
                        </div>
                    </div>
		    <div class="card-body">
	<?php if (!empty($server['snapshots'])): ?>
            <?php
            $lastSnap = reset($server['snapshots']);
            ?>
            <div class="col-md-3 mb-3">
                <div class="p-3 border rounded">
                    <p>ID: <?= htmlspecialchars($lastSnap['id'] ?? 'N/A') ?></p>
                    <p>Name: <?= htmlspecialchars($lastSnap['name'] ?? 'N/A') ?></p>
                    <p><small><?= htmlspecialchars($lastSnap['createdAt'] ?? 'N/A') ?></small></p>
                </div>
            </div>

            <div id="more-list-snap" class="d-none">
                <?php foreach ($server['snapshots'] as $index => $snapshots): ?>
                    <?php if ($index !== count($server['snapshots']) - 1): ?>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 border rounded">
                                <p>ID: <?= htmlspecialchars($snapshots['id'] ?? 'N/A') ?></p>
                                <p>Name: <?= htmlspecialchars($snapshots['name'] ?? 'N/A') ?></p>
                                <p><small><?= htmlspecialchars($snapshots['createdAt'] ?? 'N/A') ?></small></p>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="col-12">
                <button class="btn btn-primary shadow" id="show-more-btn-snap">Show more</button>
	    </div>
          <?php else: ?>
                   <p>No snapshots available</p>
          <?php endif; ?>
	    </div>
	    <div class="card-footer bg-info">
                <?php $SnapCount = isset($server['snapshots']) ? count($server['snapshots']) : 0;?>
                <small class="text-white" id="last-updated">Total Snapshots: <?= htmlspecialchars($SnapCount) ?></small>
	   </div>
	</div>
      </div>
    </div>
  </div>
<script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        var passwordText = document.getElementById('password-plain');
        var passwordInput = document.getElementById('passwordInput');
        
        if (passwordText.style.display === 'none') {
            passwordText.style.display = 'inline';
            passwordInput.style.display = 'none';
            this.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
        } else {
            passwordText.style.display = 'none';
            passwordInput.style.display = 'inline';
            this.innerHTML = '<i class="fa-solid fa-eye"></i>';
        }
    });

document.addEventListener('DOMContentLoaded', function () {
    const copyButton = document.getElementById('copy-password');
    const passwordPlain = document.getElementById('password-plain');
    const toast = new bootstrap.Toast(document.getElementById('password-toast'));

    copyButton.addEventListener('click', function () {
        const password = passwordPlain.textContent;
        navigator.clipboard.writeText(password).then(function () {
            toast.show();
        }).catch(function () {
            alert('Failed to copy password');
        });
    });
});

    const pageReloadTime = new Date();

    function timeAgoInSeconds() {
        const now = new Date();
        const diff = now - pageReloadTime;
        const seconds = Math.floor(diff / 1000);
        return seconds + " second" + (seconds > 1 ? "s" : "") + " ago";
    }

    document.getElementById('last-updated').textContent = "Last updated " + timeAgoInSeconds();
    
    setInterval(() => {
        document.getElementById('last-updated').textContent = "Last updated " + timeAgoInSeconds();
    }, 1000);

document.getElementById('show-more-btn-bck').addEventListener('click', function() {
        var moreBack = document.getElementById('more-list-bck');
        moreBack.classList.toggle('d-none');
        this.textContent = moreBack.classList.contains('d-none') ? 'Show more' : 'Show less';
});

document.getElementById('show-more-btn-snap').addEventListener('click', function() {
        var moreSnap = document.getElementById('more-list-snap');
        moreSnap.classList.toggle('d-none');
        this.textContent = moreSnap.classList.contains('d-none') ? 'Show more' : 'Show less';
});

</script>
</body>
</html>
