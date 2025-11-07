<?php
header('Content-Type: application/json');

include "session_config.php";
include "connection.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Debe estar logueado."]);
    exit;
}

$user_id = $_SESSION["user_id"];

$sql = "SELECT id, status, started_at, expires_at, plan_type, price, currency FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$has_active_subscription = false;
$subscription = null;

if ($result->num_rows > 0) {
    $has_active_subscription = true;
    $subscription = $result->fetch_assoc();
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "has_subscription" => $has_active_subscription,
    "subscription" => $subscription
]);
?>
