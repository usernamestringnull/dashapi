<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'firewalls';

include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_prefix = $_SESSION['prefix'];
$endpoint = "https://api.clouding.io/v1/firewalls?pageSize=200";
$apikey = $_SESSION['apikey'];
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

$firewalls = [];
if ($user_prefix) {
    foreach ($decoderesponse['firewalls'] as $firewall) {
        if (strpos($firewall['name'], $user_prefix) === 0) {
            $firewalls[] = $firewall;
        }
    }
} else {
    $firewalls = $decoderesponse['values'];
}

if (isset($_POST['create_firewall'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];

    $url = 'https://api.clouding.io/v1/firewalls';
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true);

    if ($httpCode === 201) {
        echo "<div class='alert alert-success text-center'>New Firewall created successfully <form action='firewalls.php' method='POST' style='display: inline;'>
    <input type='hidden' name='refresh' value='1'>
    <button type='submit' class='btn btn-primary'>
        <i class='bi bi-arrow-clockwise'>Refresh</i>
    </button>
</form></div>";
    } else {
	    echo "<div class='alert alert-danger text-center'>Error: " . htmlspecialchars($response) . "<form action='firewalls.php' method='POST' style='display: inline;'>
		    <input type='hidden' name='refresh' value='1'>
    <button type='submit' class='btn btn-primary'>
        <i class='bi bi-arrow-clockwise'>Refresh</i>
    </button></div>";
    }
}

if (isset($_POST['delete_firewall'])) {
    $firewall_id = $_POST['firewall_id'];
    if (empty($firewall_id)) {
        echo "<div class='alert alert-danger text-center'>Error: No Firewall ID provided</div>";
        exit();
    }

    $url = "https://api.clouding.io/v1/firewalls/$firewall_id";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-API-KEY: ' . $apikey
    ));

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 204) {
        echo "<div class='alert alert-danger text-center'>Error: Unable to delete Firewall. " . htmlspecialchars($error) . "<input type='hidden' name='refresh' value='1'>
    <button type='submit' class='btn btn-primary'>
        <i class='bi bi-arrow-clockwise'>Refresh</i>
    </button></div>";
    } else {
        echo "<div class='alert alert-success text-center'>Firewall deleted successfully <form action='firewalls.php' method='POST' style='display: inline;'>
    <input type='hidden' name='refresh' value='1'>
    <button type='submit' class='btn btn-primary'>
        <i class='bi bi-arrow-clockwise'>Refresh</i>
    </button>
</form>
</div>";
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Firewalls</title>
    <style>
        .content {
            margin-left: 250px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content effectup">
        <div class="container mt-4">
	    <h1><i class="bi bi-bricks"></i> Firewalls</h1>
	    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
	    <button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#createFirewallModal"><i class="bi bi-plus-lg"></i> Add Profile</button>
	    <?php endif; ?>
	    <button class="btn btn-secondary mb-3 shadow" onclick="window.location.href = window.location.href;"><i class="bi bi-arrow-clockwise"></i></button>
	    <table class="table table-striped shadow" data-toggle="table" data-pagination="true" data-search="true" data-page-size="10">
                <thead>
                    <tr>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><th>ID</th><?php endif; ?>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($firewalls as $firewall): ?>
                        <tr>
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><td class="truncate"><?php echo $firewall['id']; ?></td><?php endif; ?>
                            <td><?php echo $firewall['name']; ?></td>
                            <td class="truncate"><?php echo $firewall['description']; ?></td>
                            <td>
			     	<div class="dropdown">
                                    <button class="btn btn-secondary btn-sm dropdown-toggle shadow" type="button" id="dropdownMenuButton_<?php echo $firewall['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                        Actions
                                    </button>
				    <ul class="dropdown-menu shadow" aria-labelledby="dropdownMenuButton_<?php echo $firewall['id']; ?>">
					<?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
					<li>
                                            <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteFirewallModal" data-id="<?php echo $firewall['id']; ?>"><i class="bi bi-trash"></i> Delete</button>
					</li>
					<div class="dropdown-divider"></div>
					<?php endif; ?>
					<li>
                                            <a class="dropdown-item" href="firewall.php?id=<?php echo $firewall['id']; ?>"><i class="bi bi-info-circle"></i> More...</a>
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

    <!-- Modal for creating a new firewall -->
    <div class="modal fade" id="createFirewallModal" tabindex="-1" aria-labelledby="createFirewallModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createFirewallModalLabel">Create New Firewall</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="firewalls.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="create_firewall">Create Firewall</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for deleting a firewall -->
    <div class="modal fade" id="deleteFirewallModal" tabindex="-1" aria-labelledby="deleteFirewallModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteFirewallModalLabel">Delete Firewall</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="firewalls.php" method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this firewall?</p>
                        <input type="hidden" id="firewall_id" name="firewall_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_firewall">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('.table').addEventListener('click', function (e) {
        if (e.target.matches('[data-bs-target="#deleteFirewallModal"]')) {
            const button = e.target;
            const firewallId = button.getAttribute('data-id');
            document.getElementById('firewall_id').value = firewallId;
        }
    });
});    
</script>
</body>
</html>
