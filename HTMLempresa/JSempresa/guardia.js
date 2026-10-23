/**
 * A.P.P.E. — Guarda de acceso de las páginas de administración.
 *
 * Si no hay sesión de entrenador o administrador, redirige al login.
 * La comprobación la hace el servidor (`check.php`), que verifica identidad Y
 * rol (hallazgo 11): una sesión de cliente ya no alcanza.
 *
 * Falla cerrado: ante cualquier error de red se redirige al login.
 */
(function () {
    "use strict";

    API.configurar("./PHPempresa/");

    function redirigir() {
        window.location.replace("./interfaz2.html");
    }

    API.get("check.php")
        .then((datos) => {
            if (!datos || !datos.exito) {
                redirigir();
                return;
            }

            // Se informa el rol para adaptar la interfaz (por ejemplo, la
            // sección de alta de cuentas solo existe para el Administrador).
            document.dispatchEvent(new CustomEvent("appe:sesion", { detail: datos }));
        })
        .catch(redirigir);
})();
