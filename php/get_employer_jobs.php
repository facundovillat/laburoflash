<?php
header('Content-Type: application/json');

include "session_config.php";
include "connection.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Debe estar logueado."]);
    exit;
}

$employer_id = $_SESSION["user_id"];

$sql = "SELECT t.*, tc.name as category_name, sc.name as subcategory_name, l.city, l.province, COUNT(DISTINCT tr.id) as total_responses, COUNT(DISTINCT CASE WHEN tr.status = 'requested' THEN tr.id END) as pending_responses, ta.status as assignment_status, ta.completed_at, CASE WHEN ta.status = 'pending_completion' THEN 1 ELSE 0 END as has_pending_completion FROM tasks t LEFT JOIN task_categories tc ON t.category_id = tc.id LEFT JOIN sub_categories sc ON t.sub_categories_id = sc.id LEFT JOIN locations l ON t.location_id = l.id LEFT JOIN task_responses tr ON t.id = tr.task_id LEFT JOIN task_assignments ta ON t.id = ta.task_id WHERE t.employer_id = ? GROUP BY t.id, ta.status, ta.completed_at ORDER BY t.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employer_id);
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
