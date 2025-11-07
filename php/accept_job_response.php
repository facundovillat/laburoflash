<?php
header('Content-Type: application/json');

include "session_config.php";
include "connection.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if (!isset($_SESSION["user_id"])) {
        echo json_encode(["success" => false, "message" => "Debe estar logueado."]);
        exit;
    }
    
    $task_id = isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0;
    $response_id = isset($_POST["response_id"]) ? (int)$_POST["response_id"] : 0;
    $worker_id = isset($_POST["worker_id"]) ? (int)$_POST["worker_id"] : 0;
    $employer_id = $_SESSION["user_id"];
    
    if ($task_id <= 0 || $response_id <= 0 || $worker_id <= 0) {
        echo json_encode(["success" => false, "message" => "Parámetros inválidos."]);
        exit;
    }
    
    $check_task = $conn->prepare("SELECT id, status FROM tasks WHERE id = ? AND employer_id = ?");
    $check_task->bind_param("ii", $task_id, $employer_id);
    $check_task->execute();
    $task_result = $check_task->get_result();
    
    if ($task_result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "No tienes permiso para aceptar solicitudes de este trabajo."]);
        $check_task->close();
        $conn->close();
        exit;
    }
    
    $check_response = $conn->prepare("SELECT id, status FROM task_responses WHERE id = ? AND task_id = ? AND worker_id = ? AND status = 'requested'");
    $check_response->bind_param("iii", $response_id, $task_id, $worker_id);
    $check_response->execute();
    $response_result = $check_response->get_result();
    
    if ($response_result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "La solicitud no existe o ya fue procesada."]);
        $check_task->close();
        $check_response->close();
        $conn->close();
        exit;
    }
    
    $check_assignment = $conn->prepare("SELECT task_id FROM task_assignments WHERE task_id = ?");
    $check_assignment->bind_param("i", $task_id);
    $check_assignment->execute();
    $assignment_result = $check_assignment->get_result();
    
    if ($assignment_result->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Este trabajo ya está asignado a otro trabajador."]);
        $check_task->close();
        $check_response->close();
        $check_assignment->close();
        $conn->close();
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        $update_response = $conn->prepare("UPDATE task_responses SET status = 'selected', updated_at = NOW() WHERE id = ?");
        $update_response->bind_param("i", $response_id);
        $update_response->execute();
        
        $reject_others = $conn->prepare("UPDATE task_responses SET status = 'rejected', updated_at = NOW() WHERE task_id = ? AND id != ? AND status = 'requested'");
        $reject_others->bind_param("ii", $task_id, $response_id);
        $reject_others->execute();
        
        $create_assignment = $conn->prepare("INSERT INTO task_assignments (task_id, response_id, worker_id, status, assigned_at) VALUES (?, ?, ?, 'assigned', NOW())");
        $create_assignment->bind_param("iii", $task_id, $response_id, $worker_id);
        $create_assignment->execute();
        
        $update_task = $conn->prepare("UPDATE tasks SET status = 'assigned' WHERE id = ?");
        $update_task->bind_param("i", $task_id);
        $update_task->execute();
        
        $conn->commit();
        
        echo json_encode(["success" => true, "message" => "¡Trabajo asignado exitosamente!"]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Error al asignar el trabajo: " . $e->getMessage()]);
    }
    
    $check_task->close();
    $check_response->close();
    $check_assignment->close();
    if (isset($update_response)) $update_response->close();
    if (isset($reject_others)) $reject_others->close();
    if (isset($create_assignment)) $create_assignment->close();
    if (isset($update_task)) $update_task->close();
    $conn->close();
    
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
}
?>
