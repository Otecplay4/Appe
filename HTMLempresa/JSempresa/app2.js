/**
 * A.P.P.E. — Inicio de sesión del entrenador y alta de cuentas.
 *
 * El alta de cuentas funciona con solicitud: cualquier persona puede enviarla
 * y solo el Administrador la aprueba (ver `solicitudesregistro.html`).
 */
(function () {
    "use strict";

    API.configurar("./PHPempresa/");

    const div = document.getElementById("div");
    const crearEntrenador = document.getElementById("crearEntrenador");
    const iniciarEntrenador = document.getElementById("iniciarEntrenador");

    /* ----------------------------------------------------------------------
     * Inicio de sesión (CU-01)
     * -------------------------------------------------------------------- */
    if (iniciarEntrenador) {
        iniciarEntrenador.addEventListener("submit", (evento) => {
            evento.preventDefault();

            const datos = new FormData(iniciarEntrenador);
            const correo = String(datos.get("correo") || "").trim();
            const contrasena = String(datos.get("contraseña") || "");

            if (correo === "" || contrasena === "") {
                Dom.mensaje(div, "Completá el correo y la contraseña.", "error");
                return;
            }

            Dom.mensaje(div, "Verificando credenciales...", "info");

            API.post("iniciarentrenador.php", { correo: correo, contraseña: contrasena })
                .then(() => {
                    Dom.mensaje(div, "Sesión iniciada. Redirigiendo...", "exito");
                    window.location.href = "./inicio-empresa.html";
                })
                .catch((error) => {
                    Dom.mensaje(div, error.mensaje || "No se pudo iniciar sesión.", "error");
                });
        });
    }

    /* ----------------------------------------------------------------------
     * Solicitud de alta de entrenador (RF-05) — la aprueba el Administrador
     * -------------------------------------------------------------------- */
    if (crearEntrenador) {
        crearEntrenador.addEventListener("submit", (evento) => {
            evento.preventDefault();

            const datos = new FormData(crearEntrenador);
            const correo = String(datos.get("correo") || "").trim();
            const contrasena = String(datos.get("contraseña") || "");

            if (correo === "" || contrasena === "") {
                Dom.mensaje(div, "Completá todos los campos obligatorios.", "error");
                return;
            }

            if (contrasena.length < 8) {
                Dom.mensaje(div, "La contraseña debe tener al menos 8 caracteres.", "error");
                return;
            }

            Dom.mensaje(div, "Creando la cuenta...", "info");

            API.postForm("crearentrenador.php", datos)
                .then(() => {
                    Dom.mensaje(div, "Solicitud de registro enviada. El administrador la revisará y te avisará cuando esté aprobada. Mientras tanto no podés iniciar sesión.", "exito");
                    crearEntrenador.reset();
                })
                .catch((error) => {
                    Dom.mensaje(div, error.mensaje || "No se pudo crear la cuenta.", "error");
                });
        });
    }
})();
