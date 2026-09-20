<?php
include "conexion2.php";
$correo = $_POST['correo'];
$contraseña = $_POST['contraseña'];



// $sen=$conexion->prepare("SELECT * FROM entrenador WHERE correo = ? AND contraseña =  ?");
$sen=$conexion->prepare("SELECT * FROM entrenador WHERE correo = ?");
$res=$sen->execute([$correo]);

$usuario = $sen->fetch(PDO::FETCH_ASSOC);

// Retornar respuesta
if($usuario){
    //VErificacion de contraseña encriptada
    if(password_verify($contraseña, $usuario['contraseña'])){
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
        "mensaje" => "Contraseña incorrectos",
    ]);
    }

}else{
    echo json_encode([
        "exito" => false,
        "mensaje" => "Correo incorrectos",
    ]);
}
?>