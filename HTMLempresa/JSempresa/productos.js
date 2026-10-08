// Hallazgo 18: antes este archivo repetía el mismo bloque de código cuatro
// veces (uno por categoría) y declaraba "let form" varias veces en el mismo
// ámbito, lo cual es un SyntaxError que impedía ejecutar el archivo completo
// ("node --check" lo confirmó: Identifier 'form' has already been declared").
// Hallazgo 29: además duplicaba funciones globales y usaba "contenedor" sin
// una referencia explícita al DOM. Ahora hay un único flujo de inicialización,
// reutilizado por los tres formularios (Consumibles, Atuendos, Accesorios).

const contenedor = document.getElementById("contenedor");

const formularios = [
    document.getElementById("formularioConsumibles"),
    document.getElementById("formularioAtuendos"),
    document.getElementById("formularioAccesorios"),
].filter(Boolean);

formularios.forEach(form => {
    form.addEventListener("submit", (e) => {
        e.preventDefault();
        const datos = new FormData(form);

        fetch("./PHPempresa/img.php", {
            method: "POST",
            body: datos
        })
            .then(r => r.json())
            .then(respuesta => {
                console.log(respuesta);
                if (respuesta.exito) {
                    form.reset();
                    actualizar();
                } else {
                    alert(respuesta.error || "No se pudo subir el producto");
                }
            })
            .catch(err => {
                console.error("Error al subir el producto:", err);
                alert("Error de conexión al subir el producto");
            });
    });
});

function actualizar() {
    fetch("./PHPempresa/mostrarprod.php")
        .then(r => r.json())
        .then(datos => {
            if (!contenedor) {
                console.error("No se encontró el elemento #contenedor en esta página.");
                return;
            }
            contenedor.innerHTML = "";

            if (datos.error) {
                console.error(datos.error);
                return;
            }

            datos.forEach(pj => pintarProducto(pj));
        })
        .catch(err => console.error("Error al listar productos:", err));
}

// Se construyen los nodos del DOM y se asigna el texto con textContent
// (mismo criterio que en los hallazgos 5 y 6) en vez de interpolar nombres
// de producto controlados por el usuario dentro de innerHTML.
function pintarProducto(pj) {
    const tarjeta = document.createElement("div");
    tarjeta.className = "producto";

    const titulo = document.createElement("h2");
    titulo.textContent = pj.nombre;

    const imagen = document.createElement("img");
    imagen.src = `./img/${encodeURIComponent(pj.id)}.jpg`;
    imagen.alt = pj.nombre;

    const btnEliminar = document.createElement("button");
    btnEliminar.textContent = "X";
    btnEliminar.addEventListener("click", () => eliminar(pj.id));

    const btnActualizar = document.createElement("button");
    btnActualizar.textContent = "Actualizar";
    btnActualizar.addEventListener("click", () => actualizarNombre(pj.id, pj.nombre));

    tarjeta.appendChild(titulo);
    tarjeta.appendChild(imagen);
    tarjeta.appendChild(btnEliminar);
    tarjeta.appendChild(btnActualizar);

    contenedor.appendChild(tarjeta);
}

actualizar();

function eliminar(idu) {
    // Hallazgo 15: el borrado ya no viaja por GET.
    const datos = new URLSearchParams();
    datos.set("id", idu);

    fetch("./PHPempresa/eliminarpro.php", {
        method: "POST",
        body: datos
    })
        .then(r => r.json())
        .then(respuesta => {
            console.log(respuesta);
            actualizar();
        })
        .catch(err => console.error("Error al eliminar el producto:", err));
}

// Hallazgo 28: se corrige la ruta llamada ("actualizprod.php" no existía; el
// archivo real es "actualizarprod.php") y se completa su uso real: actualizar
// el nombre de un producto existente.
function actualizarNombre(idu, nombreActual) {
    const nuevoNombre = prompt("Nuevo nombre del producto:", nombreActual || "");
    if (nuevoNombre === null || nuevoNombre.trim() === "") {
        return;
    }

    const datos = new URLSearchParams();
    datos.set("id", idu);
    datos.set("nombre", nuevoNombre.trim());

    fetch("./PHPempresa/actualizarprod.php", {
        method: "POST",
        body: datos
    })
        .then(r => r.json())
        .then(respuesta => {
            console.log(respuesta);
            if (!respuesta.exito) {
                alert(respuesta.error || "No se pudo actualizar el producto");
            }
            actualizar();
        })
        .catch(err => console.error("Error al actualizar el producto:", err));
}
