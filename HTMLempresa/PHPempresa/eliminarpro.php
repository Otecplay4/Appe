<?php
include "conexion2.php";
$ID=$_GET["id"];

if (unlink("./img/".$ID.".jpg")){
echo json_encode(["exito"=>true]);
}
else{

    echo json_encode(["exito"=>false]);

}

?>
