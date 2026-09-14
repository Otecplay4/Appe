<?php

//$dato= filter_var($_POST["dato"]);
$dato = $_POST["dato"];
if(filter_var($dato, FILTER_VALIDATE_EMAIL)){
    echo "Correo:". $dato;
}else{
    echo "Formato incorrecto";
}

?>