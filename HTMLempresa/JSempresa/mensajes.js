/**
 * A.P.P.E. — Bandeja de mensajes de contacto.
 *
 * Requisito funcional RF-15 · Caso de uso CU-07.
 */
(function () {
    "use strict";

    API.configurar("./PHPempresa/");

    const contenedor = document.getElementById("mensajesContainer");
    const mensaje = document.getElementById("mensajeInfo");
    const selectorFiltro = document.getElementById("filtroMensajes");

    function pintarMensaje(item) {
        if (!contenedor) {
            return;
        }

        const tarjeta = Dom.crear("article", { clases: ["tarjeta"] });
        tarjeta.appendChild(Dom.texto("h3", item.nombre, { clases: ["nombre-plan"] }));
        tarjeta.appendChild(Dom.texto("p", item.correo + (item.telefono ? " · " + item.telefono : ""), { clases: ["descripcion-plan"] }));
        tarjeta.appendChild(Dom.texto("p", item.mensaje, { clases: ["beneficio-plan"] }));
        tarjeta.appendChild(Dom.texto("span", Dom.fecha(item.fecha), { clases: ["estado-plan"] }));
        tarjeta.appendChild(Dom.texto("span", Number(item.leido) === 1 ? "Leído" : "No leído", { clases: ["estado-plan"] }));

        const acciones = Dom.crear("div", { clases: ["acciones"] });

        const botonAlternar = Dom.boton(Number(item.leido) === 1 ? "Marcar como no leído" : "Marcar como leído", () => {
            const nuevo = Number(item.leido) === 1 ? 0 : 1;

            API.post("mensajes.php?accion=marcar", { id: item.id_mensaje, leido: nuevo })
                .then(cargar)
                .catch((error) => {
                    Dom.mensaje(mensaje, error.mensaje || "No se pudo actualizar el mensaje.", "error");
                });
        }, { clases: ["btnActualizar"] });

        acciones.appendChild(botonAlternar);
        tarjeta.appendChild(acciones);

        contenedor.appendChild(tarjeta);
    }

    function cargar() {
        if (!contenedor) {
            return;
        }

        Dom.limpiar(contenedor);

        const parametros = { accion: "listar" };

        if (selectorFiltro && selectorFiltro.value) {
            parametros.filtro = selectorFiltro.value;
        }

        API.get("mensajes.php", parametros)
            .then((datos) => {
                const lista = datos.mensajes || [];

                if (lista.length === 0) {
                    contenedor.appendChild(Dom.texto("p", "No hay mensajes en la bandeja.", { clases: ["sin-datos"] }));
                    return;
                }

                lista.forEach(pintarMensaje);
            })
            .catch((error) => {
                Dom.mensaje(mensaje, error.mensaje || "No se pudo cargar la bandeja de mensajes.", "error");
            });
    }

    if (selectorFiltro) {
        selectorFiltro.addEventListener("change", cargar);
    }

    cargar();
})();
