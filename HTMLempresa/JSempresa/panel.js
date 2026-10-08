// 1. Apenas carga la página HTML, ejecutamos la función para mostrar los clientes
document.addEventListener("DOMContentLoaded", function () {
    cargarClientes();
});

// Función para pedir los datos a PHP y armar las filas de la tabla
function cargarClientes() {
    fetch("./PHPempresa/mostrarcliente.php")
        .then(res => res.json())
        .then(datos => {
            const cuerpoTabla = document.getElementById("cuerpoTabla");
            if (!cuerpoTabla) {
                console.error("No se encontró el elemento #cuerpoTabla en esta página.");
                return;
            }
            cuerpoTabla.innerHTML = ""; // Limpiamos la tabla antes de recargar

            if (datos.error) {
                console.error(datos.error);
                return;
            }

            // Hallazgo 6 (XSS almacenado): se construyen los nodos del DOM y se
            // asigna el texto con textContent, en vez de interpolar en innerHTML.
            datos.forEach(cliente => {
                const fila = document.createElement("tr");

                const celdas = [cliente.id, cliente.nombre, cliente.apellido, cliente.correo];
                celdas.forEach(valor => {
                    const celda = document.createElement("td");
                    celda.textContent = valor;
                    fila.appendChild(celda);
                });

                cuerpoTabla.appendChild(fila);
            });
        })
        .catch(err => {
            console.error("Error al cargar clientes:", err);
        });
}

// 2. ELIMINAR CLIENTE
const eliminarCliente = document.getElementById("eliminarCliente");

if (eliminarCliente) {
    eliminarCliente.addEventListener("submit", function (e) {
        e.preventDefault(); // Evitamos que la página se recargue sola

        let datosFormulario = new FormData(eliminarCliente);

        fetch("./PHPempresa/eliminar.php", {
            method: "POST",
            body: datosFormulario
        })
        .then(res => res.json())
        .then(respuesta => {
            // Hallazgo 25: se comprueba el campo "exito" antes de confirmar.
            if (respuesta.exito) {
                alert("Cliente eliminado con éxito");
            } else {
                alert(respuesta.error || "No se pudo eliminar el cliente");
            }
            eliminarCliente.reset(); // Limpia el input
            cargarClientes(); // Recarga la tabla en pantalla sin refrescar la página
        })
        .catch(err => {
            console.error(err);
            alert("Error de conexión al eliminar el cliente");
        });
    });
}

// 3. ACTUALIZAR CLIENTE
const actualizarCliente = document.getElementById("actualizarCliente");

if (actualizarCliente) {
    actualizarCliente.addEventListener("submit", function (e) {
        e.preventDefault();

        let datosFormulario = new FormData(actualizarCliente);

        fetch("./PHPempresa/actualizar.php", {
            method: "POST",
            body: datosFormulario
        })
        .then(res => res.json())
        .then(respuesta => {
            // Hallazgo 25: se comprueba el campo "exito" antes de confirmar.
            if (respuesta.exito) {
                alert("Cliente actualizado con éxito");
            } else {
                alert(respuesta.error || "No se pudo actualizar el cliente");
            }
            actualizarCliente.reset(); // Limpia los inputs
            cargarClientes(); // Recarga la tabla en pantalla
        })
        .catch(err => {
            console.error(err);
            alert("Error de conexión al actualizar el cliente");
        });
    });
}
