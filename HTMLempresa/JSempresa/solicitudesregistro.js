(function () {
    "use strict";

    API.configurar("./PHPempresa/");

    const contenedor = document.getElementById("solicitudesRegistroContainer");
    const mensaje = document.getElementById("solicitudRegistroMensaje");
    const selectorEstado = document.getElementById("filtroEstadoRegistro");

    function pintarSolicitud(solicitud) {
        if (!contenedor) {
            return;
        }

        const tarjeta = Dom.crear("article", { clases: ["tarjeta"] });
        tarjeta.appendChild(Dom.texto("h3", solicitud.nombre + " " + (solicitud.apellido || ""), { clases: ["nombre-plan"] }));
        tarjeta.appendChild(Dom.texto("p", "Cédula: " + solicitud.cedula, { clases: ["descripcion-plan"] }));
        tarjeta.appendChild(Dom.texto("p", "Correo: " + solicitud.correo, { clases: ["descripcion-plan"] }));
        tarjeta.appendChild(Dom.texto("p", "Teléfono: " + solicitud.telefono, { clases: ["descripcion-plan"] }));
        tarjeta.appendChild(Dom.texto("p", "Rol solicitado: " + solicitud.rol, { clases: ["beneficio-plan"] }));
        tarjeta.appendChild(Dom.texto("span", "Estado: " + solicitud.estado, { clases: ["estado-plan"] }));
        tarjeta.appendChild(Dom.texto("span", Dom.fecha(solicitud.fecha_solicitud), { clases: ["estado-plan"] }));

        if (solicitud.motivo_rechazo) {
            tarjeta.appendChild(Dom.texto("p", "Motivo: " + solicitud.motivo_rechazo, { clases: ["beneficio-plan"] }));
        }

        if (solicitud.estado === "pendiente") {
            const campoRespuesta = Dom.crear("textarea", {
                atributos: { rows: "2", placeholder: "Motivo (obligatorio al rechazar)", maxlength: "500" },
            });
            tarjeta.appendChild(campoRespuesta);

            const acciones = Dom.crear("div", { clases: ["acciones"] });

            const botonAprobar = Dom.boton("Aprobar", () => {
                API.post("solicitudesregistro.php?accion=aprobar", {
                    id: solicitud.id_solicitud,
                    respuesta: campoRespuesta.value,
                })
                    .then(() => {
                        Dom.mensaje(mensaje, "Solicitud aprobada. La cuenta fue creada.", "exito");
                        cargar();
                    })
                    .catch((error) => {
                        Dom.mensaje(mensaje, error.mensaje || "No se pudo aprobar la solicitud.", "error");
                    });
            }, { clases: ["btnActualizar"] });

            const botonRechazar = Dom.boton("Rechazar", () => {
                API.post("solicitudesregistro.php?accion=rechazar", {
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

        API.get("solicitudesregistro.php", parametros)
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
