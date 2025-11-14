<?php
include "config_sesion.php";
include "conexion.php";
include __DIR__ . '/lib/utilidades.php';

header('Content-Type: application/json');

$accion = $_GET['accion'] ?? ($_POST['accion'] ?? '');
if (!$accion) {
    echo json_encode(["success" => false, "message" => "Acción no especificada."]);
    exit;
}

switch ($accion) {
    case 'detalle':
        if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["task_id"])) {
            $task_id = (int)$_GET["task_id"];
            if ($task_id <= 0) {
                echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]); exit;
            }
            $sql = "SELECT t.*, tc.name as category_name, sc.name as subcategory_name, l.city, l.province, u.name as employer_name, u.last_name as employer_last_name, u.email as employer_email, u.phone_number as employer_phone FROM tasks t LEFT JOIN task_categories tc ON t.category_id = tc.id LEFT JOIN sub_categories sc ON t.sub_categories_id = sc.id LEFT JOIN locations l ON t.location_id = l.id LEFT JOIN users u ON t.employer_id = u.id WHERE t.id = ? AND t.status = 'published'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $task_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                echo json_encode(["success" => false, "message" => "El trabajo no existe o no está disponible."]); $stmt->close(); $conn->close(); exit;
            }
            $task = $result->fetch_assoc();
            $has_applied = false;
            if (isset($_SESSION["user_id"])) {
                $check_response = $conn->prepare("SELECT id, status FROM task_responses WHERE task_id = ? AND worker_id = ?");
                $worker_id = $_SESSION["user_id"]; $check_response->bind_param("ii", $task_id, $worker_id);
                $check_response->execute(); $response_result = $check_response->get_result();
                $has_applied = $response_result->num_rows > 0; $check_response->close();
            }
            echo json_encode(["success" => true, "data" => $task, "has_applied" => $has_applied]);
            $stmt->close(); $conn->close();
        } else { echo json_encode(["success" => false, "message" => "Parámetros inválidos."]); }
        break;

    case 'postular':
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado para tomar un trabajo."]); exit; }
            $task_id = isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0; $worker_id = $_SESSION["user_id"]; $message = isset($_POST["message"]) ? trim($_POST["message"]) : "";
            if ($task_id <= 0) { echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]); exit; }
            $check_task = $conn->prepare("SELECT id, employer_id, status FROM tasks WHERE id = ? AND status = 'published'"); $check_task->bind_param("i", $task_id); $check_task->execute(); $result = $check_task->get_result();
            if ($result->num_rows === 0) { echo json_encode(["success" => false, "message" => "El trabajo no existe o ya no está disponible."]); $check_task->close(); $conn->close(); exit; }
            $task = $result->fetch_assoc(); if ($task["employer_id"] == $worker_id) { echo json_encode(["success" => false, "message" => "No puedes tomar tu propio trabajo."]); $check_task->close(); $conn->close(); exit; }
            $check_response = $conn->prepare("SELECT id, status FROM task_responses WHERE task_id = ? AND worker_id = ?"); $check_response->bind_param("ii", $task_id, $worker_id); $check_response->execute(); $response_result = $check_response->get_result();
            if ($response_result->num_rows > 0) { $existing_response = $response_result->fetch_assoc(); if ($existing_response["status"] != "withdrawn") { echo json_encode(["success" => false, "message" => "Ya has solicitado este trabajo anteriormente."]); $check_task->close(); $check_response->close(); $conn->close(); exit; } }
            $check_sub = $conn->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND expires_at > NOW() LIMIT 1"); $check_sub->bind_param("i", $worker_id); $check_sub->execute(); $sub_result = $check_sub->get_result(); $has_subscription = $sub_result->num_rows > 0; $check_sub->close();
            if (!$has_subscription) {
                $check_limit = $conn->prepare("SELECT COUNT(*) as count FROM task_responses WHERE worker_id = ? AND status IN ('requested', 'selected') AND created_at >= DATE_SUB(NOW(), INTERVAL 5 DAY)"); $check_limit->bind_param("i", $worker_id); $check_limit->execute(); $limit_result = $check_limit->get_result(); $limit_row = $limit_result->fetch_assoc(); $jobs_taken = (int)$limit_row['count']; $check_limit->close(); $limit = 2;
                if ($jobs_taken >= $limit) { echo json_encode(["success" => false, "message" => "Has alcanzado el límite de {$limit} trabajos cada 5 días. Considera suscribirte por $15,000 ARS/mes para tomar trabajos sin límite.", "limit_reached" => true, "jobs_taken" => $jobs_taken, "limit" => $limit]); $check_task->close(); if ($response_result->num_rows > 0) { $check_response->close(); } $conn->close(); exit; }
            }
            if (!empty($message) && strlen($message) > 240) { echo json_encode(["success" => false, "message" => "El mensaje no puede exceder los 240 caracteres."]); $check_task->close(); if ($response_result->num_rows > 0) { $check_response->close(); } $conn->close(); exit; }
            $message_value = empty($message) ? null : trim($message);
            $stmt = $conn->prepare("INSERT INTO task_responses (task_id, worker_id, message, status, created_at) VALUES (?, ?, ?, 'requested', NOW())"); if (!$stmt) { echo json_encode(["success" => false, "message" => "Error al preparar la consulta: " . $conn->error]); $check_task->close(); if ($response_result->num_rows > 0) { $check_response->close(); } $conn->close(); exit; }
            $stmt->bind_param("iis", $task_id, $worker_id, $message_value);
            if ($stmt->execute()) { echo json_encode(["success" => true, "message" => "¡Trabajo solicitado exitosamente! El empleador revisará tu solicitud."]); } else { echo json_encode(["success" => false, "message" => "Error al solicitar el trabajo: " . $stmt->error]); }
            $stmt->close(); $check_task->close(); if ($response_result->num_rows > 0) { $check_response->close(); } $conn->close();
        } else { echo json_encode(["success" => false, "message" => "Acceso no autorizado."]); }
        break;

    case 'mis_trabajos_empleador':
        if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado."]); exit; }
        $employer_id = $_SESSION["user_id"];
        $sql = "SELECT t.*, tc.name as category_name, sc.name as subcategory_name, l.city, l.province, COUNT(DISTINCT tr.id) as total_responses, COUNT(DISTINCT CASE WHEN tr.status = 'requested' THEN tr.id END) as pending_responses, ta.status as assignment_status, ta.completed_at, CASE WHEN ta.status = 'pending_completion' THEN 1 ELSE 0 END as has_pending_completion FROM tasks t LEFT JOIN task_categories tc ON t.category_id = tc.id LEFT JOIN sub_categories sc ON t.sub_categories_id = sc.id LEFT JOIN locations l ON t.location_id = l.id LEFT JOIN task_responses tr ON t.id = tr.task_id LEFT JOIN task_assignments ta ON t.id = ta.task_id WHERE t.employer_id = ? GROUP BY t.id, ta.status, ta.completed_at ORDER BY t.created_at DESC";
        $stmt = $conn->prepare($sql); $stmt->bind_param("i", $employer_id); $stmt->execute(); $result = $stmt->get_result(); $jobs = []; while ($row = $result->fetch_assoc()) { $jobs[] = $row; } echo json_encode(["success" => true, "data" => $jobs]); $stmt->close(); $conn->close();
        break;

    case 'solicitudes_por_trabajo':
        if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado."]); exit; }
        $task_id = isset($_GET["task_id"]) ? (int)$_GET["task_id"] : 0; $employer_id = $_SESSION["user_id"]; if ($task_id <= 0) { echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]); exit; }
        $check_task = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND employer_id = ?"); $check_task->bind_param("ii", $task_id, $employer_id); $check_task->execute(); $task_result = $check_task->get_result(); if ($task_result->num_rows === 0) { echo json_encode(["success" => false, "message" => "No tienes permiso para ver este trabajo."]); $check_task->close(); $conn->close(); exit; }
        $sql = "SELECT tr.*, u.name, u.last_name, u.email, u.phone_number, ta.status as assignment_status, ta.assigned_at, ta.completed_at, t.employer_id FROM task_responses tr INNER JOIN users u ON tr.worker_id = u.id INNER JOIN tasks t ON tr.task_id = t.id LEFT JOIN task_assignments ta ON tr.task_id = ta.task_id AND tr.worker_id = ta.worker_id WHERE tr.task_id = ? ORDER BY tr.created_at DESC";
        $stmt = $conn->prepare($sql); $stmt->bind_param("i", $task_id); $stmt->execute(); $result = $stmt->get_result(); $responses = []; while ($row = $result->fetch_assoc()) { $responses[] = $row; }
        echo json_encode(["success" => true, "data" => $responses]); $check_task->close(); $stmt->close(); $conn->close();
        break;

    case 'aceptar_solicitud':
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado."]); exit; }
            $task_id = isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0; $response_id = isset($_POST["response_id"]) ? (int)$_POST["response_id"] : 0; $worker_id = isset($_POST["worker_id"]) ? (int)$_POST["worker_id"] : 0; $employer_id = $_SESSION["user_id"];
            if ($task_id <= 0 || $response_id <= 0 || $worker_id <= 0) { echo json_encode(["success" => false, "message" => "Parámetros inválidos."]); exit; }
            $check_task = $conn->prepare("SELECT id, status FROM tasks WHERE id = ? AND employer_id = ?"); $check_task->bind_param("ii", $task_id, $employer_id); $check_task->execute(); $task_result = $check_task->get_result(); if ($task_result->num_rows === 0) { echo json_encode(["success" => false, "message" => "No tienes permiso para aceptar solicitudes de este trabajo."]); $check_task->close(); $conn->close(); exit; }
            $check_response = $conn->prepare("SELECT id, status FROM task_responses WHERE id = ? AND task_id = ? AND worker_id = ? AND status = 'requested'"); $check_response->bind_param("iii", $response_id, $task_id, $worker_id); $check_response->execute(); $response_result = $check_response->get_result(); if ($response_result->num_rows === 0) { echo json_encode(["success" => false, "message" => "La solicitud no existe o ya fue procesada."]); $check_task->close(); $check_response->close(); $conn->close(); exit; }
            $check_assignment = $conn->prepare("SELECT task_id FROM task_assignments WHERE task_id = ?"); $check_assignment->bind_param("i", $task_id); $check_assignment->execute(); $assignment_result = $check_assignment->get_result(); if ($assignment_result->num_rows > 0) { echo json_encode(["success" => false, "message" => "Este trabajo ya está asignado a otro trabajador."]); $check_task->close(); $check_response->close(); $check_assignment->close(); $conn->close(); exit; }
            $conn->begin_transaction();
            try {
                $update_response = $conn->prepare("UPDATE task_responses SET status = 'selected', updated_at = NOW() WHERE id = ?"); $update_response->bind_param("i", $response_id); $update_response->execute();
                $reject_others = $conn->prepare("UPDATE task_responses SET status = 'rejected', updated_at = NOW() WHERE task_id = ? AND id != ? AND status = 'requested'"); $reject_others->bind_param("ii", $task_id, $response_id); $reject_others->execute();
                $create_assignment = $conn->prepare("INSERT INTO task_assignments (task_id, response_id, worker_id, status, assigned_at) VALUES (?, ?, ?, 'assigned', NOW())"); $create_assignment->bind_param("iii", $task_id, $response_id, $worker_id); $create_assignment->execute();
                $update_task = $conn->prepare("UPDATE tasks SET status = 'assigned' WHERE id = ?"); $update_task->bind_param("i", $task_id); $update_task->execute();
                $conn->commit(); echo json_encode(["success" => true, "message" => "¡Trabajo asignado exitosamente!"]); 
            } catch (Exception $e) { $conn->rollback(); echo json_encode(["success" => false, "message" => "Error al asignar el trabajo: " . $e->getMessage()]); }
            $check_task->close(); $check_response->close(); $check_assignment->close(); if (isset($update_response)) $update_response->close(); if (isset($reject_others)) $reject_others->close(); if (isset($create_assignment)) $create_assignment->close(); if (isset($update_task)) $update_task->close(); $conn->close();
        } else { echo json_encode(["success" => false, "message" => "Método no permitido."]); }
        break;

    case 'rechazar_solicitud':
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado."]); exit; }
            $response_id = isset($_POST["response_id"]) ? (int)$_POST["response_id"] : 0; $task_id = isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0; $employer_id = $_SESSION["user_id"];
            if ($response_id <= 0 || $task_id <= 0) { echo json_encode(["success" => false, "message" => "Parámetros inválidos."]); exit; }
            $check_task = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND employer_id = ?"); $check_task->bind_param("ii", $task_id, $employer_id); $check_task->execute(); $task_result = $check_task->get_result(); if ($task_result->num_rows === 0) { echo json_encode(["success" => false, "message" => "No tienes permiso para rechazar solicitudes de este trabajo."]); $check_task->close(); $conn->close(); exit; }
            $check_response = $conn->prepare("SELECT id FROM task_responses WHERE id = ? AND task_id = ? AND status = 'requested'"); $check_response->bind_param("ii", $response_id, $task_id); $check_response->execute(); $response_result = $check_response->get_result(); if ($response_result->num_rows === 0) { echo json_encode(["success" => false, "message" => "La solicitud no existe o ya fue procesada."]); $check_task->close(); $check_response->close(); $conn->close(); exit; }
            $reject_response = $conn->prepare("UPDATE task_responses SET status = 'rejected', updated_at = NOW() WHERE id = ?"); $reject_response->bind_param("i", $response_id);
            if ($reject_response->execute()) { echo json_encode(["success" => true, "message" => "Solicitud rechazada exitosamente."]); } else { echo json_encode(["success" => false, "message" => "Error al rechazar la solicitud: " . $reject_response->error]); }
            $check_task->close(); $check_response->close(); $reject_response->close(); $conn->close();
        } else { echo json_encode(["success" => false, "message" => "Método no permitido."]); }
        break;

    case 'confirmar_finalizacion':
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado."]); exit; }
            $task_id = isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0; $employer_id = $_SESSION["user_id"]; if ($task_id <= 0) { echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]); exit; }
            $check_task = $conn->prepare("SELECT id, employer_id FROM tasks WHERE id = ? AND employer_id = ?"); $check_task->bind_param("ii", $task_id, $employer_id); $check_task->execute(); $task_result = $check_task->get_result(); if ($task_result->num_rows === 0) { echo json_encode(["success" => false, "message" => "No tienes permiso para confirmar este trabajo."]); $check_task->close(); $conn->close(); exit; }
            $check_assignment = $conn->prepare("SELECT task_id, status FROM task_assignments WHERE task_id = ? AND status = 'pending_completion'"); $check_assignment->bind_param("i", $task_id); $check_assignment->execute(); $assignment_result = $check_assignment->get_result(); if ($assignment_result->num_rows === 0) { echo json_encode(["success" => false, "message" => "Este trabajo no está pendiente de confirmación."]); $check_task->close(); $check_assignment->close(); $conn->close(); exit; }
            $conn->begin_transaction();
            try {
                $update_assignment = $conn->prepare("UPDATE task_assignments SET status = 'done', confirmed_paid_at = NOW() WHERE task_id = ?"); $update_assignment->bind_param("i", $task_id); $update_assignment->execute();
                $update_task = $conn->prepare("UPDATE tasks SET status = 'completed' WHERE id = ?"); $update_task->bind_param("i", $task_id); $update_task->execute();
                $resolve_dispute = $conn->prepare("UPDATE completion_disputes SET status = 'resolved', resolved_by = ?, updated_at = NOW() WHERE task_id = ? AND status = 'pending'"); $resolve_dispute->bind_param("ii", $employer_id, $task_id); $resolve_dispute->execute();
                $conn->commit(); echo json_encode(["success" => true, "message" => "¡Trabajo confirmado como completado exitosamente!"]); 
            } catch (Exception $e) { $conn->rollback(); echo json_encode(["success" => false, "message" => "Error al confirmar el trabajo: " . $e->getMessage()]); }
            $check_task->close(); $check_assignment->close(); if (isset($update_assignment)) $update_assignment->close(); if (isset($update_task)) $update_task->close(); if (isset($resolve_dispute)) $resolve_dispute->close(); $conn->close();
        } else { echo json_encode(["success" => false, "message" => "Método no permitido."]); }
        break;

    case 'rechazar_finalizacion':
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado."]); exit; }
            $task_id = isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0; $employer_id = $_SESSION["user_id"]; $reason = isset($_POST["reason"]) ? trim($_POST["reason"]) : ""; if ($task_id <= 0) { echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]); exit; } if (empty($reason)) { echo json_encode(["success" => false, "message" => "Por favor proporciona una razón para rechazar la finalización."]); exit; }
            $check_task = $conn->prepare("SELECT id, employer_id FROM tasks WHERE id = ? AND employer_id = ?"); $check_task->bind_param("ii", $task_id, $employer_id); $check_task->execute(); $task_result = $check_task->get_result(); if ($task_result->num_rows === 0) { echo json_encode(["success" => false, "message" => "No tienes permiso para rechazar este trabajo."]); $check_task->close(); $conn->close(); exit; }
            $check_assignment = $conn->prepare("SELECT task_id, status, worker_id FROM task_assignments WHERE task_id = ? AND status = 'pending_completion'"); $check_assignment->bind_param("i", $task_id); $check_assignment->execute(); $assignment_result = $check_assignment->get_result(); if ($assignment_result->num_rows === 0) { echo json_encode(["success" => false, "message" => "Este trabajo no está pendiente de confirmación."]); $check_task->close(); $check_assignment->close(); $conn->close(); exit; }
            $assignment = $assignment_result->fetch_assoc(); $worker_id = $assignment["worker_id"]; $conn->begin_transaction();
            try {
                $update_assignment = $conn->prepare("UPDATE task_assignments SET status = 'disputed' WHERE task_id = ?"); $update_assignment->bind_param("i", $task_id); $update_assignment->execute();
                $check_dispute = $conn->prepare("SELECT id FROM completion_disputes WHERE task_id = ? AND status = 'pending'"); $check_dispute->bind_param("i", $task_id); $check_dispute->execute(); $dispute_result = $check_dispute->get_result();
                if ($dispute_result->num_rows > 0) { $update_dispute = $conn->prepare("UPDATE completion_disputes SET employer_message = ?, updated_at = NOW() WHERE task_id = ? AND status = 'pending'"); $update_dispute->bind_param("si", $reason, $task_id); $update_dispute->execute(); }
                else { $create_dispute = $conn->prepare("INSERT INTO completion_disputes (task_id, worker_id, employer_id, initiated_by, employer_message, status, created_at) VALUES (?, ?, ?, 'employer', ?, 'pending', NOW())"); $create_dispute->bind_param("iiis", $task_id, $worker_id, $employer_id, $reason); $create_dispute->execute(); }
                $conn->commit(); echo json_encode(["success" => true, "message" => "Finalización rechazada. Se ha iniciado un proceso de verificación."]); 
            } catch (Exception $e) { $conn->rollback(); echo json_encode(["success" => false, "message" => "Error al rechazar la finalización: " . $e->getMessage()]); }
            $check_task->close(); $check_assignment->close(); if (isset($update_assignment)) $update_assignment->close(); if (isset($check_dispute)) $check_dispute->close(); if (isset($update_dispute)) $update_dispute->close(); if (isset($create_dispute)) $create_dispute->close(); $conn->close();
        } else { echo json_encode(["success" => false, "message" => "Método no permitido."]); }
        break;

    case 'mis_trabajos_trabajador':
        if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado."]); exit; }
        $worker_id = $_SESSION["user_id"];
        $sql = "SELECT tr.*, t.id as task_id, t.title, t.description, t.duration_hours, t.status as task_status, tc.name as category_name, sc.name as subcategory_name, l.city, l.province, u.name as employer_name, u.last_name as employer_last_name, u.email as employer_email, u.phone_number as employer_phone, ta.status as assignment_status, ta.assigned_at, ta.completed_at FROM task_responses tr INNER JOIN tasks t ON tr.task_id = t.id LEFT JOIN task_categories tc ON t.category_id = tc.id LEFT JOIN sub_categories sc ON t.sub_categories_id = sc.id LEFT JOIN locations l ON t.location_id = l.id LEFT JOIN users u ON t.employer_id = u.id LEFT JOIN task_assignments ta ON tr.task_id = ta.task_id AND tr.worker_id = ta.worker_id WHERE tr.worker_id = ? ORDER BY tr.created_at DESC";
        $stmt = $conn->prepare($sql); $stmt->bind_param("i", $worker_id); $stmt->execute(); $result = $stmt->get_result(); $jobs = []; while ($row = $result->fetch_assoc()) { $jobs[] = $row; } echo json_encode(["success" => true, "data" => $jobs]); $stmt->close(); $conn->close();
        break;

    case 'marcar_completado':
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado."]); exit; }
            $task_id = isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0; $worker_id = $_SESSION["user_id"]; if ($task_id <= 0) { echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]); exit; }
            $check_assignment = $conn->prepare("SELECT task_id, status FROM task_assignments WHERE task_id = ? AND worker_id = ?"); $check_assignment->bind_param("ii", $task_id, $worker_id); $check_assignment->execute(); $assignment_result = $check_assignment->get_result(); if ($assignment_result->num_rows === 0) { echo json_encode(["success" => false, "message" => "No tienes este trabajo asignado."]); $check_assignment->close(); $conn->close(); exit; }
            $assignment = $assignment_result->fetch_assoc(); if ($assignment["status"] === "done") { echo json_encode(["success" => false, "message" => "Este trabajo ya está completado y confirmado."]); $check_assignment->close(); $conn->close(); exit; }
            if ($assignment["status"] === "pending_completion" || $assignment["status"] === "disputed") { echo json_encode(["success" => false, "message" => "Este trabajo ya está en proceso de verificación."]); $check_assignment->close(); $conn->close(); exit; }
            $get_task = $conn->prepare("SELECT employer_id FROM tasks WHERE id = ?"); $get_task->bind_param("i", $task_id); $get_task->execute(); $task_result = $get_task->get_result(); $task_info = $task_result->fetch_assoc(); $employer_id = $task_info["employer_id"]; $worker_message = isset($_POST["message"]) ? trim($_POST["message"]) : "Trabajo completado según lo acordado.";
            $conn->begin_transaction();
            try {
                $update_assignment = $conn->prepare("UPDATE task_assignments SET status = 'pending_completion', completed_at = NOW() WHERE task_id = ? AND worker_id = ?"); $update_assignment->bind_param("ii", $task_id, $worker_id); $update_assignment->execute();
                $update_task = $conn->prepare("UPDATE tasks SET status = 'in_progress' WHERE id = ?"); $update_task->bind_param("i", $task_id); $update_task->execute();
                $create_dispute_record = $conn->prepare("INSERT INTO completion_disputes (task_id, worker_id, employer_id, initiated_by, worker_message, status, created_at) VALUES (?, ?, ?, 'worker', ?, 'pending', NOW()) ON DUPLICATE KEY UPDATE worker_message = ?, updated_at = NOW()"); $create_dispute_record->bind_param("iiiss", $task_id, $worker_id, $employer_id, $worker_message, $worker_message); $create_dispute_record->execute();
                $conn->commit(); echo json_encode(["success" => true, "message" => "¡Trabajo marcado como completado! Esperando confirmación del empleador."]); $get_task->close(); if (isset($create_dispute_record)) $create_dispute_record->close();
            } catch (Exception $e) { $conn->rollback(); echo json_encode(["success" => false, "message" => "Error al completar el trabajo: " . $e->getMessage()]); }
            $check_assignment->close(); if (isset($update_assignment)) $update_assignment->close(); if (isset($update_task)) $update_task->close(); $conn->close();
        } else { echo json_encode(["success" => false, "message" => "Método no permitido."]); }
        break;

    case 'ver_disputa':
        if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado."]); exit; }
        $task_id = isset($_GET["task_id"]) ? (int)$_GET["task_id"] : 0; $user_id = $_SESSION["user_id"]; if ($task_id <= 0) { echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]); exit; }
        $check_access = $conn->prepare("SELECT t.employer_id, ta.worker_id FROM tasks t INNER JOIN task_assignments ta ON t.id = ta.task_id WHERE t.id = ? AND (t.employer_id = ? OR ta.worker_id = ?)"); $check_access->bind_param("iii", $task_id, $user_id, $user_id); $check_access->execute(); $access_result = $check_access->get_result(); if ($access_result->num_rows === 0) { echo json_encode(["success" => false, "message" => "No tienes acceso a esta disputa."]); $check_access->close(); $conn->close(); exit; }
        $sql = "SELECT cd.*, u_worker.name as worker_name, u_worker.last_name as worker_last_name, u_employer.name as employer_name, u_employer.last_name as employer_last_name FROM completion_disputes cd LEFT JOIN users u_worker ON cd.worker_id = u_worker.id LEFT JOIN users u_employer ON cd.employer_id = u_employer.id WHERE cd.task_id = ? AND cd.status = 'pending' ORDER BY cd.created_at DESC LIMIT 1";
        $stmt = $conn->prepare($sql); $stmt->bind_param("i", $task_id); $stmt->execute(); $result = $stmt->get_result(); if ($result->num_rows === 0) { echo json_encode(["success" => false, "message" => "No hay disputa activa para este trabajo."]); $check_access->close(); $stmt->close(); $conn->close(); exit; }
        $dispute = $result->fetch_assoc(); echo json_encode(["success" => true, "data" => $dispute]); $check_access->close(); $stmt->close(); $conn->close();
        break;

    case 'agregar_mensaje_disputa':
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado."]); exit; }
            $task_id = isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0; $message = isset($_POST["message"]) ? trim($_POST["message"]) : ""; $user_id = $_SESSION["user_id"]; if ($task_id <= 0) { echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]); exit; } if (empty($message)) { echo json_encode(["success" => false, "message" => "El mensaje no puede estar vacío."]); exit; }
            $check_access = $conn->prepare("SELECT t.employer_id, ta.worker_id FROM tasks t INNER JOIN task_assignments ta ON t.id = ta.task_id WHERE t.id = ? AND (t.employer_id = ? OR ta.worker_id = ?)"); $check_access->bind_param("iii", $task_id, $user_id, $user_id); $check_access->execute(); $access_result = $check_access->get_result(); if ($access_result->num_rows === 0) { echo json_encode(["success" => false, "message" => "No tienes acceso a esta disputa."]); $check_access->close(); $conn->close(); exit; }
            $task_data = $access_result->fetch_assoc(); $is_employer = ($task_data["employer_id"] == $user_id); $is_worker = ($task_data["worker_id"] == $user_id);
            $check_dispute = $conn->prepare("SELECT id, status FROM completion_disputes WHERE task_id = ? AND status = 'pending'"); $check_dispute->bind_param("i", $task_id); $check_dispute->execute(); $dispute_result = $check_dispute->get_result(); if ($dispute_result->num_rows === 0) { echo json_encode(["success" => false, "message" => "No hay disputa activa para este trabajo."]); $check_access->close(); $check_dispute->close(); $conn->close(); exit; }
            $dispute = $dispute_result->fetch_assoc();
            if ($is_employer) { $update_dispute = $conn->prepare("UPDATE completion_disputes SET employer_message = ?, updated_at = NOW() WHERE id = ?"); $update_dispute->bind_param("si", $message, $dispute["id"]); }
            else if ($is_worker) { $update_dispute = $conn->prepare("UPDATE completion_disputes SET worker_message = ?, updated_at = NOW() WHERE id = ?"); $update_dispute->bind_param("si", $message, $dispute["id"]); }
            else { echo json_encode(["success" => false, "message" => "Rol no válido."]); $check_access->close(); $check_dispute->close(); $conn->close(); exit; }
            if ($update_dispute->execute()) { echo json_encode(["success" => true, "message" => "Mensaje agregado a la disputa exitosamente."]); } else { echo json_encode(["success" => false, "message" => "Error al agregar el mensaje: " . $update_dispute->error]); }
            $check_access->close(); $check_dispute->close(); $update_dispute->close(); $conn->close();
        } else { echo json_encode(["success" => false, "message" => "Método no permitido."]); }
        break;

    case 'info_suscripcion':
        if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado."]); exit; }
        $user_id = $_SESSION["user_id"]; $sql = "SELECT id, status, started_at, expires_at, plan_type, price, currency, renewed_at FROM subscriptions WHERE user_id = ? AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1"; $stmt = $conn->prepare($sql); $stmt->bind_param("i", $user_id); $stmt->execute(); $result = $stmt->get_result(); $has_active_subscription = false; $subscription = null; if ($result->num_rows > 0) { $has_active_subscription = true; $subscription = $result->fetch_assoc(); $expires_at = new DateTime($subscription['expires_at']); $now = new DateTime(); $days_remaining = $now->diff($expires_at)->days; if ($expires_at < $now) { $days_remaining = 0; } $subscription['days_remaining'] = $days_remaining; }
        $jobs_sql = "SELECT COUNT(*) as count FROM task_responses WHERE worker_id = ? AND status IN ('requested', 'selected') AND created_at >= DATE_SUB(NOW(), INTERVAL 5 DAY)"; $jobs_stmt = $conn->prepare($jobs_sql); $jobs_stmt->bind_param("i", $user_id); $jobs_stmt->execute(); $jobs_result = $jobs_stmt->get_result(); $jobs_row = $jobs_result->fetch_assoc(); $jobs_taken = (int)$jobs_row['count']; $jobs_stmt->close(); $limit = 2; $remaining = max(0, $limit - $jobs_taken);
        echo json_encode(["success" => true, "has_subscription" => $has_active_subscription, "subscription" => $subscription, "job_stats" => ["jobs_taken" => $jobs_taken, "limit" => $limit, "remaining" => $remaining, "has_limit" => !$has_active_subscription], "subscription_price" => 15000.00, "subscription_currency" => "ARS"]); $stmt->close(); $conn->close();
        break;

    case 'crear_suscripcion':
        if ($_SERVER["REQUEST_METHOD"] !== "POST") { echo json_encode(["success" => false, "message" => "Método no permitido."]); exit; }
        if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado."]); exit; }
        $user_id = $_SESSION["user_id"]; $price = 15000.00; $currency = "ARS"; $plan_type = "worker_premium"; sleep(1);
        $check_existing = $conn->prepare("SELECT id, expires_at FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW() LIMIT 1"); $check_existing->bind_param("i", $user_id); $check_existing->execute(); $existing_result = $check_existing->get_result();
        if ($existing_result->num_rows > 0) { $existing = $existing_result->fetch_assoc(); $expires_at = new DateTime($existing['expires_at']); $expires_at->modify('+1 month'); $check_renewed = $conn->query("SHOW COLUMNS FROM subscriptions LIKE 'renewed_at'"); $has_renewed_at = $check_renewed->num_rows > 0; $update_sql = $has_renewed_at ? "UPDATE subscriptions SET expires_at = ?, renewed_at = NOW() WHERE id = ?" : "UPDATE subscriptions SET expires_at = ? WHERE id = ?"; $update_stmt = $conn->prepare($update_sql); $update_stmt->bind_param("si", $expires_at->format('Y-m-d H:i:s'), $existing['id']); if ($update_stmt->execute()) { echo json_encode(["success" => true, "message" => "¡Pago procesado y suscripción renovada exitosamente! Válida hasta " . $expires_at->format('d/m/Y'), "expires_at" => $expires_at->format('Y-m-d H:i:s')]); } else { echo json_encode(["success" => false, "message" => "Error al renovar la suscripción: " . $update_stmt->error]); } $update_stmt->close(); $check_existing->close(); $conn->close(); exit; }
        $check_existing->close(); $cancel_old = $conn->prepare("UPDATE subscriptions SET status = 'canceled' WHERE user_id = ? AND status = 'active' AND expires_at <= NOW()"); $cancel_old->bind_param("i", $user_id); $cancel_old->execute(); $cancel_old->close(); $columns_check = $conn->query("SHOW COLUMNS FROM subscriptions"); $columns = []; while ($row = $columns_check->fetch_assoc()) { $columns[] = $row['Field']; }
        $started_at = new DateTime(); $expires_at = new DateTime(); $expires_at->modify('+1 month'); $has_plan_type = in_array('plan_type', $columns); $has_price = in_array('price', $columns); $has_currency = in_array('currency', $columns);
        if ($has_plan_type && $has_price && $has_currency) { $insert_sql = "INSERT INTO subscriptions (user_id, status, started_at, expires_at, plan_type, price, currency) VALUES (?, 'active', ?, ?, ?, ?, ?)"; $insert_stmt = $conn->prepare($insert_sql); $insert_stmt->bind_param("issssd", $user_id, $started_at->format('Y-m-d H:i:s'), $expires_at->format('Y-m-d H:i:s'), $plan_type, $price, $currency); }
        else { $insert_sql = "INSERT INTO subscriptions (user_id, status, started_at, expires_at) VALUES (?, 'active', ?, ?)"; $insert_stmt = $conn->prepare($insert_sql); $insert_stmt->bind_param("iss", $user_id, $started_at->format('Y-m-d H:i:s'), $expires_at->format('Y-m-d H:i:s')); }
        if ($insert_stmt->execute()) { echo json_encode(["success" => true, "message" => "¡Pago procesado exitosamente! Suscripción Premium activa hasta " . $expires_at->format('d/m/Y') . ". Ahora puedes tomar trabajos sin límite.", "expires_at" => $expires_at->format('Y-m-d H:i:s')]); }
        else { if (strpos($insert_stmt->error, 'uq_user_active') !== false || strpos($insert_stmt->error, 'Duplicate') !== false) { $update_sql = "UPDATE subscriptions SET expires_at = ?, status = 'active' WHERE user_id = ? AND status = 'active'"; $update_stmt = $conn->prepare($update_sql); $update_stmt->bind_param("si", $expires_at->format('Y-m-d H:i:s'), $user_id); if ($update_stmt->execute()) { echo json_encode(["success" => true, "message" => "¡Pago procesado exitosamente! Suscripción Premium activa hasta " . $expires_at->format('d/m/Y') . ". Ahora puedes tomar trabajos sin límite.", "expires_at" => $expires_at->format('Y-m-d H:i:s')]); } else { echo json_encode(["success" => false, "message" => "Error al procesar la suscripción: " . $update_stmt->error]); } $update_stmt->close(); } else { echo json_encode(["success" => false, "message" => "Error al crear la suscripción: " . $insert_stmt->error]); } }
        $insert_stmt->close(); $conn->close();
        break;

    case 'cancelar_suscripcion':
        if ($_SERVER["REQUEST_METHOD"] !== "POST") { echo json_encode(["success" => false, "message" => "Método no permitido."]); exit; }
        if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado."]); exit; }
        $user_id = $_SESSION["user_id"]; $check_subscription = $conn->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW() LIMIT 1"); $check_subscription->bind_param("i", $user_id); $check_subscription->execute(); $result = $check_subscription->get_result(); if ($result->num_rows === 0) { echo json_encode(["success" => false, "message" => "No tienes una suscripción activa para cancelar."]); $check_subscription->close(); $conn->close(); exit; }
        $subscription = $result->fetch_assoc(); $check_subscription->close(); $cancel_sql = "UPDATE subscriptions SET status = 'canceled' WHERE id = ?"; $cancel_stmt = $conn->prepare($cancel_sql); $cancel_stmt->bind_param("i", $subscription['id']); if ($cancel_stmt->execute()) { echo json_encode(["success" => true, "message" => "Suscripción cancelada exitosamente. Podrás seguir usando los beneficios hasta la fecha de vencimiento."]); } else { echo json_encode(["success" => false, "message" => "Error al cancelar la suscripción: " . $cancel_stmt->error]); } $cancel_stmt->close(); $conn->close();
        break;

    case 'publicar_trabajo':
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $required_fields = ['title', 'description', 'category_id', 'location_id', 'address_text', 'duration_hours', 'sub_categories_id', 'terms_conditions'];
            foreach ($required_fields as $field) { if (!isset($_POST[$field]) || empty($_POST[$field])) { echo json_encode(["success" => false, "message" => "El campo " . $field . " es requerido."]); exit; } }
            $title = trim($_POST["title"]); $description = trim($_POST["description"]); $category_id = (int)$_POST["category_id"]; $location_id = (int)$_POST["location_id"]; $address_text = trim($_POST["address_text"]); $duration_hours = (float)$_POST["duration_hours"]; $sub_categories_id = (int)$_POST["sub_categories_id"]; $terms_conditions = $_POST["terms_conditions"];
            if (strlen($title) < 5 || strlen($title) > 120) { echo json_encode(["success" => false, "message" => "El título debe tener entre 5 y 120 caracteres."]); exit; }
            if (strlen($description) < 10 || strlen($description) > 1000) { echo json_encode(["success" => false, "message" => "La descripción debe tener entre 10 y 1000 caracteres."]); exit; }
            if ($duration_hours < 0.5 || $duration_hours > 24) { echo json_encode(["success" => false, "message" => "La duración debe estar entre 0.5 y 24 horas."]); exit; }
            if ($terms_conditions !== "on") { echo json_encode(["success" => false, "message" => "Debe aceptar los términos y condiciones."]); exit; }
            $check_category = $conn->prepare("SELECT id FROM task_categories WHERE id = ?"); $check_category->bind_param("i", $category_id); $check_category->execute(); $check_category->store_result(); if ($check_category->num_rows === 0) { echo json_encode(["success" => false, "message" => "La categoría seleccionada no es válida."]); exit; }
            $check_location = $conn->prepare("SELECT id FROM locations WHERE id = ?"); $check_location->bind_param("i", $location_id); $check_location->execute(); $check_location->store_result(); if ($check_location->num_rows === 0) { echo json_encode(["success" => false, "message" => "La ubicación seleccionada no es válida."]); exit; }
            $check_subcategory = $conn->prepare("SELECT id FROM sub_categories WHERE id = ?"); $check_subcategory->bind_param("i", $sub_categories_id); $check_subcategory->execute(); $check_subcategory->store_result(); if ($check_subcategory->num_rows === 0) { echo json_encode(["success" => false, "message" => "La sub-categoría seleccionada no es válida."]); exit; }
            if (!isset($_SESSION["user_id"])) { echo json_encode(["success" => false, "message" => "Debe estar logueado para publicar un trabajo."]); exit; }
            $employer_id = $_SESSION["user_id"]; $stmt = $conn->prepare("INSERT INTO tasks (employer_id, title, description, category_id, sub_categories_id, location_id, address_text, duration_hours, status, created_at, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published', NOW(), NOW())"); if (!$stmt) { echo json_encode(["success" => false, "message" => "Error en la preparación de la consulta: " . $conn->error]); exit; }
            $stmt->bind_param("issiiisd", $employer_id, $title, $description, $category_id, $sub_categories_id, $location_id, $address_text, $duration_hours);
            if ($stmt->execute()) { $task_id = $conn->insert_id; echo json_encode(["success" => true, "message" => "¡Trabajo publicado exitosamente!", "redirect" => "../jobs.php"]); }
            else { echo json_encode(["success" => false, "message" => "Error al publicar el trabajo: " . $stmt->error]); }
            $stmt->close(); $check_category->close(); $check_location->close(); $check_subcategory->close(); $conn->close();
        } else { echo json_encode(["success" => false, "message" => "Acceso no autorizado."]); }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Acción desconocida: " . $accion]);
}
?>