<?php
header('Content-Type: application/json');

include "session_config.php";
include "connection.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if (!isset($_SESSION["user_id"])) {
        echo json_encode(["success" => false, "message" => "Debe estar logueado para tomar un trabajo."]);
        exit;
    }
    
    $task_id = isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0;
    $worker_id = $_SESSION["user_id"];
    $message = isset($_POST["message"]) ? trim($_POST["message"]) : "";
    
    if ($task_id <= 0) {
        echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]);
        exit;
    }
    
    $check_task = $conn->prepare("SELECT id, employer_id, status FROM tasks WHERE id = ? AND status = 'published'");
    $check_task->bind_param("i", $task_id);
    $check_task->execute();
    $result = $check_task->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "El trabajo no existe o ya no está disponible."]);
        $check_task->close();
        $conn->close();
        exit;
    }
    
    $task = $result->fetch_assoc();
    
    if ($task["employer_id"] == $worker_id) {
        echo json_encode(["success" => false, "message" => "No puedes tomar tu propio trabajo."]);
        $check_task->close();
        $conn->close();
        exit;
    }
    
    $check_response = $conn->prepare("SELECT id, status FROM task_responses WHERE task_id = ? AND worker_id = ?");
    $check_response->bind_param("ii", $task_id, $worker_id);
    $check_response->execute();
    $response_result = $check_response->get_result();
    
    if ($response_result->num_rows > 0) {
        $existing_response = $response_result->fetch_assoc();
        if ($existing_response["status"] != "withdrawn") {
            echo json_encode(["success" => false, "message" => "Ya has solicitado este trabajo anteriormente."]);
            $check_task->close();
            $check_response->close();
            $conn->close();
            exit;
        }
    }
    
    $check_sub = $conn->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND expires_at > NOW() LIMIT 1");
    $check_sub->bind_param("i", $worker_id);
    $check_sub->execute();
    $sub_result = $check_sub->get_result();
    $has_subscription = $sub_result->num_rows > 0;
    $check_sub->close();
    
    if (!$has_subscription) {
        $check_limit = $conn->prepare("SELECT COUNT(*) as count FROM task_responses WHERE worker_id = ? AND status IN ('requested', 'selected') AND created_at >= DATE_SUB(NOW(), INTERVAL 5 DAY)");
        $check_limit->bind_param("i", $worker_id);
        $check_limit->execute();
        $limit_result = $check_limit->get_result();
        $limit_row = $limit_result->fetch_assoc();
        $jobs_taken = (int)$limit_row['count'];
        $check_limit->close();
        
        $limit = 2;
        if ($jobs_taken >= $limit) {
            echo json_encode([
                "success" => false,
                "message" => "Has alcanzado el límite de {$limit} trabajos cada 5 días. Considera suscribirte por $15,000 ARS/mes para tomar trabajos sin límite.",
                "limit_reached" => true,
                "jobs_taken" => $jobs_taken,
                "limit" => $limit
            ]);
            $check_task->close();
            if ($response_result->num_rows > 0) {
                $check_response->close();
            }
            $conn->close();
            exit;
        }
    }
    
    if (!empty($message) && strlen($message) > 240) {
        echo json_encode(["success" => false, "message" => "El mensaje no puede exceder los 240 caracteres."]);
        $check_task->close();
        if ($response_result->num_rows > 0) {
            $check_response->close();
        }
        $conn->close();
        exit;
    }
    
    $message_value = empty($message) ? null : trim($message);
    
    $stmt = $conn->prepare("INSERT INTO task_responses (task_id, worker_id, message, status, created_at) VALUES (?, ?, ?, 'requested', NOW())");
    
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Error al preparar la consulta: " . $conn->error]);
        $check_task->close();
        if ($response_result->num_rows > 0) {
            $check_response->close();
        }
        $conn->close();
        exit;
    }
    
    $stmt->bind_param("iis", $task_id, $worker_id, $message_value);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "¡Trabajo solicitado exitosamente! El empleador revisará tu solicitud."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al solicitar el trabajo: " . $stmt->error]);
    }
    
    $stmt->close();
    $check_task->close();
    if ($response_result->num_rows > 0) {
        $check_response->close();
    }
    $conn->close();
    
} else {
    echo json_encode(["success" => false, "message" => "Acceso no autorizado."]);
}
?>
