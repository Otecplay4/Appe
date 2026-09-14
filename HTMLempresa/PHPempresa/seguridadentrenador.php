<?php
include "conexion2.php";

$nombre = $_POST["nombre"];
$correo = $_POST["correo"];
if(filter_var($correo, FILTER_VALIDATE_EMAIL)){
    echo "Correo:". $correo;
}else{
    echo "Formato incorrecto";
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $nombre)) {
        echo "El nombre de usuario no es válido. Solo se permiten letras, números y guiones bajos.";
    } 

?>