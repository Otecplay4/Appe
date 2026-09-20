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
                    div.innerHTML = "Usuario no encontrado";
            }
        })
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
                location.href = "./inicio.html"; 
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