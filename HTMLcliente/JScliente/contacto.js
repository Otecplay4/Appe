/**
 * A.P.P.E. — Formulario de contacto con el gimnasio.
 *
 * Requisito funcional RF-15 · Caso de uso CU-07.
 */
(function () {
    "use strict";

    API.configurar("./PHPcliente/");

    const formulario = document.getElementById("formularioContacto");
    const mensaje = document.getElementById("contactoMensaje");

    if (!formulario) {
        return;
    }

    formulario.addEventListener("submit", (evento) => {
        evento.preventDefault();

        const datos = new FormData(formulario);
        const nombre = String(datos.get("nombre") || "").trim();
        const correo = String(datos.get("correo") || "").trim();
        const texto = String(datos.get("mensaje") || "").trim();

        if (nombre === "" || correo === "" || texto === "") {
            Dom.mensaje(mensaje, "Completá el nombre, el correo y el mensaje.", "error");
            return;
        }

        if (texto.length < 10) {
            Dom.mensaje(mensaje, "El mensaje debe tener al menos 10 caracteres.", "error");
            return;
        }

        Dom.mensaje(mensaje, "Enviando mensaje...", "info");

        API.postForm("contacto.php", datos)
            .then(() => {
                Dom.mensaje(mensaje, "¡Gracias por escribirnos! Te responderemos a la brevedad.", "exito");
                formulario.reset();
            })
            .catch((error) => {
                Dom.mensaje(mensaje, error.mensaje || "No se pudo enviar el mensaje.", "error");
            });
    });
})();
