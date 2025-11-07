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
    $employer_id = $_SESSION["user_id"];
    
    if ($task_id <= 0) {
        echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]);
        exit;
    }
    
    $check_task = $conn->prepare("SELECT id, employer_id FROM tasks WHERE id = ? AND employer_id = ?");
    $check_task->bind_param("ii", $task_id, $employer_id);
    $check_task->execute();
    $task_result = $check_task->get_result();
    
    if ($task_result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "No tienes permiso para confirmar este trabajo."]);
        $check_task->close();
        $conn->close();
        exit;
    }
    
    $check_assignment = $conn->prepare("SELECT task_id, status FROM task_assignments WHERE task_id = ? AND status = 'pending_completion'");
    $check_assignment->bind_param("i", $task_id);
    $check_assignment->execute();
    $assignment_result = $check_assignment->get_result();
    
    if ($assignment_result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Este trabajo no está pendiente de confirmación."]);
        $check_task->close();
        $check_assignment->close();
        $conn->close();
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        $update_assignment = $conn->prepare("UPDATE task_assignments SET status = 'done', confirmed_paid_at = NOW() WHERE task_id = ?");
        $update_assignment->bind_param("i", $task_id);
        $update_assignment->execute();
        
        $update_task = $conn->prepare("UPDATE tasks SET status = 'completed' WHERE id = ?");
        $update_task->bind_param("i", $task_id);
        $update_task->execute();
        
        $resolve_dispute = $conn->prepare("UPDATE completion_disputes SET status = 'resolved', resolved_by = ?, updated_at = NOW() WHERE task_id = ? AND status = 'pending'");
        $resolve_dispute->bind_param("ii", $employer_id, $task_id);
        $resolve_dispute->execute();
        
        $conn->commit();
        
        echo json_encode(["success" => true, "message" => "¡Trabajo confirmado como completado exitosamente!"]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Error al confirmar el trabajo: " . $e->getMessage()]);
    }
    
    $check_task->close();
    $check_assignment->close();
    if (isset($update_assignment)) $update_assignment->close();
    if (isset($update_task)) $update_task->close();
    if (isset($resolve_dispute)) $resolve_dispute->close();
    $conn->close();
    
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
}
?>
