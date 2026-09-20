<?php
include "conexion2.php";

$sen=$conexion->prepare("SELECT * FROM imagenes");

$sen->execute();

$r=$sen->fetchALL(PDO::FETCH_ASSOC);

echo json_encode($r)

?>