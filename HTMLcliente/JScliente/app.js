const div = document.getElementById("div")
const crearCuenta = document.getElementById("crearCuenta")
const iniciarSesion = document.getElementById("iniciarSesion")



if(crearCuenta){
    crearCuenta.addEventListener("submit", (e)=> {
        e.preventDefault()
        let form = new FormData(crearCuenta)
        fetch("./PHPcliente/registro.php", {
            "method": "post",
            "body": form
        })
        .then(res => res.json())
        .then(datos => {
            console.log(datos)
            if(datos.exito){
                    location.href = "./Principio.html";
                }else{
                    // Hallazgo 26: se muestra el mensaje real devuelto por el backend.
                    div.innerHTML = datos.mensaje || "No se pudo crear la cuenta";
            }
        })
        .catch(err => {
            console.error(err);
            div.innerHTML = "Error de conexion";
        });
})
}


if(iniciarSesion){
    iniciarSesion.addEventListener("submit", (e)=> {
        e.preventDefault()
        let form = new FormData(iniciarSesion)
        fetch("./PHPcliente/iniciarSesion.php", {
            "method": "post",
            "body": form
        })
        .then(res => res.json())
        .then(datos => {
            console.log(datos)
            if(datos.exito){
                // Usamos 'datos.id' y lo guardamos ANTES de redirigir
                sessionStorage.setItem("id_cliente", datos.id);
                // Hallazgo 28: el archivo real se llama "Inicio.html" (con mayúscula),
                // no "inicio.html".
                location.href = "./Inicio.html";
            } else {
                div.innerHTML = datos.mensaje;
            }
        })
        .catch(err => {
            console.error(err);
            div.innerHTML = "Error de conexion";
        });
    });
}
