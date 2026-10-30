/**
 * A.P.P.E. — Gestión de clientes (panel de la empresa).
 *
 * Requisito funcional RF-06 · Caso de uso CU-02 «Gestionar Usuarios».
 */
(function () {
    "use strict";

    API.configurar("./PHPempresa/");

    const cuerpoTabla = document.getElementById("cuerpoTabla");
    const formularioActualizar = document.getElementById("actualizarCliente");
    const formularioEliminar = document.getElementById("eliminarCliente");
    const mensaje = document.getElementById("panelMensaje");
    const campoBuscar = document.getElementById("buscarCliente");
    const selectorEstado = document.getElementById("filtroEstado");

    function pintarClientes(clientes) {
        if (!cuerpoTabla) {
            return;
        }

        Dom.limpiar(cuerpoTabla);

        if (clientes.length === 0) {
            const fila = Dom.crear("tr");
            const celda = Dom.crear("td", { texto: "No se encontraron clientes.", atributos: { colspan: "7" } });
            fila.appendChild(celda);
            cuerpoTabla.appendChild(fila);
            return;
        }

        clientes.forEach((cliente) => {
            const fila = Dom.crear("tr", { dataset: { id: String(cliente.id_cliente) } });

            fila.appendChild(Dom.crear("td", { texto: String(cliente.id_cliente) }));
            fila.appendChild(Dom.crear("td", { texto: cliente.nombre + " " + cliente.apellido }));
            fila.appendChild(Dom.crear("td", { texto: cliente.correo }));
            fila.appendChild(Dom.crear("td", { texto: cliente.telefono || "-" }));
            fila.appendChild(Dom.crear("td", { texto: cliente.plan_nombre || "Sin plan" }));
            fila.appendChild(Dom.crear("td", { texto: cliente.entrenador_nombre || "Sin entrenador" }));
            fila.appendChild(Dom.crear("td", { texto: cliente.activo ? "Activo" : "Inactivo" }));

            const celdaAcciones = Dom.crear("td", { clases: ["acciones"] });

            const botonEditar = Dom.boton("Editar", () => {
                if (!formularioActualizar) {
                    return;
                }

                formularioActualizar.elements["id"].value = String(cliente.id_cliente);
                formularioActualizar.elements["nombre"].value = cliente.nombre;
                formularioActualizar.elements["apellido"].value = cliente.apellido;
                formularioActualizar.elements["correo"].value = cliente.correo;
                formularioActualizar.elements["telefono"].value = cliente.telefono || "";
                formularioActualizar.scrollIntoView({ behavior: "smooth", block: "center" });
            }, { clases: ["btnActualizar"] });

            const botonEliminar = Dom.boton("Eliminar", () => {
                if (!formularioEliminar) {
                    return;
                }

                formularioEliminar.elements["id"].value = String(cliente.id_cliente);
                formularioEliminar.dispatchEvent(new Event("submit", { cancelable: true, bubbles: true }));
            }, { clases: ["btnEliminar"] });

            celdaAcciones.appendChild(botonEditar);
            celdaAcciones.appendChild(botonEliminar);
            fila.appendChild(celdaAcciones);

            cuerpoTabla.appendChild(fila);
        });
    }

    function cargarClientes() {
        const parametros = {};

        if (campoBuscar && campoBuscar.value.trim() !== "") {
            parametros.q = campoBuscar.value.trim();
        }

        if (selectorEstado && selectorEstado.value) {
            parametros.estado = selectorEstado.value;
        }

        API.get("mostrarcliente.php", parametros)
            .then((datos) => pintarClientes(datos.clientes || []))
            .catch((error) => {
                Dom.mensaje(mensaje, error.mensaje || "No se pudo cargar el listado de clientes.", "error");
            });
    }

    if (formularioActualizar) {
        formularioActualizar.addEventListener("submit", (evento) => {
            evento.preventDefault();

            const datos = new FormData(formularioActualizar);
            const id = String(datos.get("id") || "").trim();

            if (id === "") {
                Dom.mensaje(mensaje, "Seleccioná un cliente de la tabla para modificarlo.", "error");
                return;
            }

            API.postForm("actualizar.php", datos)
                .then(() => {
                    Dom.mensaje(mensaje, "Cliente actualizado correctamente.", "exito");
                    cargarClientes();
                })
                .catch((error) => {
                    Dom.mensaje(mensaje, error.mensaje || "No se pudo actualizar el cliente.", "error");
                });
        });
    }

    if (formularioEliminar) {
        formularioEliminar.addEventListener("submit", (evento) => {
            evento.preventDefault();

            const datos = new FormData(formularioEliminar);
            const id = String(datos.get("id") || "").trim();

            if (id === "") {
                return;
            }

            const confirmar = () => {
                API.post("eliminar.php", { id: id })
                    .then(() => {
                        Dom.mensaje(mensaje, "El cliente fue eliminado.", "exito");
                        cargarClientes();
                    })
                    .catch((error) => {
                        Dom.mensaje(mensaje, error.mensaje || "No se pudo eliminar el cliente.", "error");
                    });
            };

            if (typeof Swal !== "undefined") {
                Swal.fire({
                    title: "¿Eliminar el cliente?",
                    text: "La operación no se puede deshacer.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Sí, eliminar",
                    cancelButtonText: "Cancelar",
                }).then((resultado) => {
                    if (resultado.isConfirmed) {
                        confirmar();
                    }
                });
            } else if (window.confirm("¿Eliminar el cliente? La operación no se puede deshacer.")) {
                confirmar();
            }
        });
    }

    if (campoBuscar) {
        campoBuscar.addEventListener("input", () => {
            cargarClientes();
        });
    }

    if (selectorEstado) {
        selectorEstado.addEventListener("change", () => {
            cargarClientes();
        });
    }

    if (cuerpoTabla) {
        cargarClientes();
    }
})();
