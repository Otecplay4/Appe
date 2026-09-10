<?php

include "conexion.php";
$correo = $_POST['correo'];
$contraseña = $_POST['contraseña'];



$sen=$conexion->prepare("SELECT * FROM clientes WHERE correo = ? AND contraseña =  ?");
$res=$sen->execute([$correo, $contraseña]);


$usuario = $sen->fetch(PDO::FETCH_ASSOC);

// Retornar respuesta
if($usuario){
    session_start();
    $_SESSION["correo"] = $usuario["correo"];
    $_SESSION["contraseña"] = $usuario["contraseña"];
    
    echo json_encode([
        "exito" => true,
        "mensaje" => "Login correcto",
    ]);
}else{
    echo json_encode([
        "exito" => false,
        "mensaje" => "Correo o contraseña incorrectos",
    ]);
}
?>