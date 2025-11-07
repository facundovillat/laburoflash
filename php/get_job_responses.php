<?php
header('Content-Type: application/json');

include "session_config.php";
include "connection.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Debe estar logueado."]);
    exit;
}

$task_id = isset($_GET["task_id"]) ? (int)$_GET["task_id"] : 0;
$employer_id = $_SESSION["user_id"];

if ($task_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]);
    exit;
}

$check_task = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND employer_id = ?");
$check_task->bind_param("ii", $task_id, $employer_id);
$check_task->execute();
$task_result = $check_task->get_result();

if ($task_result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "No tienes permiso para ver este trabajo."]);
    $check_task->close();
    $conn->close();
    exit;
}

$sql = "SELECT tr.*, u.name, u.last_name, u.email, u.phone_number, ta.status as assignment_status, ta.assigned_at, ta.completed_at, t.employer_id FROM task_responses tr INNER JOIN users u ON tr.worker_id = u.id INNER JOIN tasks t ON tr.task_id = t.id LEFT JOIN task_assignments ta ON tr.task_id = ta.task_id AND tr.worker_id = ta.worker_id WHERE tr.task_id = ? ORDER BY tr.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();

$responses = [];
while ($row = $result->fetch_assoc()) {
    $responses[] = $row;
}

echo json_encode(["success" => true, "data" => $responses]);

$check_task->close();
$stmt->close();
$conn->close();
?>
