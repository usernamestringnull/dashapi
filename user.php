<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = 'users';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_superadmin']) {
    header("Location: login.php");
    exit();
}

include 'db.php';

function startImpersonation($user_imp_id) {
    if (!isset($_SESSION['is_impersonation'])) {
	    $_SESSION['is_impresonation'] = $_SESSION['user_id'];
    }
    include 'db.php';
    include 'functions.php';
    $action = "session start impresonation: {$user_imp_id}"; 
    logAction($action, $conn);
    $_SESSION['original_mailbox'] = $_SESSION['mailbox'];
    $_SESSION['original_apikey'] = $_SESSION['apikey'];
    $_SESSION['original_is_admin'] = $_SESSION['is_admin'];
    $_SESSION['original_prefix'] = $_SESSION['prefix'];
    $_SESSION['original_is_superadmin'] = $_SESSION['is_superadmin'];
    $stmt = $conn->prepare("SELECT id, mailbox, password, apikey, is_admin, prefix, is_superadmin FROM users WHERE id = ?");
    $stmt->bind_param("s", $user_imp_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $mailbox, $hashed_password, $apikey, $is_admin, $prefix, $is_superadmin);

    if ($stmt->num_rows > 0) {
            $stmt->fetch();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['mailbox'] = $mailbox;
            $_SESSION['apikey'] = $apikey;
            $_SESSION['is_admin'] = $is_admin;
            $_SESSION['prefix'] = $prefix;
	    $_SESSION['is_superadmin'] = $is_superadmin;
	    $stmt->close();
    	    $conn->close();
            header("Location: index.php");
            exit();
        } else {
	    echo "<div class='alert alert-danger text-center'>Error: User not found.</div>";
	}
    $stmt->close();
    $conn->close();
}

if (isset($_POST['impersonate_user'])) {
    $user_imp_id = intval($_POST['user_imp_id']);
    include 'db.php';
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_imp_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows == 1) {
	startImpersonation($user_imp_id);
        $stmt->close();
        $conn->close();
        header("Location: index.php");
        exit();
    } else {
        echo "<div class='alert alert-danger text-center'>Error: User not found.</div>";
    }
    $stmt->close();
    $conn->close();
}


if (isset($_POST['create_user'])) {
    $mailbox = $_POST['mailbox'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $prefix = $_POST['prefix'];
    $apikey = $_POST['apikey'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    include 'db.php';
    $sql_check = "SELECT * FROM users WHERE mailbox='$mailbox'";
    $result = $conn->query($sql_check);

    if ($result->num_rows > 0) {
        echo "<div class='alert alert-danger text-center'>Error: The mailbox is already registered.</div>";
    } else {

    $sql = "INSERT INTO users (mailbox, password, prefix, apikey, is_admin) VALUES ('$mailbox', '$password', '$prefix', '$apikey', '$is_admin')";
    if ($conn->query($sql) === TRUE) {
	$id_insert = $conn->insert_id;
	if (isset($_SESSION['is_impersonation'])) {
        	$action = "create user " . htmlspecialchars($id_insert) . " - " . htmlspecialchars($mailbox) . " (impersonation). Real user: " . htmlspecialchars($_SESSION['is_impersonation']);
    	} else {
        	$action = "create user " . htmlspecialchars($id_insert) . " - " . htmlspecialchars($mailbox);
	}
	if (!function_exists('logAction')) {
        	include 'functions.php';
	}
	logAction($action, $conn);
    echo '
<!-- New User Created Modal -->
<div class="modal fade show" id="newUserSuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">User Created</h5>
      </div>
      <div class="modal-body">
        New user created successfully.
      </div>
      <div class="modal-footer">
        <form action="user.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    
    } else {
    echo '
<!-- General Error Modal -->
<div class="modal fade show" id="generalErrorModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Error</h5>
      </div>
      <div class="modal-body">
        Error: ' . htmlspecialchars($sql) . '<br>' . htmlspecialchars($conn->error) . '
      </div>
      <div class="modal-footer">
        <form action="user.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    }
    }
}

if (isset($_POST['edit_user'])) {
    $id = $_POST['user_id'];
    $prefix = $_POST['prefix'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $is_superadmin = isset($_POST['is_superadmin']) ? 1 : 0;
    if (isset($_SESSION['is_impersonation'])) {
        $action = "edit user " . htmlspecialchars($id) . " (impersonation). Real user: " . htmlspecialchars($_SESSION['is_impersonation']);
    } else {
        $action = "edit user " . htmlspecialchars($id);
    }
    if (!function_exists('logAction')) {
        include 'functions.php';
    }
    logAction($action, $conn);


    $sql = "UPDATE users SET prefix='$prefix', is_admin='$is_admin', is_superadmin='$is_superadmin' WHERE id='$id'";
    if ($conn->query($sql) === TRUE) {
    echo '
<!-- User Updated Success Modal -->
<div class="modal fade show" id="userUpdateSuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Success</h5>
      </div>
      <div class="modal-body">
        User updated successfully.
      </div>
      <div class="modal-footer">
        <form action="user.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>';
    } else {
	echo '
    <!-- User Update Error Modal -->
<div class="modal fade show" id="userUpdateErrorModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Error</h5>
      </div>
      <div class="modal-body">
        Error: ' . htmlspecialchars($sql) . '<br>' . htmlspecialchars($conn->error) . '
      </div>
      <div class="modal-footer">
        <form action="user.php" method="GET">
          <input type="hidden" name="refresh" value="1">
	  <button type="submit" class="btn btn-secondary">Close</button>
	</form>
      </div>
    </div>
  </div>
</div>';
    }
}

if (isset($_POST['delete_user'])) {
    $id = $_POST['user_id'];
    if (isset($_SESSION['is_impersonation'])) {
        $action = "delete user " . htmlspecialchars($id) . " (impersonation). Real user: " . htmlspecialchars($_SESSION['is_impersonation']);
    } else {
        $action = "delete user " . htmlspecialchars($id);
    }
    if (!function_exists('logAction')) {
        include 'functions.php';
    }
    logAction($action, $conn);
    $sql = "DELETE FROM users WHERE id='$id'";
    if ($conn->query($sql) === TRUE) {
    	echo '
<!-- User Deleted Success Modal -->
<div class="modal fade show" id="userDeleteSuccessModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Success</h5>
      </div>
      <div class="modal-body">
        User deleted successfully.
      </div>
      <div class="modal-footer">
        <form action="user.php" method="GET">
          <input type="hidden" name="refresh" value="1">
          <button type="submit" class="btn btn-secondary">Close</button>
        </form>
	</div>
    </div>
  </div>
</div>';
    } else {
	echo '
    	<!-- User Delete Error Modal -->
<div class="modal fade show" id="userDeleteErrorModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Error</h5>
      </div>
      <div class="modal-body">
        Error: ' . htmlspecialchars($sql) . '<br>' . htmlspecialchars($conn->error) . '
      </div>
      <div class="modal-footer">
        <form action="user.php" method="GET">
          <input type="hidden" name="refresh" value="1">
	  <button type="submit" class="btn btn-secondary">Close</button>
	</form>
	</div>
    </div>
  </div>
</div>';
    }
}

$sql = "SELECT id, mailbox, prefix, is_admin, is_superadmin FROM users";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content effectup">
        <div class="container mt-4">
	    <h1><i class="bi bi-people"></i> Users</h1>
<?php if (isset($_SESSION['is_impresonation'])): ?>
   <button type="button" class="btn btn-primary mb-3 shadow" disabled>
      <i class="bi bi-person-add"></i> Locked</button>
<?php else: ?>
	    <button type="button" class="btn btn-primary mb-3 shadow" data-bs-toggle="modal" data-bs-target="#createUserModal"><i class="bi bi-person-add"></i> Add User</button>
<?php endif; ?>
            <table class="table table-striped table-borderless table-sm shadow" data-toggle="table" data-pagination="true" data-search="true" data-page-size="10">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Prefix</th>
			<th>Admin</th>
                        <th class="truncate">Superadmin</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr >
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['mailbox']; ?></td>
                            <td class="truncate"><?php echo $row['prefix']; ?></td>
                            <td class="truncate"><?php echo $row['is_admin'] ? 'Yes' : 'No'; ?></td>
			    <td class="truncate"><?php echo $row['is_superadmin'] ? 'Yes' : 'No'; ?></td>
			    <td>
                                <div class="dropdown">
                                    <button class="btn btn-secondary btn-sm dropdown-toggle shadow" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">Actions
                                    </button>
				    <ul class="dropdown-menu shadow" aria-labelledby="dropdownMenuButton">
					<?php if (isset($_SESSION['is_impresonation'])): ?>
					   <li>
						<button type="button" class="dropdown-item text-secondary" disabled>
                                                        <i class="bi bi-person-badge"></i> Locked
						</button>
					   </li>
					<?php else: ?>
					<li>
					<form action="user.php" method="POST" style="display: inline;">
				        <input type="hidden" name="user_imp_id" value="<?php echo $row['id']; ?>">
						<button type="submit" class="dropdown-item text-info" name="impersonate_user">
                					<i class="bi bi-person-badge"></i> Switch to
            					</button>
					</form>
					</li>
                                        <li>
					    <button class="dropdown-item text-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" data-id="<?php echo $row['id']; ?>" data-prefix="<?php echo $row['prefix']; ?>" data-is_admin="<?php echo $row['is_admin']; ?>" data-is_superadmin="<?php echo $row['is_superadmin']; ?>"><i class="bi bi-pencil-square"></i> Edit</button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-id="<?php echo $row['id']; ?>"><i class="bi bi-trash"></i> Delete</button>
					</li>
					<?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for creating a new user -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="user.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="mailbox" class="form-label">User</label>
                            <input type="text" class="form-control" id="mailbox" name="mailbox" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="prefix" class="form-label">Prefix</label>
                            <input type="text" class="form-control" id="prefix" name="prefix">
                        </div>
                        <div class="mb-3">
                            <label for="apikey" class="form-label">API Key</label>
                            <input type="text" class="form-control" id="apikey" name="apikey" required>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin">
                            <label class="form-check-label" for="is_admin">Admin</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="create_user">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for editing a user -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="user.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="mb-3">
                            <label for="edit_prefix" class="form-label">Prefix</label>
                            <input type="text" class="form-control" id="edit_prefix" name="prefix">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="edit_is_admin" name="is_admin">
                            <label class="form-check-label" for="edit_is_admin">Admin</label>
			</div>
			<div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="edit_is_superadmin" name="is_superadmin">
                            <label class="form-check-label" for="edit_is_superadmin">Superadmin</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="edit_user">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for deleting a user -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="user.php" method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this user?</p>
                        <input type="hidden" id="delete_user_id" name="user_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_user">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>

document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('.table').addEventListener('click', function (e) {
        if (e.target.matches('[data-bs-target="#editUserModal"]')) {
            const button = e.target;
            document.getElementById('edit_user_id').value = button.getAttribute('data-id');
            document.getElementById('edit_prefix').value = button.getAttribute('data-prefix');
	    document.getElementById('edit_is_admin').checked = button.getAttribute('data-is_admin') === '1';
	    document.getElementById('edit_is_superadmin').checked = button.getAttribute('data-is_superadmin') === '1';
        }

        if (e.target.matches('[data-bs-target="#deleteUserModal"]')) {
            const button = e.target;
            document.getElementById('delete_user_id').value = button.getAttribute('data-id');
        }
    });
});

</script>
</body>
</html>
