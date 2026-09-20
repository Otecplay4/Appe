<?php
include "conexion.php";
$correo = $_POST['correo'];
$contraseña = $_POST['contraseña'];

// 1. Buscamos al usuario SOLO por su correo
$sen = $conexion->prepare("SELECT * FROM cliente WHERE correo = ?");
$sen->execute([$correo]);
$usuario = $sen->fetch(PDO::FETCH_ASSOC);

// 2. Verificamos si el usuario existe Y si la contraseña coincide con el hash
if($usuario && password_verify($contraseña, $usuario['contraseña'])){
    session_start();
    // Guardamos los datos necesarios en la sesión
    $_SESSION["id"] = $usuario["id"]; 
    $_SESSION["correo"] = $usuario["correo"];

    
    echo json_encode([
        "exito" => true,
        "mensaje" => "Login correcto",
        "id" => $usuario["id"]
    ]);
} else {
    // Mensaje genérico para no dar pistas a atacantes
    echo json_encode([
        "exito" => false,
        "mensaje" => "Correo o contraseña incorrectos",
    ]);
}
?>