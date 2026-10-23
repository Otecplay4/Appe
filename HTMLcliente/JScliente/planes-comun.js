/**
 * A.P.P.E. — Utilidades compartidas de los planes.
 *
 * Carga los planes que crea el entrenador (RF-07) y arma los botones de
 * período con sus precios.
 */
const PlanesComun = (() => {
    "use strict";

    API.configurar("./PHPcliente/");

    const PERIODOS = [
        { clave: "semanal", etiqueta: "Semanal" },
        { clave: "mensual", etiqueta: "Mensual" },
        { clave: "trimestral", etiqueta: "Trimestral" },
        { clave: "anual", etiqueta: "Anual" },
    ];

    function formatearPrecio(valor) {
        const numero = Number(valor);

        if (!isFinite(numero)) {
            return "-";
        }

        return "$ " + numero.toLocaleString("es-UY", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function urlImagenPlan(plan) {
        // Si el plan no tiene imagen cargada se usa la imagen local por
        // defecto: así el navegador no pide un recurso inexistente.
        if (!plan || !plan.id_plan || !plan.extension) {
            return "./imagenes/plan basico.jpg";
        }

        return "./PHPcliente/imagenplan.php?id=" + encodeURIComponent(plan.id_plan);
    }

    /**
     * Pide planes al servidor.
     */
    function pedirPlanes(parametros) {
        return API.get("planes.php", parametros || {})
            .then((datos) => {
                const lista = datos.planes || [];
                return Array.isArray(lista) ? lista : [lista];
            })
            .catch(() => []);
    }

    /**
     * Carga todos los planes publicados.
     */
    function cargarPlanesServidor() {
        return pedirPlanes({});
    }

    /**
     * Carga un plan concreto.
     */
    function cargarPlanServidor(id) {
        return pedirPlanes({ id: id }).then((lista) => (lista.length ? lista[0] : null));
    }

    /**
     * Crea los botones de período con su precio.
     */
    function crearBotonesTiempo(plan, contenedor) {
        const precios = plan.precios || {};
        const disponibles = PERIODOS.filter((periodo) => {
            const valor = precios[periodo.clave];
            return valor !== undefined && valor !== null && Number(valor) > 0;
        });

        disponibles.forEach((periodo) => {
            const caja = Dom.crear("div", { clases: ["tiempo-plan"] });

            const precio = Dom.texto("span", formatearPrecio(precios[periodo.clave]), { clases: ["precio-tiempo"] });
            const boton = Dom.crear("button", {
                texto: periodo.etiqueta,
                clases: ["boton-tiempo"],
                dataset: {
                    periodo: periodo.clave,
                    plan: String(plan.id_plan),
                    precio: String(precios[periodo.clave]),
                },
                atributos: { type: "button" },
            });

            caja.appendChild(boton);
            caja.appendChild(precio);
            contenedor.appendChild(caja);
        });

        return disponibles.length;
    }

    return {
        PERIODOS: PERIODOS,
        formatearPrecio: formatearPrecio,
        urlImagenPlan: urlImagenPlan,
        pedirPlanes: pedirPlanes,
        cargarPlanesServidor: cargarPlanesServidor,
        cargarPlanServidor: cargarPlanServidor,
        crearBotonesTiempo: crearBotonesTiempo,
    };
})();
