<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'firewall';

include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $firewall_id = $_GET['id'];
} else {
    header("Location: firewalls.php");
    exit();
}

$user_prefix = $_SESSION['prefix'];
$endpoint = "https://api.clouding.io/v1/firewalls/$firewall_id";
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
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$decoderesponse = json_decode($response, true);
curl_close($curl);

if ($httpCode !== 200) {
    header('Location: firewalls.php');
    exit();
}

if ($user_prefix) {
        if (strpos($decoderesponse['name'], $user_prefix) === 0) {
	    header("Location: firewalls.php");
            exit();
	}
} else {
    $rules = $decoderesponse['rules'];
}

if (isset($_POST['create_rule'])) {
    $portmin = $_POST['portmin'];
    $portmax = $_POST['portmax'];
    $Protocol = $_POST['Protocol'];
    $Source = $_POST['Source'];
    $description = $_POST['description'];

    $url = "https://api.clouding.io/v1/firewalls/$firewall_id/rules";
    $data = array(
	    'portRangeMin' => $portmin,
	    'portRangeMax' => $portmax,
	    'protocol' => $Protocol,
	    'sourceIp' => $Source,
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
        echo "<div class='alert alert-success text-center'>New Rule created successfully <form action='firewall.php?id=" . htmlspecialchars($firewall_id) . "' method='POST' style='display: inline;'>
    <input type='hidden' name='refresh' value='1'>
    <button type='submit' class='btn btn-primary'>
        <i class='bi bi-arrow-clockwise'>Refresh</i>
    </button>
</form></div>";
    } else {
        echo "<div class='alert alert-danger text-center'>Error: " . htmlspecialchars($response) . "<input type='hidden' name='refresh' value='1'>
    <button type='submit' class='btn btn-primary'>
        <i class='bi bi-arrow-clockwise'>Refresh</i>
    </button></div>";
    }
}

if (isset($_POST['delete_rule'])) {
    $rule_id = $_POST['rule_id'];
    if (empty($rule_id)) {
        echo "<div class='alert alert-danger text-center'>Error: No Rule ID provided</div>";
        exit();
    }

    $url = "https://api.clouding.io/v1/firewalls/rules/$rule_id";

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
        echo "<div class='alert alert-danger text-center'>Error: Unable to delete Rule. " . htmlspecialchars($error) . "<input type='hidden' name='refresh' value='1'>
    <button type='submit' class='btn btn-primary'>
        <i class='bi bi-arrow-clockwise'>Refresh</i>
    </button></div>";
    } else {
        echo "<div class='alert alert-success text-center'>Rule deleted successfully <form action='firewall.php?id=" . htmlspecialchars($firewall_id) . "' method='POST' style='display: inline;'>
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
    <title>Rules</title>
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
	    <h1>Manage Rules</h1>
<?php
if (isset($decoderesponse['attachments'])) {
    echo '<button class="btn btn-primary mb-2 shadow" type="button" id="toggleAttachments">Show Attachments</button>';

    echo '<div id="attachmentsTable" class="d-none">';
    echo '<table class="table table-striped shadow" data-loading-template="loadingTemplate" data-toggle="table" data-search="true">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Server Name</th>';
    echo '<th>Details</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($decoderesponse['attachments'] as $index => $attachment) {
        $serverName = $attachment['serverName'] ?? 'Null';
        $collapseId = "collapseServer" . $index;
        ?>
        <tr>
            <td class="text-truncate"><?php echo htmlspecialchars($serverName); ?></td>
            <td>
		<button class="btn btn-sm btn-outline-primary shadow" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="false" aria-controls="<?php echo $collapseId; ?>">
                    Show More
                </button>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div id="<?php echo $collapseId; ?>" class="collapse">
                    <?php if (!empty($attachment['publicPorts']) || !empty($attachment['vpcPorts'])): ?>
                        <table class="table table-striped shadow">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($attachment['publicPorts'] as $publicPort) {
                                    $ipAddress = $publicPort['ipAddress'] ?? 'No IP Address';
                                    echo '<tr><td>Public</td><td>' . htmlspecialchars($ipAddress) . '</td></tr>';
                                }
                                foreach ($attachment['vpcPorts'] as $vpcPort) {
                                    $ipAddress = $vpcPort['ipAddress'] ?? 'No IP Address';
                                    echo '<tr><td>VPC</td><td>' . htmlspecialchars($ipAddress) . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">No IP Addresses available.</div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
} else {
    echo '<div class="alert alert-warning p-2 m-1">No attachments found.</div>';
}
?>

<script>
    document.getElementById('toggleAttachments').addEventListener('click', function () {
        const attachmentsTable = document.getElementById('attachmentsTable');
        if (attachmentsTable.classList.contains('d-none')) {
            attachmentsTable.classList.remove('d-none');
            this.textContent = 'Hide Attachments';
        } else {
            attachmentsTable.classList.add('d-none');
            this.textContent = 'Show Attachments';
        }
    });
</script>
</p>

	    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#createRuleModal">Add Rule</button><?php endif; ?>
            <table class="table table-striped shadow" data-loading-template="loadingTemplate" data-toggle="table" data-pagination="true" data-search="true" data-page-size="5">
                <thead>
		    <tr>
			<?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><th>ID</th><?php endif; ?>
			<th class="truncate">Description</th>
                        <th class="truncate">Protocol</th>
                        <th class="truncate">Ports</th>
			<th class="truncate">Source</th>
			<th class="truncate">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rules as $rule): ?>
			<tr>
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?><td class="truncate"><?php echo $rule['id']; ?></td><?php endif; ?>
			    <td class="truncate"><?php echo $rule['description']; ?></td>
			    <td class="truncate"><?php echo $rule['protocol']; ?></td>
			    <td class="<?php echo $rule['enabled'] ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $rule['portRangeMin']; ?>-<?php echo $rule['portRangeMax']; ?>
			    </td>
			    <td><?php echo $rule['sourceIp']; ?></td>
		            <td>
				<button type="button" class="btn btn-danger btn-sm shadow" data-bs-toggle="modal" data-bs-target="#deleteRuleModal" data-id="<?php echo $rule['id']; ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for creating a new Rule -->
    <div class="modal fade" id="createRuleModal" tabindex="-1" aria-labelledby="createRuleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createRuleModalLabel">Create new Rule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="firewall.php?id=<?php echo $firewall_id; ?>" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="portmin" class="form-label">Port min</label>
                            <input type="number" class="form-control" id="portmin" name="portmin" required min="0" max="65535">
			</div>
                        <div class="mb-3">
                            <label for="portmax" class="form-label">Port max</label>
                            <input type="number" class="form-control" id="portmax" name="portmax" required min="0" max="65535">
                        </div>
                        <div class="mb-3">
                            <label for="Protocol" class="form-label">Protocol (number)</label>
                            <input type="number" class="form-control" id="Protocol" name="Protocol" required min="0" max="255">
			</div>
                        <div class="mb-3">
                            <label for="Source" class="form-label">Source</label>
                            <input type="text" class="form-control" id="Source" name="Source" required>
			</div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="create_rule">Create Rule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for deleting a rule -->
    <div class="modal fade" id="deleteRuleModal" tabindex="-1" aria-labelledby="deleteRuleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteRuleModalLabel">Delete Rule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="firewall.php?id=<?php echo $firewall_id; ?>" method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this Rule?</p>
			<input type="hidden" id="rule_id" name="rule_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_rule">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


<script>
document.addEventListener('click', function (e) {
    if (e.target.matches('[data-bs-target="#deleteRuleModal"]')) {
        const ruleId = e.target.getAttribute('data-id');
        document.getElementById('rule_id').value = ruleId;
        console.log("Rule ID:", ruleId);
    }
});
</script>

</body>
</html>
