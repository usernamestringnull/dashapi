<?php
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


include 'db.php';
if (!function_exists('logAction')) {
        include 'functions.php';
}

if (isset($_POST['logout'])) {
    if (isset($_SESSION['is_impresonation'])) {
	$action = "session destroy impresonation: {$_SESSION['user_id']}";
	$_SESSION['user_id'] = $_SESSION['is_impresonation'];
        $_SESSION['mailbox'] = $_SESSION['original_mailbox'];
        $_SESSION['apikey'] = $_SESSION['original_apikey'];
        $_SESSION['is_admin'] = $_SESSION['original_is_admin'];
        $_SESSION['prefix'] = $_SESSION['original_prefix'];
	$_SESSION['is_superadmin'] = $_SESSION['original_is_superadmin'];
	logAction($action, $conn);
	unset($_SESSION['original_superadmin']);
	unset($_SESSION['original_mailbox']);
	unset($_SESSION['original_apikey']);
	unset($_SESSION['original_is_admin']);
	unset($_SESSION['original_is_prefix']);
	unset($_SESSION['original_is_superadmin']);
	unset($_SESSION['is_impresonation']);
    	header("Location: user.php");
    	exit();
    }
    $action = "session destroy";
    logAction($action, $conn);
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$user_mailbox = $_SESSION['mailbox'];
$user_prefix = $_SESSION['prefix'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0 maximum-scale=1.0, user-scalable=no">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.24.0/dist/bootstrap-table.min.css">
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.24.0/dist/bootstrap-table.min.js"></script>
    <script src="scripts.js"></script>
</head>
<header>
    <nav id="navbar" class="navbar navbar-expand-lg navbar-light bg-light content">
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <span class="navbar-text">
			Welcome, <?php if (isset($_SESSION['is_impresonation'])) { echo "impersonation "; }?>
			<?php echo htmlspecialchars($user_mailbox); ?> (Prefix: <?php echo htmlspecialchars($user_prefix); ?>)
                    </span>
                </li>
            </ul>
        </div>
         <div class="d-flex d-flexoptions ms-auto">
                <button id="themeToggle" class="btn btn-outline-secondary shadow"><i class="bi bi-moon-stars"></i> Dark</button>
		<form class="form-inline ms-1" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <button class="btn btn-outline-danger shadow" type="submit" name="logout"><i class="bi bi-box-arrow-right"></i> Logout <?php if (isset($_SESSION['is_impresonation'])) { echo " as"; }?></button>
                </form>
            </div>
    </nav>
    <button id="sidebarToggle" class="btn btn-primary d-md-none shadow">☰</button>
    <div class="sidebar shadow">
        <div class="d-flex flex-column p-3">
            <h4 class="text-center mb-4">Panel Admin</h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'index') ? 'active' : ''; ?>" href="index.php">Dashboard</a>
                </li>
                <?php if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin']): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'users') ? 'active' : ''; ?>" href="user.php">Users</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'servers') ? 'active' : ''; ?>" href="servers.php">Servers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'snapshots') ? 'active' : ''; ?>" href="snapshots.php">Snapshots</a>
		</li>
		<li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'backups') ? 'active' : ''; ?>" href="backups.php">Backups</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'firewalls') ? 'active' : ''; ?>" href="firewalls.php">Firewalls</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'vpcs') ? 'active' : ''; ?>" href="vpcs.php">VPC's</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'ssh_keys') ? 'active' : ''; ?>" href="ssh_keys.php">SSH Keys</a>
		</li>
		<?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
		<li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'images') ? 'active' : ''; ?>" href="images.php">Images</a>
		<?php endif; ?></li>
		<?php if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin']): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'logs') ? 'active' : ''; ?>" href="logs.php">Logs</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</header>
<script>
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.querySelector('.sidebar');

const closeSidebar = () => {
    sidebar.classList.remove('active');
    sidebar.style.top = '';
    sidebar.style.left = '';
    document.body.classList.remove('blur');
};

sidebarToggle.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    if (sidebar.classList.contains('active')) {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const scrollLeft = window.scrollX || document.documentElement.scrollLeft;
        sidebar.style.top = `${scrollTop}px`;
	sidebar.style.left = `${scrollLeft}px`;
	document.body.classList.add('blur');
    } else {
        closeSidebar();
    }
});

window.addEventListener('scroll', () => {
    if (sidebar.classList.contains('active')) {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const scrollLeft = window.scrollX || document.documentElement.scrollLeft;
        sidebar.style.top = `${scrollTop}px`;
        sidebar.style.left = `${scrollLeft}px`;
    }
});

window.addEventListener('resize', () => {
    if (sidebar.classList.contains('active')) {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const scrollLeft = window.scrollX || document.documentElement.scrollLeft;
        sidebar.style.top = `${scrollTop}px`;
        sidebar.style.left = `${scrollLeft}px`;
    }
});

document.addEventListener('click', (event) => {
    if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target) && sidebar.classList.contains('active')) {
        closeSidebar();
    }
});
</script>
</body>
</html>
