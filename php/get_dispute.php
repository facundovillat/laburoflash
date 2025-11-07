<?php
header('Content-Type: application/json');

include "session_config.php";
include "connection.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Debe estar logueado."]);
    exit;
}

$task_id = isset($_GET["task_id"]) ? (int)$_GET["task_id"] : 0;
$user_id = $_SESSION["user_id"];

if ($task_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]);
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

$sql = "SELECT cd.*, u_worker.name as worker_name, u_worker.last_name as worker_last_name, u_employer.name as employer_name, u_employer.last_name as employer_last_name FROM completion_disputes cd LEFT JOIN users u_worker ON cd.worker_id = u_worker.id LEFT JOIN users u_employer ON cd.employer_id = u_employer.id WHERE cd.task_id = ? AND cd.status = 'pending' ORDER BY cd.created_at DESC LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "No hay disputa activa para este trabajo."]);
    $check_access->close();
    $stmt->close();
    $conn->close();
    exit;
}

$dispute = $result->fetch_assoc();

echo json_encode(["success" => true, "data" => $dispute]);

$check_access->close();
$stmt->close();
$conn->close();
?>
