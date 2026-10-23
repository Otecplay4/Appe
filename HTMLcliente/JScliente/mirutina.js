/**
 * A.P.P.E. — Rutina del cliente.
 *
 * Requisito funcional RF-13 · Caso de uso CU-06.
 *
 * Hallazgo 5: la versión anterior interpolaba los datos de la rutina dentro
 * de `innerHTML`. Ahora todo se inserta con `textContent`.
 */
(function () {
    "use strict";

    API.configurar("./PHPcliente/");

    const titulo = document.getElementById("tituloRutina");
    const tabla = document.getElementById("tablaEjercicios");

    if (!tabla) {
        return;
    }

    function cuerpoTabla() {
        if (!tabla) {
            return null;
        }

        if (tabla.tagName === "TABLE") {
            return tabla.querySelector("tbody") || tabla;
        }

        return tabla;
    }

    function cargarRutina() {
        API.get("mirutina.php")
            .then((datos) => {
                const cuerpo = cuerpoTabla();

                if (cuerpo) {
                    // Solo se vacía el cuerpo de la tabla: el `thead` con los
                    // títulos de las columnas se conserva.
                    Dom.limpiar(cuerpo);
                }

                const rutina = datos.rutina;

                if (!rutina) {
                    if (titulo) {
                        titulo.textContent = "Todavía no tenés una rutina asignada";
                    }

                    if (cuerpo) {
                        cuerpo.appendChild(Dom.texto("p", "Todavía no tenés una rutina asignada. Contactá a tu entrenador para que te asigne una.", { clases: ["sin-rutina"] }));
                    }

                    return;
                }

                if (titulo) {
                    titulo.textContent = String(rutina.nombre || "Mi rutina").toUpperCase();
                }

                const dias = datos.dias || {};
                const claves = Object.keys(dias);

                if (claves.length === 0) {
                    if (cuerpo) {
                        cuerpo.appendChild(Dom.texto("p", "La rutina no tiene ejercicios cargados.", { clases: ["sin-rutina"] }));
                    }
                    return;
                }

                claves.forEach((dia) => {
                    const ejercicios = dias[dia] || [];

                    if (tabla.tagName === "TABLE") {
                        const cabecera = Dom.crear("tr", { clases: ["fila-dia"] });
                        cabecera.appendChild(Dom.crear("th", { texto: "Día " + dia, atributos: { colspan: "4" } }));
                        cuerpo.appendChild(cabecera);
                    } else {
                        cuerpo.appendChild(Dom.texto("h3", "Día " + dia, { clases: ["titulo-dia"] }));
                    }

                    ejercicios.forEach((ejercicio) => {
                        if (tabla.tagName === "TABLE") {
                            const fila = Dom.crear("tr", { clases: ["fila-ejercicio"] });
                            fila.appendChild(Dom.crear("td", { texto: String(ejercicio.ejercicio) }));
                            fila.appendChild(Dom.crear("td", { texto: String(ejercicio.musculo) }));
                            fila.appendChild(Dom.crear("td", { texto: String(ejercicio.serie_repeticion) }));
                            fila.appendChild(Dom.crear("td", { texto: String(dia) }));
                            cuerpo.appendChild(fila);
                        } else {
                            const item = Dom.crear("li", { clases: ["ejercicio"] });
                            item.appendChild(Dom.texto("span", ejercicio.ejercicio, { clases: ["nombre-ejercicio"] }));
                            item.appendChild(Dom.texto("span", ejercicio.musculo, { clases: ["musculo-ejercicio"] }));
                            item.appendChild(Dom.texto("span", ejercicio.serie_repeticion, { clases: ["series-ejercicio"] }));
                            cuerpo.appendChild(item);
                        }
                    });
                });
            })
            .catch((error) => {
                const cuerpo = cuerpoTabla();

                // 401: no hay sesión. 403: la sesión no tiene el rol de socio.
                const sinAcceso = error.estado === 401 || error.estado === 403;

                if (titulo) {
                    titulo.textContent = sinAcceso
                        ? "Iniciá sesión para ver tu rutina"
                        : "No se pudo cargar la rutina";
                }

                if (cuerpo) {
                    cuerpo.appendChild(Dom.texto(
                        "p",
                        sinAcceso
                            ? "Tu sesión actual no tiene acceso a la información de rutinas."
                            : (error.mensaje || "No se pudo cargar la rutina."),
                        { clases: ["sin-rutina"] }
                    ));
                }
            });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", cargarRutina);
    } else {
        cargarRutina();
    }
})();
