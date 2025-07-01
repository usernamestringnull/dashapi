<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'vpcs';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['refresh'])) {
    header("Location: vpcs.php");
    exit();
}

include 'db.php';

$user_prefix = $_SESSION['prefix'];
$apikey = $_SESSION['apikey'];
$endpoint = "https://api.clouding.io/v1/vpcs?pageSize=200";
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

$vpcs = [];
if ($user_prefix) {
    foreach ($decoderesponse['vpcs'] as $vpc) {
        if (strpos($vpc['name'], $user_prefix) === 0) {
                $vpcs[] = $vpc;
        }
    }
} else {
    $vpcs = $decoderesponse['vpcs'];
}

if (isset($_POST['create_vpc'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $subnet = $_POST['subnet'];

    $url = 'https://api.clouding.io/v1/vpcs';
    $data = array(
        'name' => $name,
        'description' => $description,
        'subnetCidr' => $subnet,
        'dnsNameservers' => null,
        'gatewayIp' => null
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

    if (isset($responseData['createdAt'])) {
	echo '<!-- Modal Success -->
<div class="modal fade show" id="SuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">VPC created</h5>
      </div>
      <div class="modal-body">
        New VPC created successfully.
      </div>
      <div class="modal-footer">
        <form action="vpcs.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    } else {
    echo '
<!-- Modal Error -->
<div class="modal fade show" id="ErrorModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Error</h5>
      </div>
      <div class="modal-body">
        Error: ' . htmlspecialchars($response) . '</div>
      <div class="modal-footer">
        <form action="vpcs.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    }
}

if (isset($_POST['delete_vpc'])) {
    $vpc_id = $_POST['vpc_id'];
    if (empty($vpc_id)) {
        echo "<div class='alert alert-danger text-center'>Error: No VPC ID provided</div>";
        exit();
    }

    $url = "https://api.clouding.io/v1/vpcs/$vpc_id";

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

    if ($httpCode === 204) {
    echo '<!-- Modal Success -->
<div class="modal fade show" id="SuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">VPC deleted</h5>
      </div>
      <div class="modal-body">VPC deleted successfully.</div>
      <div class="modal-footer">
        <form action="vpcs.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    } else {
    echo '<!-- Modal Error -->
<div class="modal fade show" id="ErrorModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Error</h5>
      </div>
      <div class="modal-body">
        Error: ' . htmlspecialchars($response) . '</div>
      <div class="modal-footer">
        <form action="vpcs.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
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
    <title>VPCs</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content effectup">
        <div class="container mt-4">
	    <h1><i class="bi bi-ethernet"></i> VPCs</h1>
	    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
	    	<button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#createVpcModal"><i class="bi bi-plus-lg"></i> Add VPC</button>
	    <?php endif; ?>
	    <button class="btn btn-secondary shadow mb-3" onclick="window.location.href = window.location.href;"><i class="bi bi-arrow-clockwise"></i></button>
	    <table class="table table-striped shadow" data-toggle="table" data-pagination="true" data-search="true" data-page-size="10">
                <thead>
                    <tr>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><th>ID</th><?php endif; ?>
                        <th>Name</th>
                        <th class="truncate">Description</th>
                        <th>Subnet</th>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vpcs as $vpc): ?>
                        <tr>
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><td class="truncate"><?php echo htmlspecialchars($vpc['id']); ?></td><?php endif; ?>
                            <td><?php echo htmlspecialchars($vpc['name']); ?></td>
                            <td class="truncate"><?php echo htmlspecialchars($vpc['description']); ?></td>
                            <td class="truncate"><?php echo htmlspecialchars($vpc['subnetCidr']); ?></td>
			    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
			    <td>
                                <div class="dropdown">
                                    <button class="btn btn-secondary btn-sm dropdown-toggle shadow" type="button" id="dropdownMenuButton_<?php echo $vpc['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                                    <ul class="dropdown-menu shadow" aria-labelledby="dropdownMenuButton_<?php echo $vpc['id']; ?>">
                                        <li>
                                            <button class="dropdown-item text-primary" data-bs-toggle="modal" data-bs-target="#editVpcModal" data-id="<?php echo $vpc['id']; ?>"><i class="bi bi-pencil-square"></i> Edit</button>
					</li>
                                        <li>
                                            <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteVpcModal" data-id="<?php echo $vpc['id']; ?>"><i class="bi bi-trash"></i> Delete</button>
                                        </li>
                                    </ul>
                                </div>
                            </td><?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for creating a new VPC -->
    <div class="modal fade" id="createVpcModal" tabindex="-1" aria-labelledby="createVpcModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createVpcModalLabel">Create New VPC</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="vpcs.php" method="POST">
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
                            <label for="subnet" class="form-label">Subnet CIDR</label>
                            <input type="text" class="form-control" id="subnet" name="subnet">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="create_vpc">Create VPC</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for deleting a VPC -->
    <div class="modal fade" id="deleteVpcModal" tabindex="-1" aria-labelledby="deleteVpcModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteVpcModalLabel">Delete VPC</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="vpcs.php" method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this VPC?</p>
                        <input type="hidden" id="vpc_id" name="vpc_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_vpc">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('.table').addEventListener('click', function (e) {
        if (e.target.matches('[data-bs-target="#deleteVpcModal"]')) {
            const button = e.target;
            const vpcId = button.getAttribute('data-id');
            document.getElementById('vpc_id').value = vpcId;
            console.log("VPC ID:", vpcId);
        }
    });
});
</script>
</body>
</html>

