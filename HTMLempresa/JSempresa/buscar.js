/**
 * A.P.P.E. — Búsqueda y filtros dinámicos.
 *
 * Requisito funcional RF-12 · Caso de uso CU-04 (paso 2 del flujo principal).
 */
(function () {
    "use strict";

    API.configurar("./PHPempresa/");

    const campoBusqueda = document.getElementById("busquedaGeneral");
    const selectorTipo = document.getElementById("tipoBusqueda");
    const contenedor = document.getElementById("resultadosBusqueda");
    const mensaje = document.getElementById("busquedaMensaje");

    let temporizador = null;

    function pintar(resultados) {
        if (!contenedor) {
            return;
        }

        Dom.limpiar(contenedor);

        if (resultados.length === 0) {
            contenedor.appendChild(Dom.texto("p", "No se encontraron registros.", { clases: ["sin-datos"] }));
            return;
        }

        resultados.forEach((resultado) => {
            const item = Dom.crear("li", { clases: ["fila-panel"] });
            item.appendChild(Dom.texto("span", resultado.etiqueta, { clases: ["fila-titulo"] }));
            item.appendChild(Dom.texto("span", resultado.detalle, { clases: ["fila-detalle"] }));
            item.appendChild(Dom.texto("span", "#" + resultado.id, { clases: ["fila-detalle"] }));
            contenedor.appendChild(item);
        });
    }

    function buscar() {
        if (!contenedor) {
            return;
        }

        const parametros = { tipo: selectorTipo ? selectorTipo.value : "clientes" };
        const texto = campoBusqueda ? campoBusqueda.value.trim() : "";

        if (texto !== "") {
            parametros.q = texto;
        }

        API.get("buscar.php", parametros)
            .then((datos) => pintar(datos.resultados || []))
            .catch((error) => {
                Dom.mensaje(mensaje, error.mensaje || "No se pudo realizar la búsqueda.", "error");
            });
    }

    if (campoBusqueda) {
        campoBusqueda.addEventListener("input", () => {
            clearTimeout(temporizador);
            temporizador = setTimeout(buscar, 250);
        });
    }

    if (selectorTipo) {
        selectorTipo.addEventListener("change", buscar);
    }

    buscar();
})();
