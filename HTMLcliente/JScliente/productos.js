const contenedor = document.getElementById("productosGrid")

function crearTarjetaProducto(producto, categoria, numero) {
    const tarjeta = document.createElement("a")
    tarjeta.className = "caja-producto"
    tarjeta.href = "producto.html?cat=" + categoria + "&id=" + numero
    tarjeta.setAttribute("aria-label", "Ver " + producto.nombre)

    const imagen = document.createElement("img")
    imagen.className = "foto-producto"
    imagen.src = producto.imagen
    imagen.alt = producto.alt
    imagen.loading = "lazy"

    const info = document.createElement("div")
    info.className = "info-producto"

    const nombre = document.createElement("h2")
    nombre.className = "nombre-producto"
    nombre.textContent = producto.nombre

    info.append(nombre)
    tarjeta.append(imagen, info)
    return tarjeta
}

if (contenedor) {
    const datos = new URLSearchParams(location.search)
    const categoria = datos.get("cat")
    const productos = CATALOGO[categoria] || []

    const titulo = document.getElementById("productosTitulo")
    if (titulo && categoria) {
        titulo.textContent = categoria.charAt(0).toUpperCase() + categoria.slice(1)
    }

    if (!productos.length) {
        const vacio = document.createElement("p")
        vacio.className = "sin-productos"
        vacio.textContent = "Esta categoría todavía no tiene productos."
        contenedor.append(vacio)
    } else {
        productos.forEach((producto, numero) => {
            contenedor.append(crearTarjetaProducto(producto, categoria, numero))
        })
    }
}
