/**
 * A.P.P.E. — Guarda de acceso de las páginas de administración.
 *
 * Si no hay sesión de entrenador o administrador, redirige al login.
 * La comprobación la hace el servidor (`check.php`), que verifica identidad Y
 * rol.
 *
 * Excepciones:
 *   - `crearentrenador.html` es pública: desde ahí cualquiera puede ENVIAR una
 *     solicitud de registro (no crea cuentas, solo el Administrador las aprueba).
 *   - `solicitudesregistro.html` es solo del Administrador: un entrenador es
 *     devuelto al inicio. (El servidor lo exige igualmente.)
 *
 * Falla cerrado: ante cualquier error de red se redirige al login, salvo en la
 * página pública de solicitud.
 */
(function () {
    "use strict";

    API.configurar("./PHPempresa/");

    const ruta = window.location.pathname.toLowerCase();
    const esPaginaAlta = ruta.includes("crearentrenador");
    const esPaginaSoloAdmin = ruta.includes("solicitudesregistro");

    function redirigir() {
        window.location.replace("./interfaz2.html");
    }

    API.get("check.php")
        .then((datos) => {
            // La página de solicitud es pública: se informa la sesión (si la
            // hay) y no se redirige.
            if (esPaginaAlta) {
                document.dispatchEvent(new CustomEvent("appe:sesion", { detail: datos }));
                return;
            }

            if (!datos || !datos.exito) {
                redirigir();
                return;
            }

            if (esPaginaSoloAdmin && datos.rol !== "admin") {
                window.location.replace("./inicio-empresa.html");
                return;
            }

            // Se informa el rol para adaptar la interfaz (por ejemplo, la
            // sección de alta de cuentas solo existe para el Administrador).
            document.dispatchEvent(new CustomEvent("appe:sesion", { detail: datos }));
        })
        .catch(() => {
            if (!esPaginaAlta) {
                redirigir();
            }
        });
})();
