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
$price = 15000.00;
$currency = "ARS";
$plan_type = "worker_premium";

sleep(1);

$check_existing = $conn->prepare("SELECT id, expires_at FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW() LIMIT 1");
$check_existing->bind_param("i", $user_id);
$check_existing->execute();
$existing_result = $check_existing->get_result();

if ($existing_result->num_rows > 0) {
    $existing = $existing_result->fetch_assoc();
    $expires_at = new DateTime($existing['expires_at']);
    $expires_at->modify('+1 month');
    
    $check_renewed = $conn->query("SHOW COLUMNS FROM subscriptions LIKE 'renewed_at'");
    $has_renewed_at = $check_renewed->num_rows > 0;
    
    $update_sql = $has_renewed_at 
        ? "UPDATE subscriptions SET expires_at = ?, renewed_at = NOW() WHERE id = ?"
        : "UPDATE subscriptions SET expires_at = ? WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $expires_at->format('Y-m-d H:i:s'), $existing['id']);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "¡Pago procesado y suscripción renovada exitosamente! Válida hasta " . $expires_at->format('d/m/Y'),
            "expires_at" => $expires_at->format('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al renovar la suscripción: " . $update_stmt->error]);
    }
    
    $update_stmt->close();
    $check_existing->close();
    $conn->close();
    exit;
}

$check_existing->close();

$cancel_old = $conn->prepare("UPDATE subscriptions SET status = 'canceled' WHERE user_id = ? AND status = 'active' AND expires_at <= NOW()");
$cancel_old->bind_param("i", $user_id);
$cancel_old->execute();
$cancel_old->close();

$columns_check = $conn->query("SHOW COLUMNS FROM subscriptions");
$columns = [];
while ($row = $columns_check->fetch_assoc()) {
    $columns[] = $row['Field'];
}

$started_at = new DateTime();
$expires_at = new DateTime();
$expires_at->modify('+1 month');

$has_plan_type = in_array('plan_type', $columns);
$has_price = in_array('price', $columns);
$has_currency = in_array('currency', $columns);

if ($has_plan_type && $has_price && $has_currency) {
    $insert_sql = "INSERT INTO subscriptions (user_id, status, started_at, expires_at, plan_type, price, currency) VALUES (?, 'active', ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("issssd", $user_id, $started_at->format('Y-m-d H:i:s'), $expires_at->format('Y-m-d H:i:s'), $plan_type, $price, $currency);
} else {
    $insert_sql = "INSERT INTO subscriptions (user_id, status, started_at, expires_at) VALUES (?, 'active', ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iss", $user_id, $started_at->format('Y-m-d H:i:s'), $expires_at->format('Y-m-d H:i:s'));
}

if ($insert_stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "¡Pago procesado exitosamente! Suscripción Premium activa hasta " . $expires_at->format('d/m/Y') . ". Ahora puedes tomar trabajos sin límite.",
        "expires_at" => $expires_at->format('Y-m-d H:i:s')
    ]);
} else {
    if (strpos($insert_stmt->error, 'uq_user_active') !== false || strpos($insert_stmt->error, 'Duplicate') !== false) {
        $update_sql = "UPDATE subscriptions SET expires_at = ?, status = 'active' WHERE user_id = ? AND status = 'active'";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $expires_at->format('Y-m-d H:i:s'), $user_id);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "¡Pago procesado exitosamente! Suscripción Premium activa hasta " . $expires_at->format('d/m/Y') . ". Ahora puedes tomar trabajos sin límite.",
                "expires_at" => $expires_at->format('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Error al procesar la suscripción: " . $update_stmt->error]);
        }
        $update_stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Error al crear la suscripción: " . $insert_stmt->error]);
    }
}

$insert_stmt->close();
$conn->close();
?>
