const cajaDetalle = document.getElementById("detalleProducto")
const datos = new URLSearchParams(location.search)
const categoria = datos.get("cat")
const numero = Number(datos.get("id"))
const productos = CATALOGO[categoria] || []
const producto = productos[numero]

function nombreCategoria(texto) {
    return texto.charAt(0).toUpperCase() + texto.slice(1)
}

if (cajaDetalle && producto) {
    document.title = producto.nombre + " | Olympia Gym"

    const foto = document.createElement("img")
    foto.className = "foto-grande"
    foto.src = producto.imagen
    foto.alt = producto.alt

    const info = document.createElement("div")
    info.className = "info-grande"

    const tipo = document.createElement("p")
    tipo.className = "tipo-producto"
    tipo.textContent = nombreCategoria(categoria)

    const nombre = document.createElement("h1")
    nombre.className = "nombre-grande"
    nombre.textContent = producto.nombre

    const descripcion = document.createElement("p")
    descripcion.className = "descripcion-producto"
    descripcion.textContent = producto.descripcion

    const texto = document.createElement("p")
    texto.className = "texto-producto"
    texto.textContent = "Precio a consultar en el gimnasio."

    const botones = document.createElement("div")
    botones.className = "botones-compra"

    const agregar = document.createElement("button")
    agregar.className = "boton-agregar"
    agregar.type = "button"
    agregar.textContent = "Agregar al carrito"
    agregar.addEventListener("click", () => {
        agregarAlCarrito(producto, categoria)
        agregar.textContent = "Agregado al carrito"
    })

    const comprar = document.createElement("button")
    comprar.className = "boton-comprar"
    comprar.type = "button"
    comprar.textContent = "Comprar ahora"
    comprar.addEventListener("click", () => {
        agregarAlCarrito(producto, categoria)
        location.href = "carrito.html"
    })

    const volver = document.createElement("a")
    volver.className = "boton-producto"
    volver.href = "producto-categoria.html?cat=" + categoria
    volver.textContent = "← Volver a " + nombreCategoria(categoria)

    botones.append(agregar, comprar)
    info.append(tipo, nombre, descripcion, texto, botones, volver)
    cajaDetalle.append(foto, info)
} else if (cajaDetalle) {
    const aviso = document.createElement("p")
    aviso.className = "sin-productos"
    aviso.textContent = "No encontramos este producto."
    cajaDetalle.append(aviso)
}
