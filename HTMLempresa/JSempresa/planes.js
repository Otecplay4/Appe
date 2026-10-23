/**
 * A.P.P.E. — Gestión de planes (empresa).
 *
 * Requisito funcional RF-07 · Caso de uso CU-03 «Registrar Plan/Suscripción».
 */
(function () {
    "use strict";

    API.configurar("./PHPempresa/");

    const formulario = document.getElementById("crearPlan");
    const contenedor = document.getElementById("planesContainer");
    const mensaje = document.getElementById("planMensaje");
    const botonCancelar = document.getElementById("cancelarEdicion");
    const tituloForm = document.getElementById("tituloFormPlan");
    const campoId = document.getElementById("idPlan");
    const campoArchivo = document.getElementById("imagenPlan");
    const notaImagen = document.getElementById("notaImagen");

    let idEnEdicion = null;

    const PERIODOS = [
        { clave: "semanal", etiqueta: "Semanal" },
        { clave: "mensual", etiqueta: "Mensual" },
        { clave: "trimestral", etiqueta: "Trimestral" },
        { clave: "anual", etiqueta: "Anual" },
    ];

    function salirDeEdicion() {
        idEnEdicion = null;

        if (campoId) {
            campoId.value = "";
        }
        if (formulario) {
            formulario.reset();
        }
        if (campoArchivo) {
            // Al crear, la imagen es obligatoria; al editar es opcional.
            campoArchivo.required = true;
        }
        if (tituloForm) {
            tituloForm.textContent = "Nuevo plan";
        }
        if (botonCancelar) {
            botonCancelar.hidden = true;
        }
        if (notaImagen) {
            notaImagen.textContent = "La imagen es obligatoria al crear un plan.";
        }
    }

    function pintarPlan(plan) {
        if (!contenedor) {
            return;
        }

        const tarjeta = Dom.crear("article", { clases: ["tarjeta"] });

        if (plan.extension) {
            const imagen = Dom.crear("img", {
                clases: ["imagen-plan"],
                atributos: {
                    // El directorio de imágenes está protegido: se pide a
                    // través del endpoint, que valida la sesión.
                    src: "./PHPempresa/imagen.php?tipo=plan&id=" + encodeURIComponent(plan.id_plan),
                    alt: plan.nombre,
                },
            });
            imagen.addEventListener("error", () => {
                imagen.style.display = "none";
            });
            tarjeta.appendChild(imagen);
        }

        tarjeta.appendChild(Dom.texto("h3", plan.nombre, { clases: ["nombre-plan"] }));

        if (plan.descripcion) {
            tarjeta.appendChild(Dom.texto("p", plan.descripcion, { clases: ["descripcion-plan"] }));
        }

        if (plan.beneficio) {
            tarjeta.appendChild(Dom.texto("p", "Beneficio: " + plan.beneficio, { clases: ["beneficio-plan"] }));
        }

        const lista = Dom.crear("ul", { clases: ["lista-precios"] });
        const precios = plan.precios || {};

        PERIODOS.forEach((periodo) => {
            const valor = precios[periodo.clave];
            if (valor === undefined || valor === null) {
                return;
            }

            lista.appendChild(Dom.texto("li", periodo.etiqueta + ": " + Dom.precio(valor)));
        });

        tarjeta.appendChild(lista);
        tarjeta.appendChild(Dom.texto("span", plan.activo ? "Publicado" : "Oculto", { clases: ["estado-plan"] }));

        const acciones = Dom.crear("div", { clases: ["acciones"] });

        const botonEditar = Dom.boton("Actualizar", () => {
            idEnEdicion = plan.id_plan;

            if (campoId) {
                campoId.value = String(plan.id_plan);
            }
            if (formulario) {
                formulario.elements["nombre_plan"].value = plan.nombre;
                formulario.elements["descripcion"].value = plan.descripcion || "";
                formulario.elements["beneficio"].value = plan.beneficio || "";
                formulario.elements["duracion"].value = plan.duracion || "";
                formulario.elements["tipo_de_plan"].value = plan.tipo_de_plan || "";

                PERIODOS.forEach((periodo) => {
                    const campo = formulario.elements["precio_" + periodo.clave];
                    if (campo) {
                        campo.value = precios[periodo.clave] !== undefined ? precios[periodo.clave] : "";
                    }
                });
            }
            if (tituloForm) {
                tituloForm.textContent = "Editar plan #" + plan.id_plan;
            }
            if (campoArchivo) {
                // Al editar no hace falta volver a subir la imagen.
                campoArchivo.required = false;
                campoArchivo.value = "";
            }
            if (botonCancelar) {
                botonCancelar.hidden = false;
            }
            if (notaImagen) {
                notaImagen.textContent = "La imagen es opcional al editar: si no subís una nueva se conserva la actual.";
            }

            formulario.scrollIntoView({ behavior: "smooth", block: "center" });
        }, { clases: ["btnActualizar"] });

        const botonEliminar = Dom.boton("Eliminar", () => {
            const confirmar = () => {
                API.post("eliminarplan.php", { id: plan.id_plan })
                    .then(() => {
                        Dom.mensaje(mensaje, "El plan fue eliminado.", "exito");
                        actualizar();
                    })
                    .catch((error) => {
                        Dom.mensaje(mensaje, error.mensaje || "No se pudo eliminar el plan.", "error");
                    });
            };

            if (typeof Swal !== "undefined") {
                Swal.fire({
                    title: "¿Eliminar el plan?",
                    text: "Los clientes que lo tengan asignado quedarán sin plan.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Sí, eliminar",
                    cancelButtonText: "Cancelar",
                }).then((resultado) => {
                    if (resultado.isConfirmed) {
                        confirmar();
                    }
                });
            } else if (window.confirm("¿Eliminar el plan?")) {
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

        API.get("mostrarplanes.php")
            .then((datos) => {
                const planes = datos.planes || [];

                if (planes.length === 0) {
                    contenedor.appendChild(Dom.texto("p", "Todavía no hay planes cargados.", { clases: ["sin-planes"] }));
                    return;
                }

                planes.forEach(pintarPlan);
            })
            .catch((error) => {
                Dom.mensaje(mensaje, error.mensaje || "No se pudo cargar el listado de planes.", "error");
            });
    }

    if (formulario) {
        formulario.addEventListener("submit", (evento) => {
            evento.preventDefault();

            const datos = new FormData(formulario);
            const nombre = String(datos.get("nombre_plan") || "").trim();

            if (nombre === "") {
                Dom.mensaje(mensaje, "El nombre del plan es obligatorio.", "error");
                return;
            }

            if (idEnEdicion === null) {
                API.postForm("agregarplan.php", datos)
                    .then(() => {
                        Dom.mensaje(mensaje, "Plan creado correctamente.", "exito");
                        salirDeEdicion();
                        actualizar();
                    })
                    .catch((error) => {
                        Dom.mensaje(mensaje, error.mensaje || "No se pudo crear el plan.", "error");
                    });
                return;
            }

            datos.set("id", String(idEnEdicion));

            API.postForm("actualizarplan.php", datos)
                .then(() => {
                    Dom.mensaje(mensaje, "Plan actualizado correctamente.", "exito");
                    salirDeEdicion();
                    actualizar();
                })
                .catch((error) => {
                    Dom.mensaje(mensaje, error.mensaje || "No se pudo actualizar el plan.", "error");
                });
        });
    }

    if (botonCancelar) {
        botonCancelar.addEventListener("click", salirDeEdicion);
    }

    actualizar();
})();
