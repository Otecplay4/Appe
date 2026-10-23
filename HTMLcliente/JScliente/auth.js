/**
 * A.P.P.E. — Guarda de sesión del cliente.
 *
 * Controla la navegación según el estado de autenticación (RF-04, RNF-05).
 * La fuente de verdad es el servidor (`sesion.php`): el navegador no decide
 * si el usuario está autenticado.
 *
 * Fallo cerrado: ante cualquier duda, la opción queda bloqueada.
 */
const Auth = (() => {
    "use strict";

    API.configurar("./PHPcliente/");

    let estado = null;
    let cargando = null;

    /**
     * Consulta el estado de la sesión al servidor (una sola vez por carga).
     */
    function cargar(forzar) {
        if (estado && !forzar) {
            return Promise.resolve(estado);
        }

        if (cargando && !forzar) {
            return cargando;
        }

        cargando = API.get("sesion.php")
            .then((datos) => {
                estado = {
                    logueado: Boolean(datos.logueado),
                    rol: datos.rol || null,
                    usuario: datos.usuario || null,
                };
                return estado;
            })
            .catch(() => {
                estado = { logueado: false, rol: null, usuario: null };
                return estado;
            });

        return cargando;
    }

    /**
     * Indica si hay un cliente autenticado.
     */
    function logueado() {
        return Boolean(estado && estado.logueado);
    }

    /**
     * Exige inicio de sesión; si no hay sesión, redirige al login.
     */
    function requerirLogin(mensaje) {
        return cargar().then((situacion) => {
            if (situacion.logueado) {
                return true;
            }

            if (typeof Swal !== "undefined") {
                Swal.fire({
                    title: "Iniciá sesión",
                    text: mensaje || "Necesitás iniciar sesión para usar esta función.",
                    icon: "info",
                    confirmButtonText: "Iniciar sesión",
                }).then(() => {
                    window.location.href = "./Inicio-Sesion.html";
                });
            } else {
                window.alert(mensaje || "Necesitás iniciar sesión para usar esta función.");
                window.location.href = "./Inicio-Sesion.html";
            }

            return false;
        });
    }

    /**
     * Oculta o bloquea los enlaces que requieren sesión.
     */
    function aplicarGuardaNav() {
        return cargar().then((situacion) => {
            const enlaces = document.querySelectorAll(
                'a[href="mirutina.html"], a[href="perfil.html"], a[href="carrito.html"]'
            );

            enlaces.forEach((enlace) => {
                if (situacion.logueado) {
                    enlace.removeAttribute("aria-disabled");
                    enlace.removeAttribute("data-bloqueado");
                    enlace.style.display = "";
                    return;
                }

                enlace.setAttribute("aria-disabled", "true");
                enlace.setAttribute("data-bloqueado", "true");
                enlace.title = "Necesitás iniciar sesión";
            });

            // El enlace de "Mi perfil" solo existe para el cliente.
            const perfil = document.querySelector('a[href="perfil.html"]');
            if (perfil && !situacion.logueado) {
                perfil.style.display = "none";
            }

            return situacion;
        });
    }

    /**
     * Aplica la guarda a la navegación apenas se carga la página: los
     * enlaces que exigen sesión quedan bloqueados para el visitante.
     */
    function iniciar() {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", aplicarGuardaNav);
        } else {
            aplicarGuardaNav();
        }
    }

    iniciar();

    return {
        cargar: cargar,
        logueado: logueado,
        requerirLogin: requerirLogin,
        aplicarGuardaNav: aplicarGuardaNav,
    };
})();
