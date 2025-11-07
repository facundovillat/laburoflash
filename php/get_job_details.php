<?php
header('Content-Type: application/json');

include "session_config.php";
include "connection.php";

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["task_id"])) {
    
    $task_id = (int)$_GET["task_id"];
    
    if ($task_id <= 0) {
        echo json_encode(["success" => false, "message" => "ID de trabajo inválido."]);
        exit;
    }
    
    $sql = "SELECT t.*, tc.name as category_name, sc.name as subcategory_name, l.city, l.province, u.name as employer_name, u.last_name as employer_last_name, u.email as employer_email, u.phone_number as employer_phone FROM tasks t LEFT JOIN task_categories tc ON t.category_id = tc.id LEFT JOIN sub_categories sc ON t.sub_categories_id = sc.id LEFT JOIN locations l ON t.location_id = l.id LEFT JOIN users u ON t.employer_id = u.id WHERE t.id = ? AND t.status = 'published'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "El trabajo no existe o no está disponible."]);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $task = $result->fetch_assoc();
    
    $has_applied = false;
    if (isset($_SESSION["user_id"])) {
        $check_response = $conn->prepare("SELECT id, status FROM task_responses WHERE task_id = ? AND worker_id = ?");
        $worker_id = $_SESSION["user_id"];
        $check_response->bind_param("ii", $task_id, $worker_id);
        $check_response->execute();
        $response_result = $check_response->get_result();
        $has_applied = $response_result->num_rows > 0;
        $check_response->close();
    }
    
    echo json_encode(["success" => true, "data" => $task, "has_applied" => $has_applied]);
    
    $stmt->close();
    $conn->close();
    
} else {
    echo json_encode(["success" => false, "message" => "Parámetros inválidos."]);
}
?>
