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
    $reason = isset($_POST["reason"]) ? trim($_POST["reason"]) : "";
    
    if ($task_id <= 0) {
        echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]);
        exit;
    }
    
    if (empty($reason)) {
        echo json_encode(["success" => false, "message" => "Por favor proporciona una razón para rechazar la finalización."]);
        exit;
    }
    
    $check_task = $conn->prepare("SELECT id, employer_id FROM tasks WHERE id = ? AND employer_id = ?");
    $check_task->bind_param("ii", $task_id, $employer_id);
    $check_task->execute();
    $task_result = $check_task->get_result();
    
    if ($task_result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "No tienes permiso para rechazar este trabajo."]);
        $check_task->close();
        $conn->close();
        exit;
    }
    
    $check_assignment = $conn->prepare("SELECT task_id, status, worker_id FROM task_assignments WHERE task_id = ? AND status = 'pending_completion'");
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
    
    $assignment = $assignment_result->fetch_assoc();
    $worker_id = $assignment["worker_id"];
    
    $conn->begin_transaction();
    
    try {
        $update_assignment = $conn->prepare("UPDATE task_assignments SET status = 'disputed' WHERE task_id = ?");
        $update_assignment->bind_param("i", $task_id);
        $update_assignment->execute();
        
        $check_dispute = $conn->prepare("SELECT id FROM completion_disputes WHERE task_id = ? AND status = 'pending'");
        $check_dispute->bind_param("i", $task_id);
        $check_dispute->execute();
        $dispute_result = $check_dispute->get_result();
        
        if ($dispute_result->num_rows > 0) {
            $update_dispute = $conn->prepare("UPDATE completion_disputes SET employer_message = ?, updated_at = NOW() WHERE task_id = ? AND status = 'pending'");
            $update_dispute->bind_param("si", $reason, $task_id);
            $update_dispute->execute();
        } else {
            $create_dispute = $conn->prepare("INSERT INTO completion_disputes (task_id, worker_id, employer_id, initiated_by, employer_message, status, created_at) VALUES (?, ?, ?, 'employer', ?, 'pending', NOW())");
            $create_dispute->bind_param("iiis", $task_id, $worker_id, $employer_id, $reason);
            $create_dispute->execute();
        }
        
        $conn->commit();
        
        echo json_encode(["success" => true, "message" => "Finalización rechazada. Se ha iniciado un proceso de verificación."]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Error al rechazar la finalización: " . $e->getMessage()]);
    }
    
    $check_task->close();
    $check_assignment->close();
    if (isset($update_assignment)) $update_assignment->close();
    if (isset($check_dispute)) $check_dispute->close();
    if (isset($update_dispute)) $update_dispute->close();
    if (isset($create_dispute)) $create_dispute->close();
    $conn->close();
    
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
}
?>
