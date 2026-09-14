
const eliminarCliente = document.getElementById("eliminarCliente")
const actualizarCliente = document.getElementById("actualizarCliente")
const mostrarCliente = document.getElementById("mostrarCliente")
const inputNombre = document.getElementById("Nombre")
const inputCorreo = document.getElementById("Correo")
const sugerencialista = document.getElementById ("sugerencialista")

document.addEventListener("DOMContentLoaded",(e)=> {
     fetch("./PHPempresa/check.php")
        .then(res => res.json())
        .then(datos => {
            console.log(datos)
            if(datos.exito == false){
                  location.href = "./interfaz2.html";
            }
        })

})


if(eliminarCliente){
    eliminarCliente.addEventListener("submit", (e)=> {
        e.preventDefault()
        let form= new FormData(eliminarCliente)

        fetch("./PHPempresa/eliminar.php", {
            method: "POST",
            body: form
        })
            });
    };










if(actualizarCliente){
    actualizarCliente.addEventListener("submit", (e)=> {
        e.preventDefault()
        let form= new FormData(actualizarCliente)

        fetch("./PHPempresa/actualizar.php", {
            method: "POST",
            body: form
            .then(res => res.json())
        .then(datos => {
            console.log(datos)
            if(datos.exito == false){
                  location.href = "./actualizar2º.html";
            }
        })

        })
            });
    };

    if(mostrarCliente){
    eliminarCliente.addEventListener("submit", (e)=> {
        e.preventDefault()
        let form= new FormData(mostrarCliente)

        fetch("./PHPempresa/mostrarcliente.php", {
            method: "POST",
            body: form
        })
            });
    };

    let timeoutId;

    inputNombre.addEventListener("input", function(){
        const query = this.value.trim();
        sugerencialista.innerHTML = "";
        if (query === "") return;
        clearTimeout (timeoutId);
        timeoutId = setTimeout(() => {
            buscaUsuariosEnBD(query);
        }, 300);

    });

    async function buscaUsuariosEnBD(query) {
        try{
            const response =await fetch(`./PHPempresa/buscar.php?q=${encodeURIComponent(query)}`);

            if(!response.ok){
                throw new Error ("Error en la respuesta del servidor");
            }

        

        const usuarios=await response.json();
            mostrarSugerencias(usuarios);
    }catch(error){
        console.error("Error al obtener los usuarios:", error);
    }
    }

    function mostrarSugerencias (usuarios){
        sugerencialista.innerHTML="";

        if(usuarios.length === 0)return;

        usuarios.forEach (user => {
            const correoUsuario = user.correo;
            const Li= document.createElement("Li");
            Li.className = "suggestion-item";
            Li.innerHTML=`
            <span class="user-name">${user.nombre}<span>
            <span class="user-email">${correoUsuario}<span>`;
            Li.addEventListener("click", () => {
                inputNombre.value = user.nombre;
                if (inputCorreo) {
                    inputCorreo.value = correoUsuario;
                }
                sugerencialista.innerHTML = "";
            });
            sugerencialista.appendChild(Li);
        });
    }
    document.addEventListener("click", function(e) {
        if (!e.target.closest("#eliminarCliente")){
            sugerencialista.innerHTML = "";
        }

    })
