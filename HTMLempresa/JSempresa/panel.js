// 1. Apenas carga la página HTML, ejecutamos la función para mostrar los clientes
document.addEventListener("DOMContentLoaded", function () {
    cargarClientes();
});

// Función para pedir los datos a PHP y armar las filas de la tabla
function cargarClientes() {
    fetch("./PHPempresa/mostrarcliente.php")
        .then(res => res.json())
        .then(datos => {
            let cuerpoTabla = document.getElementById("cuerpoTabla");
            cuerpoTabla.innerHTML = ""; // Limpiamos la tabla antes de recargar

            // Recorremos el array de clientes y creamos las filas <tr>
            datos.forEach(cliente => {
                cuerpoTabla.innerHTML += `
                    <tr>
                        <td>${cliente.id}</td>
                        <td>${cliente.nombre}</td>
                        <td>${cliente.apellido}</td>
                        <td>${cliente.correo}</td>
                    </tr>
                `;
            });
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
            alert("Cliente eliminado con éxito");
            eliminarCliente.reset(); // Limpia el input
            cargarClientes(); // Recarga la tabla en pantalla sin refrescar la página
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
            alert("Cliente actualizado con éxito");
            actualizarCliente.reset(); // Limpia los inputs
            cargarClientes(); // Recarga la tabla en pantalla
        });
    });
}