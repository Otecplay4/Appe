/**
 * A.P.P.E. — Gestión de proveedores.
 *
 * Requisito funcional RF-09 · Caso de uso CU-05.
 */
(function () {
    "use strict";

    API.configurar("./PHPempresa/");

    const formulario = document.getElementById("formularioProveedor");
    const contenedor = document.getElementById("proveedoresContainer");
    const mensaje = document.getElementById("proveedorMensaje");
    const botonCancelar = document.getElementById("cancelarEdicion");
    const tituloForm = document.getElementById("tituloFormProveedor");
    const campoId = document.getElementById("idProveedor");

    let idEnEdicion = null;

    function salirDeEdicion() {
        idEnEdicion = null;

        if (campoId) {
            campoId.value = "";
        }
        if (formulario) {
            formulario.reset();
        }
        if (tituloForm) {
            tituloForm.textContent = "Nuevo proveedor";
        }
        if (botonCancelar) {
            botonCancelar.hidden = true;
        }
    }

    function pintarProveedor(proveedor) {
        if (!contenedor) {
            return;
        }

        const tarjeta = Dom.crear("article", { clases: ["tarjeta"] });
        tarjeta.appendChild(Dom.texto("h3", proveedor.nombre, { clases: ["nombre-plan"] }));
        tarjeta.appendChild(Dom.texto("p", proveedor.correo, { clases: ["descripcion-plan"] }));

        if (proveedor.telefono) {
            tarjeta.appendChild(Dom.texto("p", "Teléfono: " + proveedor.telefono, { clases: ["beneficio-plan"] }));
        }

        if (proveedor.direccion) {
            tarjeta.appendChild(Dom.texto("p", proveedor.direccion, { clases: ["beneficio-plan"] }));
        }

        tarjeta.appendChild(Dom.texto("span", proveedor.productos + " producto(s)", { clases: ["estado-plan"] }));
        tarjeta.appendChild(Dom.texto("span", proveedor.activo ? "Activo" : "Inactivo", { clases: ["estado-plan"] }));

        const acciones = Dom.crear("div", { clases: ["acciones"] });

        const botonEditar = Dom.boton("Actualizar", () => {
            idEnEdicion = proveedor.id_proveedor;

            if (campoId) {
                campoId.value = String(proveedor.id_proveedor);
            }
            if (formulario) {
                formulario.elements["nombre"].value = proveedor.nombre;
                formulario.elements["correo"].value = proveedor.correo;
                formulario.elements["telefono"].value = proveedor.telefono || "";

                // La dirección se devuelve por partes para poder recargarla
                // sin perder los datos al editar.
                ["calle", "departamento", "manzana", "solar"].forEach((campo) => {
                    const entrada = formulario.elements[campo];
                    if (entrada) {
                        entrada.value = proveedor[campo] || "";
                    }
                });
            }
            if (tituloForm) {
                tituloForm.textContent = "Editar proveedor #" + proveedor.id_proveedor;
            }
            if (botonCancelar) {
                botonCancelar.hidden = false;
            }

            formulario.scrollIntoView({ behavior: "smooth", block: "center" });
        }, { clases: ["btnActualizar"] });

        const botonEliminar = Dom.boton("Eliminar", () => {
            const confirmar = () => {
                API.post("proveedores.php?accion=eliminar", { id: proveedor.id_proveedor })
                    .then(() => {
                        Dom.mensaje(mensaje, "El proveedor fue eliminado.", "exito");
                        actualizar();
                    })
                    .catch((error) => {
                        Dom.mensaje(mensaje, error.mensaje || "No se pudo eliminar el proveedor.", "error");
                    });
            };

            if (typeof Swal !== "undefined") {
                Swal.fire({
                    title: "¿Eliminar el proveedor?",
                    text: "Los productos quedarán sin proveedor asignado.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Sí, eliminar",
                    cancelButtonText: "Cancelar",
                }).then((resultado) => {
                    if (resultado.isConfirmed) {
                        confirmar();
                    }
                });
            } else if (window.confirm("¿Eliminar el proveedor?")) {
                confirmar();
            }
        }, { clases: ["btnEliminar"] });

        acciones.appendChild(botonEditar);
        acciones.appendChild(botonEliminar);
        tarjeta.appendChild(acciones);

        contenedor.appendChild(tarjeta);
    }

    function actualizar() {
        if (!contenedor) {
            return;
        }

        Dom.limpiar(contenedor);

        API.get("proveedores.php", { accion: "listar" })
            .then((datos) => {
                const proveedores = datos.proveedores || [];

                if (proveedores.length === 0) {
                    contenedor.appendChild(Dom.texto("p", "Todavía no hay proveedores registrados.", { clases: ["sin-datos"] }));
                    return;
                }

                proveedores.forEach(pintarProveedor);
            })
            .catch((error) => {
                Dom.mensaje(mensaje, error.mensaje || "No se pudo cargar el listado de proveedores.", "error");
            });
    }

    if (formulario) {
        formulario.addEventListener("submit", (evento) => {
            evento.preventDefault();

            const datos = new FormData(formulario);
            const nombre = String(datos.get("nombre") || "").trim();

            if (nombre === "") {
                Dom.mensaje(mensaje, "El nombre del proveedor es obligatorio.", "error");
                return;
            }

            if (idEnEdicion === null) {
                API.postForm("proveedores.php?accion=crear", datos)
                    .then(() => {
                        Dom.mensaje(mensaje, "Proveedor creado correctamente.", "exito");
                        salirDeEdicion();
                        actualizar();
                    })
                    .catch((error) => {
                        Dom.mensaje(mensaje, error.mensaje || "No se pudo crear el proveedor.", "error");
                    });
                return;
            }

            datos.set("id", String(idEnEdicion));

            API.postForm("proveedores.php?accion=actualizar", datos)
                .then(() => {
                    Dom.mensaje(mensaje, "Proveedor actualizado correctamente.", "exito");
                    salirDeEdicion();
                    actualizar();
                })
                .catch((error) => {
                    Dom.mensaje(mensaje, error.mensaje || "No se pudo actualizar el proveedor.", "error");
                });
        });
    }

    if (botonCancelar) {
        botonCancelar.addEventListener("click", salirDeEdicion);
    }

    actualizar();
})();
