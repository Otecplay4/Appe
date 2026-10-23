/**
 * A.P.P.E. — Perfil del cliente.
 *
 * Caso de uso CU-04 «Consultar Información y Estado de Cuenta» (actor Cliente)
 * y RF-14 «solicitar el cambio de rutina desde el perfil del cliente».
 */
(function () {
    "use strict";

    API.configurar("./PHPcliente/");

    const contenedor = document.getElementById("perfilContenido");
    const mensaje = document.getElementById("perfilMensaje");
    const formulario = document.getElementById("formularioSolicitud");
    const campoMotivo = document.getElementById("motivoCambio");

    function pintar(datos) {
        if (!contenedor) {
            return;
        }

        Dom.limpiar(contenedor);

        const persona = datos.datos || {};

        const ficha = Dom.crear("section", { clases: ["perfil-ficha"] });
        ficha.appendChild(Dom.texto("h3", "Datos personales", { clases: ["perfil-titulo"] }));

        const lista = Dom.crear("ul", { clases: ["perfil-lista"] });
        [
            ["Nombre", persona.nombre],
            ["Apellido", persona.apellido],
            ["Correo", persona.correo],
            ["Teléfono", persona.telefono || "-"],
            ["Cliente desde", Dom.fecha(persona.fecha_alta)],
        ].forEach(([etiqueta, valor]) => {
            const item = Dom.crear("li");
            item.appendChild(Dom.texto("span", etiqueta + ": ", { clases: ["perfil-etiqueta"] }));
            item.appendChild(Dom.texto("span", valor === null || valor === undefined ? "-" : String(valor)));
            lista.appendChild(item);
        });
        ficha.appendChild(lista);
        contenedor.appendChild(ficha);

        // Plan vigente
        const plan = datos.plan;
        const seccionPlan = Dom.crear("section", { clases: ["perfil-ficha"] });
        seccionPlan.appendChild(Dom.texto("h3", "Mi plan", { clases: ["perfil-titulo"] }));

        if (plan) {
            const listaPlan = Dom.crear("ul", { clases: ["perfil-lista"] });
            listaPlan.appendChild(Dom.texto("li", "Plan: " + plan.nombre));
            listaPlan.appendChild(Dom.texto("li", "Beneficio: " + (plan.beneficio || "-")));
            listaPlan.appendChild(Dom.texto("li", "Precio: " + Dom.precio(plan.precio)));
            listaPlan.appendChild(Dom.texto("li", "Vigencia: " + (plan.fecha_inicio || "-") + " a " + (plan.fecha_fin || "-")));
            seccionPlan.appendChild(listaPlan);
        } else {
            seccionPlan.appendChild(Dom.texto("p", "Todavía no tenés un plan asignado.", { clases: ["perfil-vacio"] }));
        }
        contenedor.appendChild(seccionPlan);

        // Rutina
        const rutina = datos.rutina;
        const seccionRutina = Dom.crear("section", { clases: ["perfil-ficha"] });
        seccionRutina.appendChild(Dom.texto("h3", "Mi rutina", { clases: ["perfil-titulo"] }));

        if (rutina) {
            seccionRutina.appendChild(Dom.texto("p", rutina.nombre + " (" + rutina.estado + ")"));
            const enlace = Dom.crear("a", {
                texto: "Ver la rutina completa",
                clases: ["perfil-enlace"],
                atributos: { href: "./mirutina.html" },
            });
            seccionRutina.appendChild(enlace);
        } else {
            seccionRutina.appendChild(Dom.texto("p", "Todavía no tenés una rutina asignada.", { clases: ["perfil-vacio"] }));
        }
        contenedor.appendChild(seccionRutina);

        // Estado de cuenta
        const cuenta = datos.estado_cuenta || {};
        const seccionCuenta = Dom.crear("section", { clases: ["perfil-ficha"] });
        seccionCuenta.appendChild(Dom.texto("h3", "Estado de cuenta", { clases: ["perfil-titulo"] }));
        seccionCuenta.appendChild(Dom.texto("p", "Total pendiente: " + Dom.precio(cuenta.total_pendiente || 0)));
        seccionCuenta.appendChild(Dom.texto("p", "Próximo vencimiento: " + Dom.fecha(cuenta.proximo_vencimiento)));

        const cuotas = cuenta.cuotas || [];
        if (cuotas.length > 0) {
            const tabla = Dom.crear("table", { clases: ["perfil-tabla"] });
            const cabecera = Dom.crear("thead");
            const filaCabecera = Dom.crear("tr");
            ["Concepto", "Monto", "Vencimiento", "Estado"].forEach((titulo) => {
                filaCabecera.appendChild(Dom.crear("th", { texto: titulo }));
            });
            cabecera.appendChild(filaCabecera);
            tabla.appendChild(cabecera);

            const cuerpo = Dom.crear("tbody");
            cuotas.forEach((cuota) => {
                const fila = Dom.crear("tr");
                fila.appendChild(Dom.crear("td", { texto: String(cuota.concepto) }));
                fila.appendChild(Dom.crear("td", { texto: Dom.precio(cuota.monto) }));
                fila.appendChild(Dom.crear("td", { texto: Dom.fecha(cuota.fecha_vencimiento) }));
                fila.appendChild(Dom.crear("td", { texto: String(cuota.estado) }));
                cuerpo.appendChild(fila);
            });
            tabla.appendChild(cuerpo);
            seccionCuenta.appendChild(tabla);
        }
        contenedor.appendChild(seccionCuenta);

        // Solicitudes de cambio de rutina
        const solicitudes = datos.solicitudes || [];
        if (solicitudes.length > 0) {
            const seccion = Dom.crear("section", { clases: ["perfil-ficha"] });
            seccion.appendChild(Dom.texto("h3", "Mis solicitudes de cambio", { clases: ["perfil-titulo"] }));

            solicitudes.forEach((solicitud) => {
                const item = Dom.crear("article", { clases: ["solicitud-card"] });
                item.appendChild(Dom.texto("p", solicitud.mensaje, { clases: ["solicitud-motivo"] }));
                item.appendChild(Dom.texto("span", "Estado: " + solicitud.estado, { clases: ["solicitud-estado"] }));
                item.appendChild(Dom.texto("span", Dom.fecha(solicitud.fecha), { clases: ["solicitud-fecha"] }));

                if (solicitud.respuesta) {
                    item.appendChild(Dom.texto("p", "Respuesta: " + solicitud.respuesta, { clases: ["solicitud-respuesta"] }));
                }

                seccion.appendChild(item);
            });

            contenedor.appendChild(seccion);
        }
    }

    function cargar() {
        API.get("miperfil.php")
            .then(pintar)
            .catch((error) => {
                // 401: no hay sesión. 403: la sesión no tiene el rol de socio.
                const sinAcceso = error.estado === 401 || error.estado === 403;

                Dom.mensaje(
                    mensaje,
                    sinAcceso
                        ? "Tu sesión actual no tiene acceso a esta información."
                        : (error.mensaje || "No se pudo cargar el perfil."),
                    "error"
                );
            });
    }

    if (formulario) {
        formulario.addEventListener("submit", (evento) => {
            evento.preventDefault();

            const motivo = campoMotivo ? campoMotivo.value.trim() : "";

            if (motivo.length < 5) {
                Dom.mensaje(mensaje, "Contá brevemente el motivo del cambio.", "error");
                return;
            }

            API.post("solicitarrutina.php", { mensaje: motivo })
                .then(() => {
                    Dom.mensaje(mensaje, "Tu solicitud fue registrada. El entrenador la revisará.", "exito");
                    if (campoMotivo) {
                        campoMotivo.value = "";
                    }
                    cargar();
                })
                .catch((error) => {
                    Dom.mensaje(mensaje, error.mensaje || "No se pudo registrar la solicitud.", "error");
                });
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", cargar);
    } else {
        cargar();
    }
})();
