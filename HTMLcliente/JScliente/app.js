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
                    location.href = "./interfaz.html";
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
            
            

            if(datos.exito){
                    location.href = "./interfaz.html";
                }else{
                    div.innerHTML = "Usuario no encontrado";
            }
            })
            .catch(err => {
                console.error(err);
                div.innerHTML = "Error de conexion";
            });
    });
}
