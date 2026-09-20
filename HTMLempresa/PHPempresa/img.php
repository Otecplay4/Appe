<?php
include "conexion2.php";
$nombre = $_POST ["nombre"];

$sen=$conexion->prepare("INSERT INTO imagenes (nombre) values (?)");
$res=$sen->execute([$nombre]);



$id=$conexion->lastInsertId();

$from = $_FILES ["archivo"] ["tmp_name"];
$to = "img/$id.jpg";
if(move_uploaded_file ($from , $to )){

echo json_encode(["exito"=>true]);

}else{
 
echo json_encode(["exito"=>false]);
}



?>