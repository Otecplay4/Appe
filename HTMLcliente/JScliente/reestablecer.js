/**
 * A.P.P.E. — Restablecimiento de contraseña con token.
 *
 * Requisito funcional RF-03. El token viaja en la URL y se envía junto con la
 * contraseña nueva.
 */
(function () {
    "use strict";

    API.configurar("./PHPcliente/");

    const formulario = document.getElementById("formularioReestablecer");
    const mensaje = document.getElementById("reestablecerMensaje");
    const campoToken = document.getElementById("token");
    const campoClave = document.getElementById("contraseñaNueva");
    const campoRepeticion = document.getElementById("contraseñaRepetida");

    if (!formulario) {
        return;
    }

    // El token se toma de la URL y se envía en el cuerpo de la petición.
    const parametros = new URLSearchParams(window.location.search);
    const tokenUrl = parametros.get("token") || "";

    if (campoToken) {
        campoToken.value = tokenUrl;
    }

    if (!tokenUrl) {
        Dom.mensaje(mensaje, "El enlace de restablecimiento no es válido.", "error");
        formulario.querySelectorAll("input, button").forEach((campo) => {
            campo.disabled = true;
        });
        return;
    }

    formulario.addEventListener("submit", (evento) => {
        evento.preventDefault();

        const clave = campoClave ? campoClave.value : "";
        const repeticion = campoRepeticion ? campoRepeticion.value : "";

        if (clave.length < 8) {
            Dom.mensaje(mensaje, "La contraseña debe tener al menos 8 caracteres.", "error");
            return;
        }

        if (clave !== repeticion) {
            Dom.mensaje(mensaje, "Las contraseñas no coinciden.", "error");
            return;
        }

        Dom.mensaje(mensaje, "Actualizando la contraseña...", "info");

        API.post("reestablecerclave.php", {
            token: tokenUrl,
            contraseña: clave,
            confirmacion: repeticion,
        })
            .then(() => {
                Dom.mensaje(mensaje, "Su contraseña fue actualizada. Ya puede iniciar sesión.", "exito");
                setTimeout(() => {
                    window.location.href = "./Inicio-Sesion.html";
                }, 1500);
            })
            .catch((error) => {
                Dom.mensaje(mensaje, error.mensaje || "No se pudo restablecer la contraseña.", "error");
            });
    });
})();
