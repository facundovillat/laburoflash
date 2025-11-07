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
    $worker_id = $_SESSION["user_id"];
    
    if ($task_id <= 0) {
        echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]);
        exit;
    }
    
    $check_assignment = $conn->prepare("SELECT task_id, status FROM task_assignments WHERE task_id = ? AND worker_id = ?");
    $check_assignment->bind_param("ii", $task_id, $worker_id);
    $check_assignment->execute();
    $assignment_result = $check_assignment->get_result();
    
    if ($assignment_result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "No tienes este trabajo asignado."]);
        $check_assignment->close();
        $conn->close();
        exit;
    }
    
    $assignment = $assignment_result->fetch_assoc();
    
    if ($assignment["status"] === "done") {
        echo json_encode(["success" => false, "message" => "Este trabajo ya está completado y confirmado."]);
        $check_assignment->close();
        $conn->close();
        exit;
    }
    
    if ($assignment["status"] === "pending_completion" || $assignment["status"] === "disputed") {
        echo json_encode(["success" => false, "message" => "Este trabajo ya está en proceso de verificación."]);
        $check_assignment->close();
        $conn->close();
        exit;
    }
    
    $get_task = $conn->prepare("SELECT employer_id FROM tasks WHERE id = ?");
    $get_task->bind_param("i", $task_id);
    $get_task->execute();
    $task_result = $get_task->get_result();
    $task_info = $task_result->fetch_assoc();
    $employer_id = $task_info["employer_id"];
    
    $worker_message = isset($_POST["message"]) ? trim($_POST["message"]) : "Trabajo completado según lo acordado.";
    
    $conn->begin_transaction();
    
    try {
        $update_assignment = $conn->prepare("UPDATE task_assignments SET status = 'pending_completion', completed_at = NOW() WHERE task_id = ? AND worker_id = ?");
        $update_assignment->bind_param("ii", $task_id, $worker_id);
        $update_assignment->execute();
        
        $update_task = $conn->prepare("UPDATE tasks SET status = 'in_progress' WHERE id = ?");
        $update_task->bind_param("i", $task_id);
        $update_task->execute();
        
        $create_dispute_record = $conn->prepare("INSERT INTO completion_disputes (task_id, worker_id, employer_id, initiated_by, worker_message, status, created_at) VALUES (?, ?, ?, 'worker', ?, 'pending', NOW()) ON DUPLICATE KEY UPDATE worker_message = ?, updated_at = NOW()");
        $create_dispute_record->bind_param("iiiss", $task_id, $worker_id, $employer_id, $worker_message, $worker_message);
        $create_dispute_record->execute();
        
        $conn->commit();
        
        echo json_encode(["success" => true, "message" => "¡Trabajo marcado como completado! Esperando confirmación del empleador."]);
        
        $get_task->close();
        if (isset($create_dispute_record)) $create_dispute_record->close();
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Error al completar el trabajo: " . $e->getMessage()]);
    }
    
    $check_assignment->close();
    if (isset($update_assignment)) $update_assignment->close();
    if (isset($update_task)) $update_task->close();
    $conn->close();
    
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
}
?>
