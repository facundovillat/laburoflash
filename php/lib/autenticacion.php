<?php
include_once __DIR__ . '/../config_sesion.php';

function auth_is_logged() {
    return isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true;
}

function auth_require() {
    if (!auth_is_logged()) {
        header("Location: ../login.html");
        exit;
    }
    if (isset($_SESSION["last_activity"]) && (time() - $_SESSION["last_activity"] > 1800)) {
        session_unset();
        session_destroy();
        header("Location: ../login.html");
        exit;
    }
    $_SESSION["last_activity"] = time();
}

function auth_login($conn, $email, $password) {
    $email = trim($email);
    $password = trim($password);
    if (empty($email) || empty($password)) {
        return ["success" => false, "message" => "Por favor completá todos los campos."];
    }
    $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows !== 1) {
        $stmt->close();
        return ["success" => false, "message" => "Correo no registrado."];
    }
    $user = $result->fetch_assoc();
    if (!password_verify($password, $user["password"])) {
        $stmt->close();
        return ["success" => false, "message" => "Contraseña incorrecta."];
    }
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["user_name"] = $user["name"];
    $_SESSION["user_email"] = $user["email"];
    $_SESSION["logged_in"] = true;
    $_SESSION["last_activity"] = time();
    session_write_close();
    $stmt->close();
    return ["success" => true, "message" => "¡Inicio de sesión exitoso!", "redirect" => "../index.php"];
}

function auth_register($conn, $name, $last_name, $email, $password, $v_password, $phone_number) {
    $name = trim($name);
    $last_name = trim($last_name);
    $email = trim($email);
    $password = trim($password);
    $v_password = trim($v_password);
    $phone_number = trim($phone_number);
    if (empty($name) || empty($last_name) || empty($email) || empty($password) || empty($v_password) || empty($phone_number)) {
        return ["success" => false, "message" => "Por favor completá todos los campos."];
    }
    if ($password !== $v_password) {
        return ["success" => false, "message" => "Las contraseñas no coinciden."];
    }
    if (!preg_match("/^[\w\-\.]+@([\w\-]+\.)+[a-zA-Z]{2,}$/", $email)) {
        return ["success" => false, "message" => "El correo electrónico no es válido."];
    }
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        return ["success" => false, "message" => "Este correo ya está registrado."];
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
        $stmt->close();
        return ["success" => true, "message" => "¡Registro exitoso! Redirigiendo...", "redirect" => "../index.php"];
    }
    $err = $stmt->error;
    $stmt->close();
    return ["success" => false, "message" => "Error al registrar: " . $err];
}

function auth_logout() {
    include_once __DIR__ . '/../config_sesion.php';
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
}
?>