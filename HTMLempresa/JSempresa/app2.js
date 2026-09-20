const div = document.getElementById("div")
const crearEntrenador = document.getElementById("crearEntrenador")
const iniciarEntrenador = document.getElementById("iniciarEntrenador")





if(crearEntrenador){
    crearEntrenador.addEventListener("submit", (e)=> {
        e.preventDefault()
        let form = new FormData(crearEntrenador)
        fetch("./PHPempresa/crearentrenador.php", {
            "method": "post",
            "body": form
        })
        .then(res => res.json())
        .then(datos => {
            console.log(datos)
            if(datos.exito){
                    location.href = "./interfaz2.html";
                }else{
                    div.innerHTML = "Usuario no encontrado";
            }
        })
})
}


if(iniciarEntrenador){
    iniciarEntrenador.addEventListener("submit", (e)=> {
        e.preventDefault()
        let form = new FormData(iniciarEntrenador)
        fetch("./PHPempresa/iniciarentrenador.php", {
            "method": "post",
            "body": form
        })
        .then(res => res.json())
        .then(datos => {
            
            

            if(datos.exito){
                    location.href = "./panel.html";
                }else{
                    div.innerHTML = datos.mensaje;
            }
            })
            .catch(err => {
                console.error(err);
                div.innerHTML = "Error de conexion";
            });
    });
}

