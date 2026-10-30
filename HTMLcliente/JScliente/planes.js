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

    /**
     * Construye la fila de un plan creado por el entrenador.
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

    /**
     * Los planes fijos de la interfaz (Plan Basico y Plan Familiar) ya están en
     * el HTML con su imagen. Sus precios se sincronizan con los de la base de
     * datos para que lo que se ve en pantalla y en el carrito sea lo mismo que
     * se registra al comprar.
     */
    function sincronizarPlanFijo(plan) {
        const precios = plan.precios || {};

        Object.keys(precios).forEach((periodo) => {
            const valor = Number(precios[periodo]);
            const boton = lista.querySelector(
                '.boton-tiempo[data-plan-compra="' + plan.id_plan + '"][data-periodo="' + periodo + '"]'
            );

            if (!boton || !(valor > 0)) {
                return;
            }

            boton.setAttribute("data-precio", String(valor));

            const etiqueta = boton.parentElement ? boton.parentElement.querySelector(".precio-tiempo") : null;
            if (etiqueta) {
                etiqueta.textContent = PlanesComun.formatearPrecio(valor);
            }
        });
    }

    function mostrar() {
        // Ids de los planes que ya están dibujados en el HTML.
        const fijos = new Set(
            Array.from(lista.querySelectorAll(".caja-plan[data-plan]")).map((tarjeta) => tarjeta.getAttribute("data-plan"))
        );

        PlanesComun.cargarPlanesServidor().then((planes) => {
            planes.forEach((plan) => {
                if (!plan || !plan.id_plan) {
                    return;
                }

                // Un plan fijo no se vuelve a dibujar: solo se actualizan sus precios.
                if (fijos.has(String(plan.id_plan))) {
                    sincronizarPlanFijo(plan);
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
