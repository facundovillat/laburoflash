<?php
include "config_sesion.php";
include "conexion.php";
include __DIR__ . '/lib/utilidades.php';
include __DIR__ . '/lib/autenticacion.php';

$isAjax = es_ajax();
if ($isAjax) {
    header('Content-Type: application/json');
}

$accion = $_GET['accion'] ?? ($_POST['accion'] ?? 'iniciar_sesion');

switch ($accion) {
    case 'iniciar_sesion':
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $res = auth_login($conn, $_POST["email"] ?? "", $_POST["password"] ?? "");
            if (!$isAjax && $res["success"]) {
                header('Location: ' . $res["redirect"]);
                exit;
            }
            json_responder($res);
            $conn->close();
        } else {
            json_responder(["success" => false, "message" => "Método no permitido."]);    
        }
        break;

    case 'registrar':
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $res = auth_register(
                $conn,
                $_POST["name"] ?? "",
                $_POST["last_name"] ?? "",
                $_POST["email"] ?? "",
                $_POST["password"] ?? "",
                $_POST["v_password"] ?? "",
                $_POST["phone_number"] ?? ""
            );
            if (!$isAjax && $res["success"]) {
                header('Location: ' . $res["redirect"]);
                exit;
            }
            json_responder($res);
            $conn->close();
        } else {
            json_responder(["success" => false, "message" => "Método no permitido."]);    
        }
        break;

    case 'cerrar_sesion':
        auth_logout();
        if ($isAjax) {
            json_responder(["success" => true, "message" => "Sesión cerrada"]);
        } else {
            header("Location: ../login.html");
            exit;
        }
        break;

    case 'verificar_sesion':
        $logged = auth_is_logged();
        json_responder(["success" => $logged, "message" => $logged ? "Sesión activa" : "No has iniciado sesión"]);
        break;

    default:
        json_responder(["success" => false, "message" => "Acción inválida: " . $accion]);
}
?>