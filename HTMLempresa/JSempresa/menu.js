/**
 * A.P.P.E. — Menú de navegación de la administración (teléfono).
 *
 * Abre y cierra el panel lateral que muestra el botón de hamburguesa.
 * En tablet y escritorio el botón está oculto y el menú siempre es visible,
 * de modo que este script no tiene efecto visible allí.
 */
(function () {
    "use strict";

    const boton = document.getElementById("menuToggle");
    const menu = document.getElementById("navMenu");

    if (!boton || !menu) {
        return;
    }

    function establecer(abierto) {
        boton.classList.toggle("activo", abierto);
        menu.classList.toggle("abierto", abierto);
        boton.setAttribute("aria-expanded", abierto ? "true" : "false");
        boton.setAttribute("aria-label", abierto ? "Cerrar menú" : "Abrir menú");
    }

    boton.addEventListener("click", function () {
        establecer(!menu.classList.contains("abierto"));
    });

    menu.querySelectorAll(".nav-boton").forEach(function (enlace) {
        enlace.addEventListener("click", function () {
            establecer(false);
        });
    });

    // Tocar fuera del panel o pulsar Escape también lo cierra.
    document.addEventListener("click", function (evento) {
        if (menu.classList.contains("abierto") && !menu.contains(evento.target) && !boton.contains(evento.target)) {
            establecer(false);
        }
    });

    document.addEventListener("keydown", function (evento) {
        if (evento.key === "Escape" && menu.classList.contains("abierto")) {
            establecer(false);
            boton.focus();
        }
    });

    // Si la pantalla crece (por ejemplo, al girar la tablet) se restablece.
    window.addEventListener("resize", function () {
        if (window.innerWidth > 760) {
            establecer(false);
        }
    });
})();
