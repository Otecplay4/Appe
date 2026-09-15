<?php
include "conexion2.php";
$sen = $conexion->prepare("SELECT *            FROM clientes");
    $sen->execute();
    
    $personas=$sen->fetchAll(PDO::FETCH_ASSOC);
    foreach($personas as $persona){
        echo "<h2> Nombre: $persona[nombre] </h2>";
        echo "<h2> ID: $persona[correo] </h2>";
        echo "<h2> ID: $persona[id] </h2>";
        echo "<a href='mostrarcliente.php?id=$persona[id]&nombre=$persona[nombre]&correo=$persona[correo]'>Actualizar<br></a>";
    }
?>