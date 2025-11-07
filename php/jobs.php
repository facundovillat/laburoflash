<?php
header('Content-Type: application/json');

include "session_config.php";
include "connection.php";

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $required_fields = ['title', 'description', 'category_id', 'location_id', 'address_text', 'duration_hours', 'sub_categories_id', 'terms_conditions'];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            if ($isAjax) {
                echo json_encode(["success" => false, "message" => "El campo " . $field . " es requerido."]);
                exit;
            } else {
                die("Error: El campo " . $field . " es requerido.");
            }
        }
    }
    
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $category_id = (int)$_POST["category_id"];
    $location_id = (int)$_POST["location_id"];
    $address_text = trim($_POST["address_text"]);
    $duration_hours = (float)$_POST["duration_hours"];
    $sub_categories_id = (int)$_POST["sub_categories_id"];
    $terms_conditions = $_POST["terms_conditions"];
    
    if (strlen($title) < 5 || strlen($title) > 120) {
        if ($isAjax) {
            echo json_encode(["success" => false, "message" => "El título debe tener entre 5 y 120 caracteres."]);
            exit;
        } else {
            die("Error: El título debe tener entre 5 y 120 caracteres.");
        }
    }
    
    if (strlen($description) < 10 || strlen($description) > 1000) {
        if ($isAjax) {
            echo json_encode(["success" => false, "message" => "La descripción debe tener entre 10 y 1000 caracteres."]);
            exit;
        } else {
            die("Error: La descripción debe tener entre 10 y 1000 caracteres.");
        }
    }
    
    if ($duration_hours < 0.5 || $duration_hours > 24) {
        if ($isAjax) {
            echo json_encode(["success" => false, "message" => "La duración debe estar entre 0.5 y 24 horas."]);
            exit;
        } else {
            die("Error: La duración debe estar entre 0.5 y 24 horas.");
        }
    }
    
    if ($terms_conditions !== "on") {
        if ($isAjax) {
            echo json_encode(["success" => false, "message" => "Debe aceptar los términos y condiciones."]);
            exit;
        } else {
            die("Error: Debe aceptar los términos y condiciones.");
        }
    }
    
    $check_category = $conn->prepare("SELECT id FROM task_categories WHERE id = ?");
    $check_category->bind_param("i", $category_id);
    $check_category->execute();
    $check_category->store_result();
    
    if ($check_category->num_rows === 0) {
        if ($isAjax) {
            echo json_encode(["success" => false, "message" => "La categoría seleccionada no es válida."]);
            exit;
        } else {
            die("Error: La categoría seleccionada no es válida.");
        }
    }
    
    $check_location = $conn->prepare("SELECT id FROM locations WHERE id = ?");
    $check_location->bind_param("i", $location_id);
    $check_location->execute();
    $check_location->store_result();
    
    if ($check_location->num_rows === 0) {
        if ($isAjax) {
            echo json_encode(["success" => false, "message" => "La ubicación seleccionada no es válida."]);
            exit;
        } else {
            die("Error: La ubicación seleccionada no es válida.");
        }
    }
    
    $check_subcategory = $conn->prepare("SELECT id FROM sub_categories WHERE id = ?");
    $check_subcategory->bind_param("i", $sub_categories_id);
    $check_subcategory->execute();
    $check_subcategory->store_result();
    
    if ($check_subcategory->num_rows === 0) {
        if ($isAjax) {
            echo json_encode(["success" => false, "message" => "La sub-categoría seleccionada no es válida."]);
            exit;
        } else {
            die("Error: La sub-categoría seleccionada no es válida.");
        }
    }
    
    if (!isset($_SESSION["user_id"])) {
        if ($isAjax) {
            echo json_encode(["success" => false, "message" => "Debe estar logueado para publicar un trabajo."]);
            exit;
        } else {
            die("Error: Debe estar logueado para publicar un trabajo.");
        }
    }
    $employer_id = $_SESSION["user_id"];
    
    $stmt = $conn->prepare("INSERT INTO tasks (employer_id, title, description, category_id, sub_categories_id, location_id, address_text, duration_hours, status, created_at, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published', NOW(), NOW())");
    
    if (!$stmt) {
        if ($isAjax) {
            echo json_encode(["success" => false, "message" => "Error en la preparación de la consulta: " . $conn->error]);
            exit;
        } else {
            die("Error en la preparación de la consulta: " . $conn->error);
        }
    }
    
    $stmt->bind_param("issiiisd", $employer_id, $title, $description, $category_id, $sub_categories_id, $location_id, $address_text, $duration_hours);
    
    if ($stmt->execute()) {
        $task_id = $conn->insert_id;
        
        if ($isAjax) {
            echo json_encode(["success" => true, "message" => "¡Trabajo publicado exitosamente!", "redirect" => "../jobs.php"]);
        } else {
            echo "<div style='text-align: center; padding: 20px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px;'>";
            echo "<h2 style='color: #155724;'>¡Trabajo publicado exitosamente!</h2>";
            echo "<p style='color: #155724;'>Tu trabajo ha sido publicado con el ID: " . $task_id . "</p>";
            echo "<p style='color: #155724;'>Título: " . htmlspecialchars($title) . "</p>";
            echo "<p style='color: #155724;'>Duración: " . $duration_hours . " horas</p>";
            echo "<a href='../jobs.php' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Ver todos los trabajos</a>";
            echo "</div>";
        }
    } else {
        if ($isAjax) {
            echo json_encode(["success" => false, "message" => "Error al publicar el trabajo: " . $stmt->error]);
        } else {
            echo "<div style='text-align: center; padding: 20px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
            echo "<h2 style='color: #721c24;'>Error al publicar el trabajo</h2>";
            echo "<p style='color: #721c24;'>Error: " . $stmt->error . "</p>";
            echo "<a href='form-jobs.html' style='display: inline-block; padding: 10px 20px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Intentar nuevamente</a>";
            echo "</div>";
        }
    }
    
    $stmt->close();
    $check_category->close();
    $check_location->close();
    $check_subcategory->close();
    $conn->close();
    
} else {
    if ($isAjax) {
        echo json_encode(["success" => false, "message" => "Acceso no autorizado."]);
    } else {
        echo "<div style='text-align: center; padding: 20px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
        echo "<h2 style='color: #721c24;'>Acceso no autorizado</h2>";
        echo "<p style='color: #721c24;'>Este archivo solo puede ser accedido mediante el formulario.</p>";
        echo "<a href='form-jobs.html' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Volver al formulario</a>";
        echo "</div>";
    }
}
?>
