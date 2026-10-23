/**
 * A.P.P.E. — Catálogo de productos: carga desde el servidor.
 *
 * El servidor es la fuente de verdad: los productos que carga el entrenador
 * (RF-08) se suman al catálogo fijo de la interfaz.
 *
 * Todos los datos del servidor se tratan como no confiables: se devuelven
 * como datos y quien los pinta los inserta con `textContent` (hallazgos 5 y 6).
 */
const Catalogo = (() => {
    "use strict";

    API.configurar("./PHPcliente/");

    /** Categorías de la interfaz y su correspondencia con el MER. */
    const CATEGORIAS = ["ropa", "suplementos", "accesorios"];

    /** Catálogo fijo, usado si el servidor no responde. */
    const CATALOGO_FIJO = {
        ropa: [
            { nombre: "Musculosa", descripcion: "Remera cómoda para entrenar todos los días.", precio: 1200, imagen: "./catalogo_img/ropa/0.jpg" },
            { nombre: "Remera", descripcion: "Buzo liviano para usar antes o después de entrenar.", precio: 1400, imagen: "./catalogo_img/ropa/1.jpg" },
            { nombre: "Pantalón deportivo", descripcion: "Pantalón pensado para moverte con comodidad.", precio: 1800, imagen: "./catalogo_img/ropa/2.jpg" },
            { nombre: "Short", descripcion: "Short práctico para tus rutinas de gimnasio.", precio: 900, imagen: "./catalogo_img/ropa/3.jpg" },
            { nombre: "Campera deportiva", descripcion: "Campera cómoda para los días más frescos.", precio: 2200, imagen: "./catalogo_img/ropa/4.jpg" },
        ],
        suplementos: [
            { nombre: "Proteína", descripcion: "Suplemento de proteína para acompañar tu entrenamiento.", precio: 2500, imagen: "./catalogo_img/suplementos/0.jpg" },
            { nombre: "Creatina", descripcion: "Creatina para sumar a tu rutina de fuerza.", precio: 1100, imagen: "./catalogo_img/suplementos/1.jpg" },
            { nombre: "Pre-entreno", descripcion: "Pre-entreno para tomar antes de tus sesiones.", precio: 1350, imagen: "./catalogo_img/suplementos/2.jpg" },
            { nombre: "Barra proteica", descripcion: "Aminoácidos para acompañar tus entrenamientos.", precio: 300, imagen: "./catalogo_img/suplementos/3.jpg" },
            { nombre: "Alfajor proteico", descripcion: "Vitaminas para sumar a tu rutina diaria.", precio: 350, imagen: "./catalogo_img/suplementos/4.jpg" },
        ],
        accesorios: [
            { nombre: "Botella deportiva", descripcion: "Botella reutilizable para mantenerte hidratado.", precio: 350, imagen: "./catalogo_img/accesorios/0.jpg" },
            { nombre: "Toalla de gimnasio", descripcion: "Toalla suave y práctica para llevar al gimnasio.", precio: 500, imagen: "./catalogo_img/accesorios/1.jpg" },
            { nombre: "Guantes de entrenamiento", descripcion: "Guantes para cuidar tus manos durante el entrenamiento.", precio: 750, imagen: "./catalogo_img/accesorios/2.jpg" },
            { nombre: "Mochila deportiva", descripcion: "Mochila para llevar tus cosas de entrenamiento.", precio: 1500, imagen: "./catalogo_img/accesorios/3.jpg" },
            { nombre: "Cinturón lumbar", descripcion: "Cinturón para entrenamientos de fuerza.", precio: 900, imagen: "./catalogo_img/accesorios/4.jpg" },
            { nombre: "Bandas elásticas", descripcion: "Bandas para sumar variedad a tus ejercicios.", precio: 450, imagen: "./catalogo_img/accesorios/5.jpg" },
            { nombre: "Rodillera deportiva", descripcion: "Rodillera para entrenar con mayor comodidad.", precio: 650, imagen: "./catalogo_img/accesorios/6.jpg" },
            { nombre: "Muñequera", descripcion: "Muñequera para acompañar tu entrenamiento.", precio: 400, imagen: "./catalogo_img/accesorios/7.jpg" },
            { nombre: "Straps", descripcion: "Colchoneta de ejercicio para piso y estiramiento.", precio: 550, imagen: "./catalogo_img/accesorios/8.jpg" },
        ],
    };

    /**
     * Traduce un producto del servidor al formato que usa la interfaz.
     */
    function productoDeServidor(fila) {
        const id = Number(fila.id_producto);
        const extension = fila.extension ? String(fila.extension) : "";

        return {
            id: id,
            origen: "servidor",
            nombre: String(fila.nombre || ""),
            descripcion: String(fila.descripcion || ""),
            precio: Number(fila.precio || 0),
            stock: Number(fila.stock || 0),
            categoria: categoriaDeTipo(fila.tipo_de_producto),
            // Sin imagen cargada se usa la del catálogo fijo.
            imagen: extension
                ? "./PHPcliente/imagenproducto.php?id=" + encodeURIComponent(id)
                : "./imagenes/OLYMPIA.jpg",
            extension: extension,
        };
    }

    /**
     * Traduce el enumerado del MER a la categoría de la interfaz.
     */
    function categoriaDeTipo(tipo) {
        switch (tipo) {
            case "atuendo":
                return "ropa";
            case "consumible":
                return "suplementos";
            case "accesorio":
                return "accesorios";
            default:
                return "accesorios";
        }
    }

    /**
     * Pide productos al servidor.
     *
     * El endpoint devuelve un objeto suelto cuando se consulta un único
     * producto (`?id=N`) y una lista cuando se pide el catálogo completo, así
     * que ambos formatos se normalizan a una lista.
     */
    function pedirProductos(parametros) {
        return API.get("productos.php", parametros || {})
            .then((datos) => {
                const respuesta = datos.productos || [];
                const lista = Array.isArray(respuesta) ? respuesta : [respuesta];
                return lista.filter((item) => item && item.id_producto).map(productoDeServidor);
            })
            .catch(() => []);
    }

    /**
     * Carga los productos de una categoría (servidor + catálogo fijo).
     *
     * Si el servidor no está disponible, se muestra el catálogo fijo. Si el
     * servidor responde, se suman sus productos salvo los que ya existen en
     * el catálogo fijo con el mismo nombre (para no mostrar duplicados).
     */
    function cargarProductosServidor(categoria) {
        return pedirProductos({ cat: categoria }).then((delServidor) => {
            const fijos = (CATALOGO_FIJO[categoria] || []).map((producto, indice) => ({
                id: "fijo-" + categoria + "-" + indice,
                origen: "fijo",
                indice: indice,
                nombre: producto.nombre,
                descripcion: producto.descripcion,
                precio: producto.precio,
                stock: null,
                categoria: categoria,
                imagen: producto.imagen,
            }));

            const nombresFijos = fijos.map((producto) => producto.nombre.toLowerCase());
            const nuevos = delServidor.filter((producto) => {
                return !nombresFijos.includes(String(producto.nombre || "").toLowerCase());
            });

            return nuevos.concat(fijos);
        });
    }

    /**
     * Carga un producto concreto del catálogo fijo por su posición.
     *
     * Los productos fijos no están en la base de datos, así que no se piden
     * al servidor: se toman del catálogo de la interfaz.
     */
    function cargarProductoFijo(categoria, indice) {
        const lista = CATALOGO_FIJO[categoria] || [];
        const producto = lista[indice];

        if (!producto) {
            return Promise.resolve(null);
        }

        return Promise.resolve({
            id: "fijo-" + categoria + "-" + indice,
            origen: "fijo",
            indice: indice,
            nombre: producto.nombre,
            descripcion: producto.descripcion,
            precio: producto.precio,
            stock: null,
            categoria: categoria,
            imagen: producto.imagen,
        });
    }

    /**
     * Carga un producto concreto por identificador.
     */
    function cargarProductoServidor(id) {
        return pedirProductos({ id: id }).then((lista) => (lista.length ? lista[0] : null));
    }

    return {
        CATEGORIAS: CATEGORIAS,
        CATALOGO_FIJO: CATALOGO_FIJO,
        productoDeServidor: productoDeServidor,
        categoriaDeTipo: categoriaDeTipo,
        pedirProductos: pedirProductos,
        cargarProductosServidor: cargarProductosServidor,
        cargarProductoServidor: cargarProductoServidor,
        cargarProductoFijo: cargarProductoFijo,
    };
})();
