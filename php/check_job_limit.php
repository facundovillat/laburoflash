<?php
header('Content-Type: application/json');

include "session_config.php";
include "connection.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Debe estar logueado."]);
    exit;
}

$worker_id = $_SESSION["user_id"];

$check_sub = $conn->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND expires_at > NOW() LIMIT 1");
$check_sub->bind_param("i", $worker_id);
$check_sub->execute();
$sub_result = $check_sub->get_result();
$has_subscription = $sub_result->num_rows > 0;
$check_sub->close();

if ($has_subscription) {
    echo json_encode([
        "success" => true,
        "can_take_job" => true,
        "has_subscription" => true,
        "jobs_taken" => 0,
        "limit" => "unlimited",
        "message" => "Tienes suscripción activa, puedes tomar trabajos sin límite."
    ]);
    $conn->close();
    exit;
}

$sql = "SELECT COUNT(*) as count FROM task_responses tr WHERE tr.worker_id = ? AND tr.status IN ('requested', 'selected') AND tr.created_at >= DATE_SUB(NOW(), INTERVAL 5 DAY)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $worker_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$jobs_taken = (int)$row['count'];
$stmt->close();

$limit = 2;
$can_take_job = $jobs_taken < $limit;

echo json_encode([
    "success" => true,
    "can_take_job" => $can_take_job,
    "has_subscription" => false,
    "jobs_taken" => $jobs_taken,
    "limit" => $limit,
    "remaining" => max(0, $limit - $jobs_taken),
    "message" => $can_take_job 
        ? "Puedes tomar este trabajo. Has tomado {$jobs_taken} de {$limit} trabajos en los últimos 5 días."
        : "Has alcanzado el límite de {$limit} trabajos cada 5 días. Considera suscribirte para tomar trabajos sin límite."
]);

$conn->close();
?>
