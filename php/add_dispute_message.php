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
    $message = isset($_POST["message"]) ? trim($_POST["message"]) : "";
    $user_id = $_SESSION["user_id"];
    
    if ($task_id <= 0) {
        echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]);
        exit;
    }
    
    if (empty($message)) {
        echo json_encode(["success" => false, "message" => "El mensaje no puede estar vacío."]);
        exit;
    }
    
    $check_access = $conn->prepare("SELECT t.employer_id, ta.worker_id FROM tasks t INNER JOIN task_assignments ta ON t.id = ta.task_id WHERE t.id = ? AND (t.employer_id = ? OR ta.worker_id = ?)");
    $check_access->bind_param("iii", $task_id, $user_id, $user_id);
    $check_access->execute();
    $access_result = $check_access->get_result();
    
    if ($access_result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "No tienes acceso a esta disputa."]);
        $check_access->close();
        $conn->close();
        exit;
    }
    
    $task_data = $access_result->fetch_assoc();
    $is_employer = ($task_data["employer_id"] == $user_id);
    $is_worker = ($task_data["worker_id"] == $user_id);
    
    $check_dispute = $conn->prepare("SELECT id, status FROM completion_disputes WHERE task_id = ? AND status = 'pending'");
    $check_dispute->bind_param("i", $task_id);
    $check_dispute->execute();
    $dispute_result = $check_dispute->get_result();
    
    if ($dispute_result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "No hay disputa activa para este trabajo."]);
        $check_access->close();
        $check_dispute->close();
        $conn->close();
        exit;
    }
    
    $dispute = $dispute_result->fetch_assoc();
    
    if ($is_employer) {
        $update_dispute = $conn->prepare("UPDATE completion_disputes SET employer_message = ?, updated_at = NOW() WHERE id = ?");
        $update_dispute->bind_param("si", $message, $dispute["id"]);
    } else if ($is_worker) {
        $update_dispute = $conn->prepare("UPDATE completion_disputes SET worker_message = ?, updated_at = NOW() WHERE id = ?");
        $update_dispute->bind_param("si", $message, $dispute["id"]);
    } else {
        echo json_encode(["success" => false, "message" => "Rol no válido."]);
        $check_access->close();
        $check_dispute->close();
        $conn->close();
        exit;
    }
    
    if ($update_dispute->execute()) {
        echo json_encode(["success" => true, "message" => "Mensaje agregado a la disputa exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al agregar el mensaje: " . $update_dispute->error]);
    }
    
    $check_access->close();
    $check_dispute->close();
    $update_dispute->close();
    $conn->close();
    
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
}
?>
