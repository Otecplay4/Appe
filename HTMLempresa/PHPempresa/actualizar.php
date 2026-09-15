<?php
include "conexion2.php";
$id = $_GET['id'];
$plan = $_GET['plan'];
$nombre = $_GET['nombre'];
$rutina = $_GET['rutina'];
?>
<form action="editar.php" method="POST"> 
    <label> Rutina </label>
       <input type="text" name="nombre" value="<?= $rutina ?>">
    <label> Plan </label>
    <input type="text" name="correo" value="<?= $plan ?>">
    <label> Nombre </label>
    <input type="text" name="contraseña" value="<?= $nombre ?>">

    <button type="submit">Actualizar</button>   
<?php

$sen=$conexion->prepare("UPDATE clientes SET plan = ?, nombre = ?, WHERE id = ?");
$sen->execute([$id, $plan, $nombre, $rutina]);

$id=$sen->fetch();
?>

$sen=$conexion->prepare("UPDATE crud SET nombre = ?, apellido = ? WHERE id = ?");
