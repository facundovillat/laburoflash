<?php
header('Content-Type: application/json');

include "session_config.php";
include "connection.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Debe estar logueado."]);
    exit;
}

$worker_id = $_SESSION["user_id"];

$sql = "SELECT tr.*, t.id as task_id, t.title, t.description, t.duration_hours, t.status as task_status, tc.name as category_name, sc.name as subcategory_name, l.city, l.province, u.name as employer_name, u.last_name as employer_last_name, u.email as employer_email, u.phone_number as employer_phone, ta.status as assignment_status, ta.assigned_at, ta.completed_at FROM task_responses tr INNER JOIN tasks t ON tr.task_id = t.id LEFT JOIN task_categories tc ON t.category_id = tc.id LEFT JOIN sub_categories sc ON t.sub_categories_id = sc.id LEFT JOIN locations l ON t.location_id = l.id LEFT JOIN users u ON t.employer_id = u.id LEFT JOIN task_assignments ta ON tr.task_id = ta.task_id AND tr.worker_id = ta.worker_id WHERE tr.worker_id = ? ORDER BY tr.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $worker_id);
$stmt->execute();
$result = $stmt->get_result();

$jobs = [];
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

echo json_encode(["success" => true, "data" => $jobs]);

$stmt->close();
$conn->close();
?>
