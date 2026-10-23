/**
 * A.P.P.E. — Solicitud de recuperación de contraseña.
 *
 * Requisito funcional RF-03.
 */
(function () {
    "use strict";

    API.configurar("./PHPcliente/");

    const formulario = document.getElementById("formularioRecuperar");
    const mensaje = document.getElementById("recuperarMensaje");
    const campoCorreo = document.getElementById("correoRecuperar");

    if (!formulario) {
        return;
    }

    formulario.addEventListener("submit", (evento) => {
        evento.preventDefault();

        const correo = campoCorreo ? campoCorreo.value.trim() : "";

        if (correo === "") {
            Dom.mensaje(mensaje, "Indicá el correo con el que te registraste.", "error");
            return;
        }

        Dom.mensaje(mensaje, "Enviando solicitud...", "info");

        API.post("recuperarclave.php", { correo: correo })
            .then((datos) => {
                Dom.mensaje(mensaje, "Si el correo está registrado, te enviamos un enlace para restablecer la contraseña.", "exito");

                // En un entorno sin servidor de correo el enlace se muestra
                // en pantalla para poder verificar el flujo completo.
                if (datos && datos.enlace) {
                    const caja = document.createElement("div");
                    caja.className = "mensaje-info";
                    const enlace = document.createElement("a");
                    enlace.href = datos.enlace;
                    enlace.textContent = "Abrir el enlace de restablecimiento";
                    caja.appendChild(enlace);
                    if (mensaje) {
                        mensaje.appendChild(caja);
                    }
                }
            })
            .catch((error) => {
                Dom.mensaje(mensaje, error.mensaje || "No se pudo procesar la solicitud.", "error");
            });
    });
})();
