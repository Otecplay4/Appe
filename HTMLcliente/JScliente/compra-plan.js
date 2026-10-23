/**
 * A.P.P.E. — Compra de planes (simulada).
 *
 * La compra es una SIMULACIÓN: el proyecto no integra ningún medio de pago.
 * Lo que se controla aquí es el acceso (RF-04): solo un cliente autenticado
 * puede "contratar" un plan.
 */
(function () {
    "use strict";

    API.configurar("./PHPcliente/");

    function nombrePlan(elemento) {
        if (!elemento) {
            return "el plan";
        }

        const contenedor = elemento.closest(".fila-plan, .plan-panel, .caja-plan, .detalle-plan") || elemento.parentElement;
        const etiqueta = contenedor ? contenedor.querySelector(".nombre-plan, .titulo-planes, h2, h3") : null;

        return etiqueta ? etiqueta.textContent.trim() : "el plan";
    }

    function precioPlan(elemento) {
        const dato = elemento && elemento.getAttribute("data-precio");
        const valor = Number(dato);

        return isFinite(valor) && valor > 0 ? valor : 0;
    }

    function periodoPlan(elemento) {
        const dato = elemento && elemento.getAttribute("data-periodo");
        const etiquetas = { semanal: "Semanal", mensual: "Mensual", trimestral: "Trimestral", anual: "Anual" };

        return etiquetas[dato] || "";
    }

    function activar() {
        document.addEventListener("click", (evento) => {
            const tarjetaTiempo = evento.target.closest(".tiempo-plan");
            const boton = tarjetaTiempo
                ? tarjetaTiempo.querySelector(".boton-tiempo, [data-plan-compra], .boton-comprar-plan")
                : evento.target.closest(".boton-tiempo, [data-plan-compra], .boton-comprar-plan");

            if (!boton) {
                return;
            }

            evento.preventDefault();

            Auth.requerirLogin("Necesitás iniciar sesión para contratar un plan.").then((permitido) => {
                if (!permitido) {
                    return;
                }

                const plan = nombrePlan(boton);
                const periodo = periodoPlan(boton);
                const precio = precioPlan(boton);

                // La compra simulada también deja el plan en el carrito.
                if (typeof Carrito !== "undefined" && precio > 0) {
                    Carrito.agregar({
                        id: "plan-" + (boton.getAttribute("data-plan") || boton.getAttribute("data-plan-compra") || "0"),
                        nombre: plan + (periodo ? " · " + periodo : ""),
                        precio: precio,
                        imagen: "./imagenes/plan basico.jpg",
                        categoria: "planes",
                    }, "planes");
                }

                const detalle = periodo ? " (" + periodo + ")" : "";
                const aviso = "Compra simulada: " + plan + detalle
                    + ".\n\nSe agregó al carrito. El proyecto no integra ningún medio de pago.";

                window.alert(aviso);
            });
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", activar);
    } else {
        activar();
    }
})();
