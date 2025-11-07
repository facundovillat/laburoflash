<?php
header('Content-Type: application/json');

include "session_config.php";
include "connection.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Debe estar logueado."]);
    exit;
}

$user_id = $_SESSION["user_id"];

$sql = "SELECT id, status, started_at, expires_at, plan_type, price, currency, renewed_at FROM subscriptions WHERE user_id = ? AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$has_active_subscription = false;
$subscription = null;

if ($result->num_rows > 0) {
    $has_active_subscription = true;
    $subscription = $result->fetch_assoc();
    
    $expires_at = new DateTime($subscription['expires_at']);
    $now = new DateTime();
    $days_remaining = $now->diff($expires_at)->days;
    if ($expires_at < $now) {
        $days_remaining = 0;
    }
    $subscription['days_remaining'] = $days_remaining;
}

$jobs_sql = "SELECT COUNT(*) as count FROM task_responses WHERE worker_id = ? AND status IN ('requested', 'selected') AND created_at >= DATE_SUB(NOW(), INTERVAL 5 DAY)";

$jobs_stmt = $conn->prepare($jobs_sql);
$jobs_stmt->bind_param("i", $user_id);
$jobs_stmt->execute();
$jobs_result = $jobs_stmt->get_result();
$jobs_row = $jobs_result->fetch_assoc();
$jobs_taken = (int)$jobs_row['count'];
$jobs_stmt->close();

$limit = 2;
$remaining = max(0, $limit - $jobs_taken);

echo json_encode([
    "success" => true,
    "has_subscription" => $has_active_subscription,
    "subscription" => $subscription,
    "job_stats" => [
        "jobs_taken" => $jobs_taken,
        "limit" => $limit,
        "remaining" => $remaining,
        "has_limit" => !$has_active_subscription
    ],
    "subscription_price" => 15000.00,
    "subscription_currency" => "ARS"
]);

$stmt->close();
$conn->close();
?>
