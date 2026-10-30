/**
 * A.P.P.E. — Constructor de rutinas (empresa).
 *
 * Requisito funcional RF-13 · Caso de uso CU-06 «Consultar y Modificar Rutina
 * de Entrenamiento» (actor Entrenador).
 *
 * El guardado se hace con `fetch` (y no con el envío nativo del formulario)
 * porque `agregarrutina.php` responde JSON..

 * El día de cada ejercicio se envía explícitamente
 */
(function () {
    "use strict";

    API.configurar("./PHPempresa/");

    const formulario = document.getElementById("crearRutina");
    const contenedorDias = document.getElementById("diasContainer");
    const selectorClientes = document.getElementById("clientesSelect");
    const mensaje = document.getElementById("rutinaMensaje");
    const campoToken = document.getElementById("csrf_token");

    let contadorDias = 0;

    function campoEtiquetado(etiqueta, input) {
        const caja = Dom.crear("label", { clases: ["campo"] });
        caja.appendChild(Dom.texto("span", etiqueta));
        caja.appendChild(input);
        return caja;
    }

    function crearFilaEjercicio(dia) {
        const fila = Dom.crear("div", { clases: ["fila-ejercicio"] });

        const musculo = Dom.crear("input", {
            atributos: { type: "text", name: "musculo[]", placeholder: "Músculo", maxlength: "200", required: "required" },
        });
        const ejercicio = Dom.crear("input", {
            atributos: { type: "text", name: "ejercicio[]", placeholder: "Ejercicio", maxlength: "200", required: "required" },
        });
        const series = Dom.crear("input", {
            atributos: { type: "number", name: "series[]", placeholder: "Series", min: "1", max: "99", required: "required" },
        });
        const repeticiones = Dom.crear("input", {
            atributos: { type: "number", name: "repeticiones[]", placeholder: "Repeticiones", min: "1", max: "999", required: "required" },
        });
        const diaOculto = Dom.crear("input", {
            atributos: { type: "hidden", name: "dia[]", value: String(dia) },
        });

        const botonQuitar = Dom.boton("Quitar", () => {
            fila.remove();
        }, { clases: ["btnEliminar"] });

        fila.appendChild(campoEtiquetado("Músculo", musculo));
        fila.appendChild(campoEtiquetado("Ejercicio", ejercicio));
        fila.appendChild(campoEtiquetado("Series", series));
        fila.appendChild(campoEtiquetado("Repeticiones", repeticiones));
        fila.appendChild(diaOculto);
        fila.appendChild(botonQuitar);

        return fila;
    }

    function agregarDia() {
        contadorDias += 1;

        const dia = contadorDias;
        const bloque = Dom.crear("section", { clases: ["dia"], dataset: { dia: String(dia) } });

        const cabecera = Dom.crear("header", { clases: ["dia-cabecera"] });
        cabecera.appendChild(Dom.texto("h3", "Día " + dia));

        const botonEliminar = Dom.boton("Eliminar día", () => {
            bloque.remove();
        }, { clases: ["btnEliminar"] });
        cabecera.appendChild(botonEliminar);
        bloque.appendChild(cabecera);

        const lista = Dom.crear("div", { clases: ["ejercicios-dia"] });
        bloque.appendChild(lista);

        const botonAgregar = Dom.boton("+ Agregar ejercicio", () => {
            lista.appendChild(crearFilaEjercicio(dia));
        }, { clases: ["btnAgregar"] });
        bloque.appendChild(botonAgregar);

        lista.appendChild(crearFilaEjercicio(dia));

        if (contenedorDias) {
            contenedorDias.appendChild(bloque);
        }
    }

    function cargarClientes() {
        if (!selectorClientes) {
            return;
        }

        API.get("buscarcliente.php", { limite: 100 })
            .then((datos) => {
                Dom.limpiar(selectorClientes);

                const vacio = Dom.crear("option", { texto: "Seleccioná un cliente", atributos: { value: "" } });
                selectorClientes.appendChild(vacio);

                (datos.clientes || []).forEach((cliente) => {
                    const opcion = Dom.crear("option", {
                        texto: cliente.nombre_completo + " (" + cliente.correo + ")",
                        atributos: { value: String(cliente.id_cliente) },
                    });
                    selectorClientes.appendChild(opcion);
                });
            })
            .catch((error) => {
                Dom.mensaje(mensaje, error.mensaje || "No se pudo cargar la lista de clientes.", "error");
            });
    }

    if (formulario) {
        formulario.addEventListener("submit", (evento) => {
            evento.preventDefault();

            const idCliente = selectorClientes ? selectorClientes.value : "";

            if (idCliente === "") {
                Dom.mensaje(mensaje, "Seleccioná el cliente de la rutina.", "error");
                return;
            }

            const datos = new FormData(formulario);
            datos.set("id_cliente", idCliente);

            // El token CSRF se renueva antes de enviar, por si la sesión
            // hubiera caducado.
            API.obtenerCsrf(true)
                .then((token) => {
                    datos.set("csrf_token", token || "");
                    return API.postForm("agregarrutina.php", datos);
                })
                .then(() => {
                    Dom.mensaje(mensaje, "Rutina guardada correctamente.", "exito");
                })
                .catch((error) => {
                    Dom.mensaje(mensaje, error.mensaje || "No se pudo guardar la rutina.", "error");
                });
        });
    }

    if (contenedorDias) {
        agregarDia();

        // Botón para agregar un día más a la planificación. Se crea desde
        // JavaScript (no hay `onclick` en línea: la CSP lo prohíbe).
        const botonAgregarDia = Dom.boton("+ AGREGAR DÍA", () => {
            agregarDia();
        }, { clases: ["btnAgregar"] });

        contenedorDias.parentNode.insertBefore(botonAgregarDia, contenedorDias.nextSibling);
    }

    cargarClientes();
})();
