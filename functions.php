<?php

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

function logAction($action, $conn) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User ID is not set in the session.");
    }

    $id_user = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $date_time = date('Y-m-d H:i:s');

    $sql = "INSERT INTO logs (id_user, ip, action, date_time) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error preparing SQL statement: " . $conn->error);
    }

    $stmt->bind_param("isss", $id_user, $ip, $action, $date_time);
    $stmt->execute();
    $stmt->close();
}

function showErrorModal($httpCode, $refreshUrl = 'servers.php') {
    echo '
    <!-- Modal Error -->
    <div class="modal fade show" id="ErrorModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content border-danger">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">Error</h5>
          </div>
          <div class="modal-body">
            Error: ' . htmlspecialchars($httpCode) . '
          </div>
          <div class="modal-footer">
            <form action="' . htmlspecialchars($refreshUrl) . '" method="GET">
              <input type="hidden" name="refresh" value="1">
              <button type="submit" class="btn btn-secondary">Close</button>
            </form>
          </div>
        </div>
      </div>
    </div>';
}

function deleteServer($serverId) {
    $apikey = $_SESSION['apikey'];
    $url = "https://api.clouding.io/v1/servers/$serverId";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "X-API-KEY: $apikey"
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode;
}

function startServer($serverId) {
    $apikey = $_SESSION['apikey'];
    $url = "https://api.clouding.io/v1/servers/$serverId/start";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "X-API-KEY: $apikey"
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode;
}

function stopServer($serverId) {
    $apikey = $_SESSION['apikey'];
    $url = "https://api.clouding.io/v1/servers/$serverId/stop";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "X-API-KEY: $apikey"
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode;
}

function rebootServer($serverId) {
    $apikey = $_SESSION['apikey'];
    $url = "https://api.clouding.io/v1/servers/$serverId/reboot";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "X-API-KEY: $apikey"
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode;
}

function hardrebootServer($serverId) {
    $apikey = $_SESSION['apikey'];
    $url = "https://api.clouding.io/v1/servers/$serverId/hard-reboot";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "X-API-KEY: $apikey"
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode;
}

function archiveServer($serverId) {
    $apikey = $_SESSION['apikey'];
    $url = "https://api.clouding.io/v1/servers/$serverId/archive";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "X-API-KEY: $apikey"
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode;
}

function unarchiveServer($serverId) {
    $apikey = $_SESSION['apikey'];
    $url = "https://api.clouding.io/v1/servers/$serverId/unarchive";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "X-API-KEY: $apikey"
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode;
}

?>
