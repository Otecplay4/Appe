/**
 * A.P.P.E. — Detalle de un plan (vista móvil).
 */
(function () {
    "use strict";

    const detalle = document.getElementById("detallePlan");

    if (!detalle) {
        return;
    }

    function mostrarNoEncontrado() {
        Dom.limpiar(detalle);
        detalle.appendChild(Dom.texto("p", "No se encontró el plan solicitado.", { clases: ["sin-plan"] }));
    }

    function mostrarPlan(plan) {
        Dom.limpiar(detalle);

        const cabecera = Dom.crear("div", { clases: ["cabecera-plan"] });
        cabecera.appendChild(Dom.texto("span", plan.tipo_de_plan || "Plan", { clases: ["tipo-plan"] }));
        cabecera.appendChild(Dom.texto("h2", plan.nombre || "Plan", { clases: ["titulo-planes"] }));

        if (plan.descripcion) {
            cabecera.appendChild(Dom.texto("p", plan.descripcion, { clases: ["descripcion-cabecera"] }));
        }

        const imagen = Dom.crear("img", {
            clases: ["imagen-plan"],
            atributos: { src: PlanesComun.urlImagenPlan(plan), alt: plan.nombre || "Plan" },
        });
        imagen.addEventListener("error", () => {
            imagen.setAttribute("src", "./imagenes/plan basico.jpg");
        });
        cabecera.appendChild(imagen);

        const grilla = Dom.crear("div", { clases: ["grilla-tiempos"] });
        PlanesComun.crearBotonesTiempo(plan, grilla);
        cabecera.appendChild(grilla);

        detalle.appendChild(cabecera);
    }

    function iniciar() {
        const parametros = new URLSearchParams(window.location.search);
        const id = parametros.get("id");

        if (!id || !/^\d+$/.test(id)) {
            mostrarNoEncontrado();
            return;
        }

        PlanesComun.cargarPlanServidor(id).then((plan) => {
            if (!plan || !plan.id_plan) {
                mostrarNoEncontrado();
                return;
            }

            mostrarPlan(plan);
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", iniciar);
    } else {
        iniciar();
    }
})();
