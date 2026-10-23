/**
 * A.P.P.E. — Detalle de un producto.
 *
 * Las acciones «Agregar al carrito» y «Comprar» exigen inicio de sesión
 * (RF-04, RNF-05).
 */
(function () {
    "use strict";

    const cajaDetalle = document.getElementById("detalleProducto");

    if (!cajaDetalle) {
        return;
    }

    function nombreCategoria(texto) {
        switch (texto) {
            case "ropa":
                return "Atuendo";
            case "suplementos":
                return "Consumible";
            case "accesorios":
                return "Accesorio";
            default:
                return "Producto";
        }
    }

    function mostrarNoEncontrado() {
        Dom.limpiar(cajaDetalle);
        cajaDetalle.appendChild(Dom.texto("p", "No se encontró el producto solicitado.", { clases: ["sin-productos"] }));
    }

    function mostrarProducto(producto) {
        Dom.limpiar(cajaDetalle);

        const foto = Dom.crear("img", {
            clases: ["foto-grande"],
            atributos: { src: producto.imagen, alt: producto.nombre },
        });
        foto.addEventListener("error", () => {
            foto.setAttribute("src", "./imagenes/OLYMPIA.jpg");
        });

        const info = Dom.crear("div", { clases: ["info-grande"] });
        info.appendChild(Dom.texto("span", nombreCategoria(producto.categoria), { clases: ["tipo-producto"] }));
        info.appendChild(Dom.texto("h2", producto.nombre, { clases: ["nombre-grande"] }));
        info.appendChild(Dom.texto("p", producto.descripcion, { clases: ["descripcion-producto"] }));
        info.appendChild(Dom.texto("span", Dom.precio(producto.precio), { clases: ["precio-producto"] }));

        const botones = Dom.crear("div", { clases: ["botones-compra"] });

        const botonAgregar = Dom.boton("Agregar al carrito", () => {
            Auth.requerirLogin("Necesitás iniciar sesión para agregar productos al carrito.").then((permitido) => {
                if (!permitido) {
                    return;
                }

                if (typeof Carrito !== "undefined") {
                    Carrito.agregar(producto, producto.categoria);
                }
            });
        }, { clases: ["boton-agregar"] });

        const botonComprar = Dom.boton("Comprar", () => {
            Auth.requerirLogin("Necesitás iniciar sesión para comprar.").then((permitido) => {
                if (!permitido) {
                    return;
                }

                const aviso = "Compra simulada: " + producto.nombre
                    + ".\n\nSe agregó al carrito. El proyecto no integra ningún medio de pago.";

                window.alert(aviso);
            });
        }, { clases: ["boton-comprar", "boton-producto"] });

        botones.appendChild(botonAgregar);
        botones.appendChild(botonComprar);
        info.appendChild(botones);

        cajaDetalle.appendChild(foto);
        cajaDetalle.appendChild(info);
    }

    function iniciar() {
        const parametros = new URLSearchParams(window.location.search);
        const idServidor = parametros.get("id");
        const indice = parametros.get("idx");
        const categoria = parametros.get("cat") || "accesorios";

        // Producto del servidor: se pide por su identificador.
        if (idServidor && /^\d+$/.test(idServidor)) {
            Catalogo.cargarProductoServidor(idServidor).then((producto) => {
                if (!producto) {
                    mostrarNoEncontrado();
                    return;
                }

                producto.categoria = producto.categoria || categoria;
                mostrarProducto(producto);
            });
            return;
        }

        // Producto del catálogo fijo: no está en la base de datos, se toma de
        // la categoría y su posición.
        if (indice !== null && /^\d+$/.test(indice)) {
            Catalogo.cargarProductoFijo(categoria, parseInt(indice, 10)).then((producto) => {
                if (!producto) {
                    mostrarNoEncontrado();
                    return;
                }

                mostrarProducto(producto);
            });
            return;
        }

        mostrarNoEncontrado();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", iniciar);
    } else {
        iniciar();
    }
})();
