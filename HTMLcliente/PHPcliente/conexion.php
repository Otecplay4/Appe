<?php

// Hallazgo 9: ya no se usa la cuenta "root" sin contraseña.
// Las credenciales se leen de variables de entorno y nunca se versionan en el código.
// En el entorno de laboratorio, si no se definen variables de entorno, se usa un
// usuario de aplicación con privilegios mínimos (no root) como valor por defecto.
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'gimnasio';
$dbUser = getenv('DB_USER') ?: 'app_gimnasio';
$dbPass = getenv('DB_PASS') ?: '';

$conexion = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass
);
$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conexion->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

?>
