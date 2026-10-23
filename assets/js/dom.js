/**
 * A.P.P.E. — Utilidades de DOM seguras.
 *
 * Hallazgos 5 y 6: varios listados interpolaban datos del servidor dentro de
 * `innerHTML`, lo que convertía un nombre de producto o un nombre de cliente
 * en un vector de XSS almacenado. Todas las pantallas nuevas usan estas
 * funciones, que construyen el DOM y asignan el texto con `textContent`.
 */
const Dom = {
    /**
     * Crea un elemento con sus atributos y sus hijos.
     *
     * @param {string} etiqueta
     * @param {Object} [opciones]  { texto, clases, atributos, dataset, html }
     * @param {Array<Node>} [hijos]
     */
    crear(etiqueta, opciones, hijos) {
        const nodo = document.createElement(etiqueta);
        const config = opciones || {};

        if (typeof config.texto === "string") {
            nodo.textContent = config.texto;
        }

        if (config.clases) {
            const clases = Array.isArray(config.clases) ? config.clases : [config.clases];
            clases.forEach((clase) => nodo.classList.add(clase));
        }

        if (config.atributos) {
            Object.keys(config.atributos).forEach((nombre) => {
                const valor = config.atributos[nombre];
                if (valor !== undefined && valor !== null) {
                    nodo.setAttribute(nombre, String(valor));
                }
            });
        }

        if (config.dataset) {
            Object.keys(config.dataset).forEach((nombre) => {
                nodo.dataset[nombre] = String(config.dataset[nombre]);
            });
        }

        (hijos || []).forEach((hijo) => {
            if (hijo) {
                nodo.appendChild(hijo);
            }
        });

        return nodo;
    },

    /**
     * Crea un elemento con texto (la forma más segura de escribir contenido).
     */
    texto(etiqueta, valor, opciones) {
        return Dom.crear(etiqueta, Object.assign({ texto: valor === null || valor === undefined ? "" : String(valor) }, opciones || {}));
    },

    /**
     * Vacía un contenedor sin usar `innerHTML`.
     */
    limpiar(nodo) {
        if (!nodo) {
            return;
        }

        while (nodo.firstChild) {
            nodo.removeChild(nodo.firstChild);
        }
    },

    /**
     * Crea un botón con su manejador.
     */
    boton(texto, manejador, opciones) {
        const config = opciones || {};
        const boton = Dom.crear("button", Object.assign({ texto: texto, clases: config.clases || [] }, config));

        boton.type = config.tipo || "button";

        if (manejador) {
            boton.addEventListener("click", manejador);
        }

        return boton;
    },

    /**
     * Muestra un mensaje en un contenedor. El texto se asigna con
     * `textContent`, nunca con `innerHTML`.
     *
     * @param {Element} contenedor
     * @param {string} mensaje
     * @param {string} [tipo]  "error", "exito" o "info".
     */
    mensaje(contenedor, mensaje, tipo) {
        if (!contenedor) {
            return;
        }

        Dom.limpiar(contenedor);

        if (!mensaje) {
            return;
        }

        const clase = tipo === "error" ? "mensaje-error" : (tipo === "exito" ? "mensaje-exito" : "mensaje-info");
        const nodo = Dom.texto("p", mensaje, { clases: [clase] });

        contenedor.appendChild(nodo);
    },

    /**
     * Formatea un importe con el símbolo de moneda.
     */
    precio(valor) {
        const numero = Number(valor);

        if (!isFinite(numero)) {
            return "$ 0";
        }

        return "$ " + numero.toLocaleString("es-UY", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    /**
     * Formatea una fecha ISO a formato local legible.
     */
    fecha(valor) {
        if (!valor) {
            return "-";
        }

        const partes = String(valor).split("T");
        const dia = partes[0].split("-").reverse().join("/");
        const hora = partes.length > 1 ? " " + partes[1].substring(0, 5) : "";

        return dia + hora;
    },
};
