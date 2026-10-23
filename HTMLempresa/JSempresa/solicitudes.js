/**
 * A.P.P.E. — Solicitudes de cambio de rutina.
 *
 * Requisito funcional RF-14 · Caso de uso CU-06.
 */
(function () {
    "use strict";

    API.configurar("./PHPempresa/");

    const contenedor = document.getElementById("solicitudesContainer");
    const mensaje = document.getElementById("solicitudMensaje");
    const selectorEstado = document.getElementById("filtroEstadoSolicitud");

    function pintarSolicitud(solicitud) {
        if (!contenedor) {
            return;
        }

        const tarjeta = Dom.crear("article", { clases: ["tarjeta"] });
        tarjeta.appendChild(Dom.texto("h3", solicitud.cliente, { clases: ["nombre-plan"] }));
        tarjeta.appendChild(Dom.texto("p", solicitud.correo, { clases: ["descripcion-plan"] }));
        tarjeta.appendChild(Dom.texto("p", solicitud.mensaje, { clases: ["beneficio-plan"] }));

        if (solicitud.rutina_nombre) {
            tarjeta.appendChild(Dom.texto("p", "Rutina actual: " + solicitud.rutina_nombre, { clases: ["beneficio-plan"] }));
        }

        tarjeta.appendChild(Dom.texto("span", "Estado: " + solicitud.estado, { clases: ["estado-plan"] }));
        tarjeta.appendChild(Dom.texto("span", Dom.fecha(solicitud.fecha), { clases: ["estado-plan"] }));

        if (solicitud.respuesta) {
            tarjeta.appendChild(Dom.texto("p", "Respuesta: " + solicitud.respuesta, { clases: ["beneficio-plan"] }));
        }

        if (solicitud.estado === "pendiente") {
            const campoRespuesta = Dom.crear("textarea", {
                atributos: { rows: "2", placeholder: "Respuesta para el cliente", maxlength: "500" },
            });
            tarjeta.appendChild(campoRespuesta);

            const acciones = Dom.crear("div", { clases: ["acciones"] });

            const botonAprobar = Dom.boton("Aprobar", () => {
                API.post("solicitudes.php?accion=aprobar", {
                    id: solicitud.id_solicitud,
                    respuesta: campoRespuesta.value,
                })
                    .then(() => {
                        Dom.mensaje(mensaje, "Solicitud aprobada.", "exito");
                        cargar();
                    })
                    .catch((error) => {
                        Dom.mensaje(mensaje, error.mensaje || "No se pudo aprobar la solicitud.", "error");
                    });
            }, { clases: ["btnActualizar"] });

            const botonRechazar = Dom.boton("Rechazar", () => {
                API.post("solicitudes.php?accion=rechazar", {
                    id: solicitud.id_solicitud,
                    respuesta: campoRespuesta.value,
                })
                    .then(() => {
                        Dom.mensaje(mensaje, "Solicitud rechazada.", "exito");
                        cargar();
                    })
                    .catch((error) => {
                        Dom.mensaje(mensaje, error.mensaje || "No se pudo rechazar la solicitud.", "error");
                    });
            }, { clases: ["btnEliminar"] });

            acciones.appendChild(botonAprobar);
            acciones.appendChild(botonRechazar);
            tarjeta.appendChild(acciones);
        }

        contenedor.appendChild(tarjeta);
    }

    function cargar() {
        if (!contenedor) {
            return;
        }

        Dom.limpiar(contenedor);

        const parametros = { accion: "listar" };

        if (selectorEstado && selectorEstado.value) {
            parametros.estado = selectorEstado.value;
        }

        API.get("solicitudes.php", parametros)
            .then((datos) => {
                const solicitudes = datos.solicitudes || [];

                if (solicitudes.length === 0) {
                    contenedor.appendChild(Dom.texto("p", "No hay solicitudes para mostrar.", { clases: ["sin-datos"] }));
                    return;
                }

                solicitudes.forEach(pintarSolicitud);
            })
            .catch((error) => {
                Dom.mensaje(mensaje, error.mensaje || "No se pudieron cargar las solicitudes.", "error");
            });
    }

    if (selectorEstado) {
        selectorEstado.addEventListener("change", cargar);
    }

    cargar();
})();
