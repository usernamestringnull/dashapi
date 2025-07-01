<?php
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$db_host = "localhost";
$db_user = "user_db";
$db_password = "pass_db";
$db_name = "name_db";

mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ALL);

try {
    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ALL);
    $conn = new mysqli($db_host, $db_user, $db_password, $db_name);
} catch (mysqli_sql_exception $e) {
    include __DIR__ . '/errors/500.php';
    exit;
}
mysqli_report(MYSQLI_REPORT_OFF);
?>
