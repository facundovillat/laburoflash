<?php
header('Content-Type: application/json');

include "session_config.php";
include "connection.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if (!isset($_SESSION["user_id"])) {
        echo json_encode(["success" => false, "message" => "Debe estar logueado."]);
        exit;
    }
    
    $response_id = isset($_POST["response_id"]) ? (int)$_POST["response_id"] : 0;
    $task_id = isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0;
    $employer_id = $_SESSION["user_id"];
    
    if ($response_id <= 0 || $task_id <= 0) {
        echo json_encode(["success" => false, "message" => "Parámetros inválidos."]);
        exit;
    }
    
    $check_task = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND employer_id = ?");
    $check_task->bind_param("ii", $task_id, $employer_id);
    $check_task->execute();
    $task_result = $check_task->get_result();
    
    if ($task_result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "No tienes permiso para rechazar solicitudes de este trabajo."]);
        $check_task->close();
        $conn->close();
        exit;
    }
    
    $check_response = $conn->prepare("SELECT id FROM task_responses WHERE id = ? AND task_id = ? AND status = 'requested'");
    $check_response->bind_param("ii", $response_id, $task_id);
    $check_response->execute();
    $response_result = $check_response->get_result();
    
    if ($response_result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "La solicitud no existe o ya fue procesada."]);
        $check_task->close();
        $check_response->close();
        $conn->close();
        exit;
    }
    
    $reject_response = $conn->prepare("UPDATE task_responses SET status = 'rejected', updated_at = NOW() WHERE id = ?");
    $reject_response->bind_param("i", $response_id);
    
    if ($reject_response->execute()) {
        echo json_encode(["success" => true, "message" => "Solicitud rechazada exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al rechazar la solicitud: " . $reject_response->error]);
    }
    
    $check_task->close();
    $check_response->close();
    $reject_response->close();
    $conn->close();
    
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
}
?>
