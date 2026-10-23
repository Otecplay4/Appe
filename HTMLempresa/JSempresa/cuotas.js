/**
 * A.P.P.E. — Gestión de cuotas y vencimientos.
 *
 * Requisito funcional RF-10 · Caso de uso CU-04 «Consultar Información y
 * Estado de Cuenta».
 */
(function () {
    "use strict";

    API.configurar("./PHPempresa/");

    const formulario = document.getElementById("formularioCuota");
    const cuerpoTabla = document.getElementById("cuerpoTablaCuotas");
    const mensaje = document.getElementById("cuotaMensaje");
    const selectorEstado = document.getElementById("filtroEstadoCuota");
    const selectorProximos = document.getElementById("filtroProximos");
    const contenedorResumen = document.getElementById("cuotaResumen");
    const selectorCliente = document.getElementById("cuotaCliente");

    function pintarResumen(datos) {
        if (!contenedorResumen) {
            return;
        }

        Dom.limpiar(contenedorResumen);

        const resumen = datos.resumen || {};

        [
            ["Cobrado", Dom.precio(resumen.cobrado || 0)],
            ["Pendiente", Dom.precio(resumen.pendiente || 0)],
            ["Vencido", Dom.precio(resumen.vencido || 0)],
            ["Vencen en 7 días", resumen.proximos || 0],
        ].forEach(([titulo, valor]) => {
            const caja = Dom.crear("article", { clases: ["indicador"] });
            caja.appendChild(Dom.texto("span", titulo, { clases: ["indicador-titulo"] }));
            caja.appendChild(Dom.texto("strong", valor, { clases: ["indicador-valor"] }));
            contenedorResumen.appendChild(caja);
        });
    }

    function pintarCuotas(cuotas) {
        if (!cuerpoTabla) {
            return;
        }

        Dom.limpiar(cuerpoTabla);

        if (cuotas.length === 0) {
            const fila = Dom.crear("tr");
            fila.appendChild(Dom.crear("td", { texto: "No hay cuotas para mostrar.", atributos: { colspan: "7" } }));
            cuerpoTabla.appendChild(fila);
            return;
        }

        cuotas.forEach((cuota) => {
            const fila = Dom.crear("tr");

            fila.appendChild(Dom.crear("td", { texto: String(cuota.id_cuota) }));
            fila.appendChild(Dom.crear("td", { texto: cuota.cliente }));
            fila.appendChild(Dom.crear("td", { texto: cuota.concepto }));
            fila.appendChild(Dom.crear("td", { texto: Dom.precio(cuota.monto) }));
            fila.appendChild(Dom.crear("td", { texto: Dom.fecha(cuota.fecha_vencimiento) }));
            fila.appendChild(Dom.crear("td", { texto: cuota.estado }));

            const celdaAcciones = Dom.crear("td", { clases: ["acciones"] });

            const botonPagar = Dom.boton("Marcar pagada", () => {
                API.post("cuotas.php?accion=actualizar", { id: cuota.id_cuota, estado: "pagada" })
                    .then(() => {
                        Dom.mensaje(mensaje, "Cuota marcada como pagada.", "exito");
                        cargar();
                    })
                    .catch((error) => {
                        Dom.mensaje(mensaje, error.mensaje || "No se pudo actualizar la cuota.", "error");
                    });
            }, { clases: ["btnActualizar"] });

            const botonEliminar = Dom.boton("Eliminar", () => {
                if (!window.confirm("¿Eliminar la cuota?")) {
                    return;
                }

                API.post("cuotas.php?accion=eliminar", { id: cuota.id_cuota })
                    .then(() => {
                        Dom.mensaje(mensaje, "La cuota fue eliminada.", "exito");
                        cargar();
                    })
                    .catch((error) => {
                        Dom.mensaje(mensaje, error.mensaje || "No se pudo eliminar la cuota.", "error");
                    });
            }, { clases: ["btnEliminar"] });

            celdaAcciones.appendChild(botonPagar);
            celdaAcciones.appendChild(botonEliminar);
            fila.appendChild(celdaAcciones);

            cuerpoTabla.appendChild(fila);
        });
    }

    function cargar() {
        const parametros = {};

        if (selectorEstado && selectorEstado.value) {
            parametros.estado = selectorEstado.value;
        }

        if (selectorProximos && selectorProximos.value) {
            parametros.proximos = selectorProximos.value;
        }

        Promise.all([
            API.get("cuotas.php", Object.assign({ accion: "listar" }, parametros)),
            API.get("cuotas.php", { accion: "resumen" }),
        ])
            .then((resultados) => {
                pintarCuotas(resultados[0].cuotas || []);
                pintarResumen(resultados[1]);
            })
            .catch((error) => {
                Dom.mensaje(mensaje, error.mensaje || "No se pudieron cargar las cuotas.", "error");
            });
    }

    function cargarClientes() {
        if (!selectorCliente) {
            return;
        }

        API.get("buscarcliente.php", { limite: 100 })
            .then((datos) => {
                Dom.limpiar(selectorCliente);
                selectorCliente.appendChild(Dom.crear("option", { texto: "Seleccioná un cliente", atributos: { value: "" } }));

                (datos.clientes || []).forEach((cliente) => {
                    selectorCliente.appendChild(Dom.crear("option", {
                        texto: cliente.nombre_completo,
                        atributos: { value: String(cliente.id_cliente) },
                    }));
                });
            })
            .catch(() => {});
    }

    if (formulario) {
        formulario.addEventListener("submit", (evento) => {
            evento.preventDefault();

            const datos = new FormData(formulario);

            if (!datos.get("id_cliente")) {
                Dom.mensaje(mensaje, "Seleccioná el cliente de la cuota.", "error");
                return;
            }

            API.postForm("cuotas.php?accion=crear", datos)
                .then(() => {
                    Dom.mensaje(mensaje, "Cuota registrada correctamente.", "exito");
                    formulario.reset();
                    cargar();
                })
                .catch((error) => {
                    Dom.mensaje(mensaje, error.mensaje || "No se pudo registrar la cuota.", "error");
                });
        });
    }

    if (selectorEstado) {
        selectorEstado.addEventListener("change", cargar);
    }

    if (selectorProximos) {
        selectorProximos.addEventListener("change", cargar);
    }

    cargarClientes();
    cargar();
})();
