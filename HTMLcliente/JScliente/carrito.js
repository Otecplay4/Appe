/**
 * A.P.P.E. — Carrito de compras (almacenamiento local).
 *
 * El carrito es local al navegador: es una simulación, no hay pasarela de
 * pago. El contenido se inserta siempre con `textContent`.
 */
const Carrito = (() => {
    "use strict";

    const CLAVE = "appe_carrito";

    function leer() {
        try {
            const crudo = window.localStorage.getItem(CLAVE);
            const lista = crudo ? JSON.parse(crudo) : [];
            return Array.isArray(lista) ? lista : [];
        } catch (error) {
            return [];
        }
    }

    function guardar(lista) {
        try {
            window.localStorage.setItem(CLAVE, JSON.stringify(lista));
        } catch (error) {
            // El almacenamiento puede estar deshabilitado: no es crítico.
        }
    }

    function clave(producto) {
        return String(producto.id) + "|" + String(producto.categoria || "");
    }

    function actualizarNumero() {
        const contador = document.getElementById("numeroCarrito");
        if (!contador) {
            return;
        }

        const cantidad = leer().reduce((total, item) => total + (Number(item.cantidad) || 0), 0);
        contador.textContent = String(cantidad);
    }

    function agregar(producto, categoria) {
        const lista = leer();
        const id = clave({ id: producto.id, categoria: categoria || producto.categoria });
        const existente = lista.find((item) => item.clave === id);

        if (existente) {
            existente.cantidad = (Number(existente.cantidad) || 0) + 1;
        } else {
            lista.push({
                clave: id,
                id: producto.id,
                nombre: producto.nombre,
                precio: Number(producto.precio || 0),
                imagen: producto.imagen || "",
                categoria: categoria || producto.categoria || "",
                cantidad: 1,
            });
        }

        guardar(lista);
        actualizarNumero();
        mostrar();

        if (typeof Swal !== "undefined") {
            Swal.fire({ title: "Agregado", text: producto.nombre + " se agregó al carrito.", icon: "success", timer: 1500 });
        }
    }

    function quitar(item) {
        guardar(leer().filter((otro) => otro.clave !== item.clave));
        actualizarNumero();
        mostrar();
    }

    function mostrar() {
        const lista = document.getElementById("listaCarrito");
        if (!lista) {
            return;
        }

        Dom.limpiar(lista);
        const items = leer();

        if (items.length === 0) {
            lista.appendChild(Dom.texto("p", "Tu carrito está vacío.", { clases: ["carrito-vacio"] }));
            return;
        }

        items.forEach((item) => {
            const fila = Dom.crear("div", { clases: ["fila-carrito"] });

            if (item.imagen) {
                const foto = Dom.crear("img", {
                    clases: ["foto-carrito"],
                    atributos: { src: item.imagen, alt: item.nombre },
                });
                fila.appendChild(foto);
            }

            fila.appendChild(Dom.texto("span", item.nombre, { clases: ["nombre-carrito"] }));
            fila.appendChild(Dom.texto("span", " x " + (Number(item.cantidad) || 0), { clases: ["cantidad-carrito"] }));
            fila.appendChild(Dom.texto("span", Dom.precio((Number(item.precio) || 0) * (Number(item.cantidad) || 0)), { clases: ["precio-carrito"] }));

            const boton = Dom.boton("Quitar", () => quitar(item), { clases: ["boton-quitar"] });
            fila.appendChild(boton);

            lista.appendChild(fila);
        });

        const total = items.reduce((suma, item) => suma + (Number(item.precio) || 0) * (Number(item.cantidad) || 0), 0);
        lista.appendChild(Dom.texto("p", "Total: " + Dom.precio(total), { clases: ["total-carrito"] }));
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => {
            actualizarNumero();
            mostrar();
        });
    } else {
        actualizarNumero();
        mostrar();
    }

    return {
        leer: leer,
        guardar: guardar,
        agregar: agregar,
        agregarAlCarrito: agregar,
        quitar: quitar,
        mostrar: mostrar,
        actualizarNumero: actualizarNumero,
    };
})();
