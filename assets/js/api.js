/**
 * A.P.P.E. — Cliente HTTP compartido por el front-end.
 *
 * Unifica el acceso a los endpoints PHP y garantiza que todas las páginas
 * usen el mismo contrato de respuesta y el mismo manejo de errores.
 *   Éxito -> HTTP 200/201  { "exito": true,  "mensaje": "...", "datos": { ... } }
 *   Error -> HTTP 4xx/5xx  { "exito": false, "mensaje": "...", "codigo": "..." }
 */
const API = (() => {
    "use strict";

    let base = "";
    let csrf = null;
    let endpointSesion = "sesion.php";

    /**
     * Configura el directorio base desde el que se resuelven los endpoints.
     * @param {string} directorio  Por ejemplo "./PHPcliente/".
     * @param {string} [sesion]    Endpoint que entrega el token CSRF.
     */
    function configurar(directorio, sesion) {
        base = directorio || "";
        endpointSesion = sesion || "sesion.php";
    }

    /**
     * Error de la API con el mensaje que devuelve el servidor.
     *
     * El mensaje se guarda en `mensaje` (como lo leen las pantallas) y también
     * en `message`, que es la propiedad estándar de `Error`.
     */
    class ErrorApi extends Error {
        constructor(mensaje, codigo, estado) {
            super(mensaje);
            this.name = "ErrorApi";
            this.mensaje = mensaje;
            this.codigo = codigo || "error";
            this.estado = estado || 0;
        }
    }

    /**
     * Construye la URL completa con sus parámetros de consulta.
     */
    function construirUrl(ruta, parametros) {
        const completa = base + ruta;
        const query = new URLSearchParams();

        Object.keys(parametros || {}).forEach((clave) => {
            const valor = parametros[clave];
            if (valor !== undefined && valor !== null && valor !== "") {
                query.append(clave, valor);
            }
        });

        const cadena = query.toString();
        return cadena ? completa + (completa.includes("?") ? "&" : "?") + cadena : completa;
    }

    /**
     * Obtiene y mantiene el token CSRF de la sesión.
     */
    async function obtenerCsrf(forzar) {
        if (csrf && !forzar) {
            return csrf;
        }

        const url = construirUrl(endpointSesion, {});
        const respuesta = await fetch(url, {
            method: "GET",
            credentials: "same-origin",
            headers: { "Accept": "application/json" },
        });

        if (!respuesta.ok) {
            throw new ErrorApi("No se pudo verificar la sesión.", "sin_sesion", respuesta.status);
        }

        const cuerpo = await respuesta.json();
        csrf = cuerpo.datos && cuerpo.datos.csrf ? cuerpo.datos.csrf : null;
        return csrf;
    }

    /**
     * Fuerza la renovación del token CSRF (tras iniciar o cerrar sesión).
     */
    async function refrescarCsrf() {
        return obtenerCsrf(true);
    }

    /**
     * Realiza una petición y devuelve el bloque `datos` de la respuesta.
     */
    async function peticion(ruta, opciones) {
        const config = opciones || {};
        const metodo = (config.metodo || "GET").toUpperCase();
        const url = construirUrl(ruta, config.parametros);

        const cabeceras = { "Accept": "application/json" };
        let cuerpo;

        if (config.cuerpo instanceof FormData) {
            cuerpo = config.cuerpo;
        } else if (config.cuerpo !== undefined) {
            cabeceras["Content-Type"] = "application/json;charset=UTF-8";
            cuerpo = JSON.stringify(config.cuerpo);
        }

        // El token CSRF solo se necesita en las operaciones que modifican el
        // estado. Se obtiene de la sesión del servidor.
        if (metodo !== "GET" && metodo !== "HEAD") {
            const token = await obtenerCsrf();
            if (token) {
                cabeceras["X-CSRF-Token"] = token;
            }
        }

        let respuesta;
        try {
            respuesta = await fetch(url, {
                method: metodo,
                headers: cabeceras,
                body: cuerpo,
                credentials: "same-origin",
            });
        } catch (error) {
            throw new ErrorApi("No se pudo conectar con el servidor.", "sin_conexion", 0);
        }

        let contenido = null;
        const tipo = respuesta.headers.get("Content-Type") || "";

        if (tipo.includes("application/json")) {
            try {
                contenido = await respuesta.json();
            } catch (error) {
                contenido = null;
            }
        }

        if (!respuesta.ok) {
            const mensaje = contenido && contenido.mensaje
                ? contenido.mensaje
                : "La operación no se pudo completar (código " + respuesta.status + ").";
            const codigo = contenido && contenido.codigo ? contenido.codigo : "error";

            // Un token vencido obliga a renovarlo para el siguiente intento.
            if (codigo === "acceso" || respuesta.status === 403) {
                csrf = null;
            }

            throw new ErrorApi(mensaje, codigo, respuesta.status);
        }

        if (!contenido) {
            throw new ErrorApi("La respuesta del servidor no se pudo interpretar.", "respuesta_invalida", respuesta.status);
        }

        return contenido.datos || {};
    }

    /**
     * Petición GET.
     */
    function get(ruta, parametros) {
        return peticion(ruta, { metodo: "GET", parametros: parametros });
    }

    /**
     * Petición POST con datos en formato JSON.
     */
    function post(ruta, datos) {
        return peticion(ruta, { metodo: "POST", cuerpo: datos || {} });
    }

    /**
     * Petición POST con un FormData (subida de archivos).
     */
    function postForm(ruta, formulario) {
        return peticion(ruta, { metodo: "POST", cuerpo: formulario });
    }

    return {
        configurar: configurar,
        obtenerCsrf: obtenerCsrf,
        refrescarCsrf: refrescarCsrf,
        peticion: peticion,
        get: get,
        post: post,
        postForm: postForm,
        ErrorApi: ErrorApi,
    };
})();
