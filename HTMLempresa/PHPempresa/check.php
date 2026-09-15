<?php

session_start();
if(isset($_SESSION["correo"])){
    echo json_encode(["exito" => true ]);
}else{
    echo json_encode(["exito" => false ]);
}