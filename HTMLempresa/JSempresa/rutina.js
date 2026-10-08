const crearRutina = document.getElementById("crearRutina")

let contadorDias = 0;

function agregarDia() {
    contadorDias++;

    let html = `
        <div class="dia" id="dia_${contadorDias}">
            <h2>Día ${contadorDias}</h2>
            
            <button type="button" class="btnEliminar" onclick="eliminarDia(${contadorDias})">
                Eliminar Día
            </button>
            <hr>
            
            <div id="ejercicios_${contadorDias}"></div>
            
            <button type="button" class="btnAgregar" onclick="agregarEjercicio(${contadorDias})">
                + Ejercicio
            </button>
        </div>
    `;

    document.getElementById("diasContainer").insertAdjacentHTML("beforeend", html);
}

function eliminarDia(id) {
    let diaElement = document.getElementById(`dia_${id}`);
    if(diaElement) {
        diaElement.remove();
    }
}

function agregarEjercicio(dia) {
    let contenedor = document.getElementById(`ejercicios_${dia}`);

    let html = `
        <div class="ejercicio">
            <!-- Este input oculto envía el número de día al PHP -->
            <input type="hidden" name="dia[]" value="${dia}">

            <label>Músculo</label>
            <input type="text" name="musculo[]" placeholder="Ej: Pecho" required>

            <label>Ejercicio</label>
            <input type="text" name="ejercicio[]" placeholder="Ej: Press banca" required>

            <label>Series</label>
            <input type="number" name="series[]" placeholder="Ej: 4" required min="1">

            <label>Repeticiones</label>
            <input type="number" name="repeticiones[]" placeholder="Ej: 12" required min="1">

            <button type="button" class="btnEliminar" onclick="this.parentElement.remove()">
                Eliminar Ejercicio
            </button>
        </div>
    `;

    contenedor.insertAdjacentHTML("beforeend", html);
}

// Iniciar con un día por defecto
agregarDia();

// Función para cargar los clientes desde la base de datos
function cargarClientes() {
    fetch('./PHPempresa/buscarcliente.php') 
        .then(res => res.json())
        .then(datos => {
            let select = document.getElementById("clientesSelect");
            
            // Limpiamos el mensaje de "Cargando..."
            select.innerHTML = '<option value="">Seleccione un cliente...</option>';

            // Si hay un error desde PHP lo mostramos
            if(datos.error){
                console.error(datos.error);
                return;
            }

            // Recorremos los clientes que llegaron de la base de datos y los agregamos
            datos.forEach(cliente => {
                let opcion = document.createElement("option");
                opcion.value = cliente.id;
                opcion.textContent = `${cliente.nombre} ${cliente.apellido} (ID: ${cliente.id})`;
                select.appendChild(opcion);
            });
        })
        .catch(error => {
            console.error("Error en la petición de clientes:", error);
            document.getElementById("clientesSelect").innerHTML = '<option value="">Error al cargar</option>';
        });
}

// Ejecutamos la función apenas cargue la ventana del navegador
window.onload = function() {
    cargarClientes();
};