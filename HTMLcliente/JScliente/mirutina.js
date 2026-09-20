document.addEventListener("DOMContentLoaded", () => {
    cargarRutina();
});

function cargarRutina() {
    // Captura los parámetros de la URL o recupera el ID desde SessionStorage
    const urlParams = new URLSearchParams(window.location.search);
    const idCliente = urlParams.get('id_cliente') || sessionStorage.getItem("id_cliente");

    let urlFetch = "./PHPcliente/mirutina.php";
    if (idCliente) {
        urlFetch += `?id_cliente=${idCliente}`;
    }

    fetch(urlFetch)
        .then(res => res.json())
        .then(datos => {
            const titulo = document.getElementById("tituloRutina");
            const tabla = document.getElementById("tablaEjercicios");

            if (datos.error || !datos.length) {
                titulo.textContent = "NO TIENES NINGUNA RUTINA ASIGNADA.";
                return;
            }

            // Asigna el nombre de la rutina
            titulo.textContent = datos[0].nombre_rutina.toUpperCase();
            tabla.innerHTML = "";

            // Inyecta los ejercicios en la tabla
            datos.forEach(item => {
                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td>${item.musculo}</td>
                    <td>${item.ejercicio}</td>
                    <td>${item.serie_repeticion}</td>
                `;
                tabla.appendChild(fila);
            });
        })
        .catch(err => {
            console.error("Error al cargar la rutina:", err);
            document.getElementById("tituloRutina").textContent = "ERROR AL CARGAR LA RUTINA.";
        });
}