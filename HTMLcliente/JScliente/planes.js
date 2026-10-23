/**
 * A.P.P.E. — Listado de planes.
 *
 * Muestra los dos planes fijos de la interfaz y suma los planes que cargó el
 * entrenador (RF-07). Todo el contenido se inserta con `textContent`.
 */
(function () {
    "use strict";

    const lista = document.querySelector(".lista-planes");

    if (!lista) {
        return;
    }

    const PANTALLA_MOVIL = 640;

    function cambiar(fila, abrir) {
        const detalle = fila.querySelector(".detalle-plan");
        const boton = fila.querySelector(".caja-plan");

        // La hoja de estilo muestra el detalle cuando la fila tiene la clase
        // «abierta».
        fila.classList.toggle("abierta", abrir);

        if (detalle) {
            detalle.setAttribute("aria-hidden", abrir ? "false" : "true");
        }

        if (boton) {
            boton.setAttribute("aria-expanded", abrir ? "true" : "false");
        }
    }

    /**
     * Construye la fila de un plan creado por el entrenador.
     *
     * La estructura es exactamente la misma que la de los planes fijos del
     * HTML: una fila `.fila-plan` con la tarjeta `.caja-plan`, la flecha y el
     * panel `.detalle-plan` donde están los precios.
     */
    function crearFilaPlan(plan) {
        const idPanel = "planPanel-" + plan.id_plan;

        const fila = Dom.crear("div", { clases: ["fila-plan", "abierta"] });

        // --- Tarjeta: imagen de fondo y nombre ---------------------------
        const tarjeta = Dom.crear("a", {
            clases: ["caja-plan"],
            dataset: { plan: String(plan.id_plan) },
            atributos: {
                href: "./plan.html?id=" + encodeURIComponent(plan.id_plan),
                "aria-expanded": "true",
                "aria-controls": idPanel,
            },
        });

        const imagen = Dom.crear("img", {
            atributos: { src: PlanesComun.urlImagenPlan(plan), alt: plan.nombre || "Plan" },
        });
        imagen.addEventListener("error", () => {
            imagen.setAttribute("src", "./imagenes/plan basico.jpg");
        });

        tarjeta.appendChild(imagen);
        tarjeta.appendChild(Dom.texto("span", plan.nombre || "Plan", { clases: ["nombre-plan"] }));

        // --- Flecha --------------------------------------------------------
        const flecha = Dom.crear("div", { clases: ["flecha-plan"], atributos: { "aria-hidden": "true" } });
        flecha.textContent = "→";

        // --- Panel de detalle ----------------------------------------------
        const detalle = Dom.crear("div", { clases: ["detalle-plan"] });
        detalle.id = idPanel;
        detalle.setAttribute("aria-hidden", "false");

        if (plan.descripcion) {
            detalle.appendChild(Dom.texto("p", plan.descripcion, { clases: ["descripcion-plan"] }));
        }

        if (plan.beneficio) {
            detalle.appendChild(Dom.texto("p", plan.beneficio, { clases: ["beneficio-plan"] }));
        }

        const grilla = Dom.crear("div", { clases: ["grilla-detalle"] });
        PlanesComun.crearBotonesTiempo(plan, grilla);
        detalle.appendChild(grilla);

        // --- Se arma la fila -------------------------------------------------
        fila.appendChild(tarjeta);
        fila.appendChild(flecha);
        fila.appendChild(detalle);

        return fila;
    }

    function mostrar() {
        PlanesComun.cargarPlanesServidor().then((planes) => {
            planes.forEach((plan) => {
                if (!plan || !plan.id_plan) {
                    return;
                }

                lista.appendChild(crearFilaPlan(plan));
            });
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", mostrar);
    } else {
        mostrar();
    }
})();
