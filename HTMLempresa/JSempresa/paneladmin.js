/**
 * A.P.P.E. — Panel centralizado de administración.
 *
 * Requisito funcional RF-11 · Caso de uso CU-04.
 */
(function () {
    "use strict";

    API.configurar("./PHPempresa/");

    const contenedor = document.getElementById("panelIndicadores");
    const ultimos = document.getElementById("panelUltimosClientes");
    const vencimientos = document.getElementById("panelVencimientos");
    const solicitudes = document.getElementById("panelSolicitudes");
    const mensaje = document.getElementById("panelMensaje");

    function tarjeta(titulo, valor, detalle) {
        const caja = Dom.crear("article", { clases: ["indicador"] });
        caja.appendChild(Dom.texto("span", titulo, { clases: ["indicador-titulo"] }));
        caja.appendChild(Dom.texto("strong", valor, { clases: ["indicador-valor"] }));

        if (detalle) {
            caja.appendChild(Dom.texto("span", detalle, { clases: ["indicador-detalle"] }));
        }

        return caja;
    }

    function pintarIndicadores(datos) {
        if (!contenedor) {
            return;
        }

        Dom.limpiar(contenedor);

        const indicadores = datos.indicadores || {};
        const clientes = indicadores.clientes || {};
        const facturacion = indicadores.facturacion || {};
        const pendientes = indicadores.pendientes || {};
        const productos = indicadores.productos || {};
        const rutinas = indicadores.rutinas || {};
        const planes = indicadores.planes || {};
        const proveedores = indicadores.proveedores || {};

        [
            ["Clientes activos", clientes.activos || 0, (clientes.sin_plan || 0) + " sin plan · " + (clientes.sin_rutina || 0) + " sin rutina"],
            ["Rutinas activas", rutinas.activas || 0, (rutinas.total || 0) + " en total"],
            ["Productos publicados", productos.activos || 0, (productos.unidades || 0) + " unidades en stock"],
            ["Planes publicados", planes.activos || 0, (planes.total || 0) + " en total"],
            ["Proveedores", proveedores.total || 0, "registrados"],
            ["Cobrado", Dom.precio(facturacion.cobrado || 0), (facturacion.pendiente || 0) > 0 ? Dom.precio(facturacion.pendiente) + " pendiente" : "sin saldo pendiente"],
            ["Vencido", Dom.precio(facturacion.vencido || 0), (facturacion.vencen_pronto || 0) + " vencen en 7 días"],
            ["Requieren atención", (pendientes.solicitudes || 0) + (pendientes.mensajes || 0), (pendientes.solicitudes || 0) + " solicitudes · " + (pendientes.mensajes || 0) + " mensajes"],
        ].forEach(([titulo, valor, detalle]) => {
            contenedor.appendChild(tarjeta(titulo, valor, detalle));
        });
    }

    function pintarUltimos(clientes) {
        if (!ultimos) {
            return;
        }

        Dom.limpiar(ultimos);

        if (!clientes || clientes.length === 0) {
            ultimos.appendChild(Dom.texto("p", "Todavía no hay clientes registrados.", { clases: ["sin-datos"] }));
            return;
        }

        clientes.forEach((cliente) => {
            const item = Dom.crear("li", { clases: ["fila-panel"] });
            item.appendChild(Dom.texto("span", cliente.nombre + " " + cliente.apellido, { clases: ["fila-titulo"] }));
            item.appendChild(Dom.texto("span", cliente.correo, { clases: ["fila-detalle"] }));
            item.appendChild(Dom.texto("span", Dom.fecha(cliente.fecha_alta), { clases: ["fila-detalle"] }));
            ultimos.appendChild(item);
        });
    }

    function pintarVencimientos(lista) {
        if (!vencimientos) {
            return;
        }

        Dom.limpiar(vencimientos);

        if (!lista || lista.length === 0) {
            vencimientos.appendChild(Dom.texto("p", "No hay cuotas pendientes ni vencidas.", { clases: ["sin-datos"] }));
            return;
        }

        lista.forEach((cuota) => {
            const item = Dom.crear("li", { clases: ["fila-panel"] });
            item.appendChild(Dom.texto("span", cuota.nombre + " " + cuota.apellido, { clases: ["fila-titulo"] }));
            item.appendChild(Dom.texto("span", cuota.concepto, { clases: ["fila-detalle"] }));
            item.appendChild(Dom.texto("span", Dom.precio(cuota.monto), { clases: ["fila-detalle"] }));
            item.appendChild(Dom.texto("span", "Vence: " + Dom.fecha(cuota.fecha_vencimiento), { clases: ["fila-detalle"] }));
            vencimientos.appendChild(item);
        });
    }

    function pintarSolicitudes(lista) {
        if (!solicitudes) {
            return;
        }

        Dom.limpiar(solicitudes);

        if (!lista || lista.length === 0) {
            solicitudes.appendChild(Dom.texto("p", "No hay solicitudes pendientes.", { clases: ["sin-datos"] }));
            return;
        }

        lista.forEach((solicitud) => {
            const item = Dom.crear("li", { clases: ["fila-panel"] });
            item.appendChild(Dom.texto("span", solicitud.nombre + " " + solicitud.apellido, { clases: ["fila-titulo"] }));
            item.appendChild(Dom.texto("span", solicitud.mensaje, { clases: ["fila-detalle"] }));
            item.appendChild(Dom.texto("span", Dom.fecha(solicitud.fecha), { clases: ["fila-detalle"] }));
            solicitudes.appendChild(item);
        });
    }

    function cargar() {
        API.get("panel.php")
            .then((datos) => {
                pintarIndicadores(datos);
                pintarUltimos(datos.ultimos_clientes);
                pintarVencimientos(datos.proximos_vencimientos);
                pintarSolicitudes(datos.cambios_pendientes);
            })
            .catch((error) => {
                Dom.mensaje(mensaje, error.mensaje || "No se pudo cargar el panel.", "error");
            });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", cargar);
    } else {
        cargar();
    }
})();
