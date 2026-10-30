/**
 * A.P.P.E. — Inicio de sesión y registro del cliente.

 * Todos los mensajes se muestran con `textContent` a través de `Dom.mensaje`.
 */
(function () {
    "use strict";

    API.configurar("./PHPcliente/");

    const div = document.getElementById("div");
    const crearCuenta = document.getElementById("crearCuenta");
    const iniciarSesion = document.getElementById("iniciarSesion");

    /* ----------------------------------------------------------------------
     * Registro (CU-09) — RF-01
     * -------------------------------------------------------------------- */
    if (crearCuenta) {
        const etiquetaCaptcha = document.getElementById("captcha-pregunta");
        const campoCaptcha = document.getElementById("captcha");

        function cargarCaptcha() {
            API.get("captcha.php")
                .then((datos) => {
                    if (etiquetaCaptcha && datos.pregunta) {
                        etiquetaCaptcha.textContent = datos.pregunta;
                    }
                })
                .catch(() => {
                    if (etiquetaCaptcha) {
                        etiquetaCaptcha.textContent = "No se pudo cargar la verificación. Recargue la página.";
                    }
                });
        }

        cargarCaptcha();

        crearCuenta.addEventListener("submit", (evento) => {
            evento.preventDefault();

            const datos = new FormData(crearCuenta);
            const nombre = String(datos.get("nombre") || "").trim();
            const apellido = String(datos.get("apellido") || "").trim();
            const correo = String(datos.get("correo") || "").trim();
            const contrasena = String(datos.get("contraseña") || "");

            if (nombre === "" || apellido === "" || correo === "" || contrasena === "") {
                Dom.mensaje(div, "Completá todos los campos obligatorios.", "error");
                return;
            }

            if (contrasena.length < 8) {
                Dom.mensaje(div, "La contraseña debe tener al menos 8 caracteres.", "error");
                return;
            }

            Dom.mensaje(div, "Creando la cuenta...", "info");

            API.postForm("registro.php", datos)
                .then(() => {
                    Dom.mensaje(div, "¡Cuenta creada! Redirigiendo...", "exito");
                    window.location.href = "./Inicio.html";
                })
                .catch((error) => {
                    Dom.mensaje(div, error.mensaje || "No se pudo crear la cuenta.", "error");

                    // El CAPTCHA se consume en cada intento: se pide uno nuevo.
                    if (campoCaptcha) {
                        campoCaptcha.value = "";
                    }
                    cargarCaptcha();
                });
        });
    }

    /* ----------------------------------------------------------------------
     * Inicio de sesión (CU-01) — RF-02
     * -------------------------------------------------------------------- */
    if (iniciarSesion) {
        iniciarSesion.addEventListener("submit", (evento) => {
            evento.preventDefault();

            const datos = new FormData(iniciarSesion);
            const correo = String(datos.get("correo") || "").trim();
            const contrasena = String(datos.get("contraseña") || "");

            if (correo === "" || contrasena === "") {
                Dom.mensaje(div, "Completá el correo y la contraseña.", "error");
                return;
            }

            Dom.mensaje(div, "Verificando credenciales...", "info");

            API.post("iniciarSesion.php", { correo: correo, contraseña: contrasena })
                .then(() => {
                    Dom.mensaje(div, "Sesión iniciada. Redirigiendo...", "exito");
                    window.location.href = "./Inicio.html";
                })
                .catch((error) => {
                    Dom.mensaje(div, error.mensaje || "No se pudo iniciar sesión.", "error");
                });
        });
    }
})();
