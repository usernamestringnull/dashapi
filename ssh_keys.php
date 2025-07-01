<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'ssh_keys';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['refresh'])) {
    header("Location: ssh_keys.php");
    exit();
}

include 'db.php';

$user_prefix = $_SESSION['prefix'];
$apikey = $_SESSION['apikey'];
$endpoint = "https://api.clouding.io/v1/keypairs";
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

if (isset($_POST['create_key'])) {
    $name = $_POST['name'];

    $url = 'https://api.clouding.io/v1/keypairs/generate';
    $data = array(
        'name' => $name
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

    if (isset($responseData['id'])) {
	    echo '<!-- Modal Success -->
<div class="modal fade show" id="SuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">SSH Key created</h5>
      </div>
      <div class="modal-body">
        New SSH Key created successfully.
      </div>
      <div class="modal-footer">
        <form action="ssh_keys.php" method="GET">
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
        <form action="ssh_keys.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    }
}

if (isset($_POST['delete_key'])) {
    $key_id = $_POST['key_id'];
    if (empty($key_id)) {
        echo "<div class='alert alert-danger text-center'>Error: No SSH Key ID provided</div>";
        exit();
    }

    $url = "https://api.clouding.io/v1/keypairs/$key_id";

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

    if ($httpCode === 204) {
	    echo '
<!-- Modal Success -->
<div class="modal fade show" id="SuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">SSH Key deleted</h5>
      </div>
      <div class="modal-body">
        SSH Key deleted successfully. 
      </div>
      <div class="modal-footer">
        <form action="ssh_keys.php" method="GET">
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
        <form action="ssh_keys.php" method="GET">
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSH Keys</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content effectup">
        <div class="container mt-3">
	    <h1><i class="bi bi-filetype-key"></i> SSH Keys</h1>
	    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
	    	<button type="button" class="btn btn-primary shadow" data-bs-toggle="modal" data-bs-target="#createKeyModal"><i class="bi bi-plus-lg"></i> Add Key</button>
	    <?php endif; ?>
	    <button class="btn btn-secondary shadow" onclick="window.location.href = window.location.href;"><i class="bi bi-arrow-clockwise"></i></button>
            <table class="table table-striped shadow" data-toggle="table" data-pagination="true" data-search="true" data-page-size="10">
                <thead>
                    <tr>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><th>ID</th><?php endif; ?>
                        <th>Name</th>
                        <th>Fingerprint</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ssh_keys as $key): ?>
                        <tr>
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><td class="truncate"><?php echo htmlspecialchars($key['id']); ?></td><?php endif; ?>
                            <td><?php echo htmlspecialchars($key['name']); ?></td>
                            <td class="truncate"><?php echo htmlspecialchars($key['fingerprint']); ?></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-secondary btn-sm dropdown-toggle shadow" type="button" id="dropdownMenuButton_<?php echo $key['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                                    <ul class="dropdown-menu shadow" aria-labelledby="dropdownMenuButton_<?php echo $key['id']; ?>">
                                        <li>
					    <button class="dropdown-item" onclick="downloadPublicKey('<?php echo htmlspecialchars($key['publicKey'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($key['name'], ENT_QUOTES, 'UTF-8'); ?>')"><i class="bi bi-download"></i> Download</button>
					</li>
                                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><li>
					    <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteKeyModal" data-id="<?php echo $key['id']; ?>">
						<i class="bi bi-trash"></i> Delete</button>
                                        </li><?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for creating a new SSH Key -->
    <div class="modal fade" id="createKeyModal" tabindex="-1" aria-labelledby="createKeyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createKeyModalLabel">Create New SSH Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="ssh_keys.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="create_key">Create SSH Key</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for deleting an SSH Key -->
    <div class="modal fade" id="deleteKeyModal" tabindex="-1" aria-labelledby="deleteKeyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteKeyModalLabel">Delete SSH Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="ssh_keys.php" method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this SSH Key?</p>
                        <input type="hidden" id="key_id" name="key_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_key">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('.table').addEventListener('click', function (e) {
        if (e.target.matches('[data-bs-target="#deleteKeyModal"]')) {
            const button = e.target;
            const keyId = button.getAttribute('data-id');
            document.getElementById('key_id').value = keyId;
            console.log("SSH Key ID:", keyId);
        }
    });
});

function downloadPublicKey(publicKey, keyName) {
    const filename = `${keyName}.key`;
    const blob = new Blob([publicKey], { type: 'application/x-pem-file' });

    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;

    document.body.appendChild(a);
    a.click();

    document.body.removeChild(a);
    URL.revokeObjectURL(a.href);
}

</script>
</body>
</html>

