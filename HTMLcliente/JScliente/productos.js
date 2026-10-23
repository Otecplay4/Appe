/**
 * A.P.P.E. — Rejilla de productos por categoría.
 *
 * Hallazgo 6: la versión anterior interpolaba el nombre del producto dentro
 * de `innerHTML`. Aquí todo el contenido se inserta con `textContent`.
 */
(function () {
    "use strict";

    const grid = document.getElementById("productosGrid");
    const titulo = document.getElementById("productosTitulo");

    if (!grid) {
        return;
    }

    function nombreCategoria(categoria) {
        switch (categoria) {
            case "ropa":
                return "Atuendos";
            case "suplementos":
                return "Suplementos";
            case "accesorios":
                return "Accesorios";
            default:
                return "Productos";
        }
    }

    /**
     * Dirección de la ficha de un producto.
     *
     * Los productos que vienen del servidor se piden por su identificador;
     * los del catálogo fijo, que no están en la base de datos, por su
     * categoría y su posición.
     */
    function urlDetalle(producto) {
        if (producto.origen === "servidor") {
            return "./producto.html?id=" + encodeURIComponent(producto.id)
                + "&cat=" + encodeURIComponent(producto.categoria);
        }

        return "./producto.html?cat=" + encodeURIComponent(producto.categoria)
            + "&idx=" + encodeURIComponent(producto.indice);
    }

    function agregarAlCarrito(producto) {
        Auth.requerirLogin("Necesitás iniciar sesión para agregar productos al carrito.").then((permitido) => {
            if (!permitido) {
                return;
            }

            if (typeof Carrito !== "undefined") {
                Carrito.agregar(producto, producto.categoria);
            }
        });
    }

    function crearTarjetaProducto(producto) {
        const caja = Dom.crear("article", { clases: ["caja-producto"] });

        const foto = Dom.crear("img", {
            clases: ["foto-producto"],
            atributos: { src: producto.imagen, alt: producto.nombre, loading: "lazy" },
        });
        foto.addEventListener("error", () => {
            foto.setAttribute("src", "./imagenes/OLYMPIA.jpg");
        });

        const info = Dom.crear("div", { clases: ["info-producto"] });

        // El nombre es un enlace a la ficha del producto.
        const enlace = Dom.crear("a", {
            clases: ["nombre-producto", "enlace-producto"],
            texto: producto.nombre,
            atributos: { href: urlDetalle(producto) },
        });

        info.appendChild(enlace);
        info.appendChild(Dom.texto("p", producto.descripcion, { clases: ["descripcion-producto"] }));
        info.appendChild(Dom.texto("span", Dom.precio(producto.precio), { clases: ["precio-producto"] }));

        // Acciones: comprar sin pasar por la ficha, o ver el detalle.
        const acciones = Dom.crear("div", { clases: ["acciones-producto"] });

        const botonAgregar = Dom.boton("Agregar al carrito", () => {
            agregarAlCarrito(producto);
        }, { clases: ["boton-agregar"] });

        const botonDetalle = Dom.crear("a", {
            clases: ["boton-producto", "boton-ver-detalle"],
            texto: "Ver detalle",
            atributos: { href: urlDetalle(producto) },
        });

        acciones.appendChild(botonAgregar);
        acciones.appendChild(botonDetalle);

        caja.appendChild(foto);
        caja.appendChild(info);
        caja.appendChild(acciones);

        return caja;
    }

    function mostrarCategoria() {
        const parametros = new URLSearchParams(window.location.search);
        const categoria = parametros.get("cat") || "ropa";
        const valida = Catalogo.CATEGORIAS.indexOf(categoria) !== -1;
        const definitiva = valida ? categoria : Catalogo.CATEGORIAS[0];

        if (titulo) {
            titulo.textContent = nombreCategoria(definitiva);
        }

        Dom.limpiar(grid);

        Catalogo.cargarProductosServidor(definitiva).then((productos) => {
            if (productos.length === 0) {
                grid.appendChild(Dom.texto("p", "No hay productos disponibles en esta categoría.", { clases: ["sin-productos"] }));
                return;
            }

            productos.forEach((producto) => {
                grid.appendChild(crearTarjetaProducto(producto));
            });
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", mostrarCategoria);
    } else {
        mostrarCategoria();
    }
})();
