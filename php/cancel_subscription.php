<?php
header('Content-Type: application/json');

include "session_config.php";
include "connection.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit;
}

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Debe estar logueado."]);
    exit;
}

$user_id = $_SESSION["user_id"];

$check_subscription = $conn->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW() LIMIT 1");
$check_subscription->bind_param("i", $user_id);
$check_subscription->execute();
$result = $check_subscription->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "No tienes una suscripción activa para cancelar."]);
    $check_subscription->close();
    $conn->close();
    exit;
}

$subscription = $result->fetch_assoc();
$check_subscription->close();

$cancel_sql = "UPDATE subscriptions SET status = 'canceled' WHERE id = ?";
$cancel_stmt = $conn->prepare($cancel_sql);
$cancel_stmt->bind_param("i", $subscription['id']);

if ($cancel_stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Suscripción cancelada exitosamente. Podrás seguir usando los beneficios hasta la fecha de vencimiento."]);
} else {
    echo json_encode(["success" => false, "message" => "Error al cancelar la suscripción: " . $cancel_stmt->error]);
}

$cancel_stmt->close();
$conn->close();
?>
