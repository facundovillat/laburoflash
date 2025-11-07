<?php
include "session_config.php";
include "connection.php";

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json');
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"] ?? "";
    $last_name = $_POST["last_name"] ?? "";
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";
    $v_password = $_POST["v_password"] ?? "";
    $phone_number = $_POST["phone_number"] ?? "";

    if (empty($name) || empty($last_name) || empty($email) || empty($password) || empty($v_password) || empty($phone_number)) {
        echo json_encode(["success" => false, "message" => "Por favor completa todos los campos."]);
        exit;
    }

    if ($password !== $v_password) {
        echo json_encode(["success" => false, "message" => "Las contraseñas no coinciden."]);
        exit;
    }

    if (!preg_match("/^[\w\-\.]+@([\w\-]+\.)+[a-zA-Z]{2,}$/", $email)) {
        echo json_encode(["success" => false, "message" => "El correo electrónico no es válido."]);
        exit;
    }

    $allowed_domains = ['com', 'net', 'org', 'edu', 'gov', 'ar', 'es'];
    $tld = strtolower(pathinfo($email, PATHINFO_EXTENSION));    

    if (!in_array($tld, $allowed_domains)) {
        echo json_encode(["success" => false, "message" => "Dominio de correo no permitido."]);
        exit;
    }

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Este correo ya está registrado."]);
        $check->close();
        $conn->close();
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, last_name, email, password, phone_number) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $last_name, $email, $hashed_password, $phone_number);

    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;
        
        $_SESSION["user_id"] = $new_user_id;
        $_SESSION["user_name"] = $name;
        $_SESSION["user_email"] = $email;
        $_SESSION["logged_in"] = true;
        $_SESSION["last_activity"] = time();
        session_write_close();
        
        if (!$isAjax) {
            header('Location: ../index.php');
            exit;
        }
        
        echo json_encode([
            "success" => true,
            "message" => "¡Registro exitoso! Redirigiendo...",
            "redirect" => "../index.php"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar: " . $stmt->error]);
    }

    $stmt->close();
    $check->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
}
?>
