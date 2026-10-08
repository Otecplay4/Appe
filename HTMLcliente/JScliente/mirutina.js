document.addEventListener("DOMContentLoaded", () => {
    cargarRutina();
});

function cargarRutina() {
    // Hallazgo 4 (IDOR): el id del cliente ya no se envía por la URL/query string;
    // el backend lo toma exclusivamente de la sesión autenticada.
    const urlFetch = "./PHPcliente/mirutina.php";

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

            // Hallazgo 5 (XSS almacenado): se construyen los nodos del DOM y se
            // asigna el contenido con textContent, en vez de interpolar en innerHTML.
            tabla.innerHTML = "";
            datos.forEach(item => {
                const fila = document.createElement("tr");

                const celdaMusculo = document.createElement("td");
                celdaMusculo.textContent = item.musculo;

                const celdaEjercicio = document.createElement("td");
                celdaEjercicio.textContent = item.ejercicio;

                const celdaSerieRep = document.createElement("td");
                celdaSerieRep.textContent = item.serie_repeticion;

                fila.appendChild(celdaMusculo);
                fila.appendChild(celdaEjercicio);
                fila.appendChild(celdaSerieRep);
                tabla.appendChild(fila);
            });
        })
        .catch(err => {
            console.error("Error al cargar la rutina:", err);
            document.getElementById("tituloRutina").textContent = "ERROR AL CARGAR LA RUTINA.";
        });
}
