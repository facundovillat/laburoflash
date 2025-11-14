<?php
$host = "localhost";  
$usuario = "root";    
$contrasena = "";     
$bd = "LaburoFlash"; 

$conn = @new mysqli($host, $usuario, $contrasena, $bd);

if ($conn->connect_error) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos."]);
        exit;
    }
    die("Conexión fallida: " . $conn->connect_error);
}

?>
