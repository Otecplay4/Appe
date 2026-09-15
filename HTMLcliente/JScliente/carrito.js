function leerCarrito() {
    const guardado = localStorage.getItem("carrito")

    if (!guardado) {
        return []
    }

    return JSON.parse(guardado)
}

function guardarCarrito(carrito) {
    localStorage.setItem("carrito", JSON.stringify(carrito))
}

function actualizarNumeroCarrito() {
    const numero = document.getElementById("numeroCarrito")

    if (numero) {
        const carrito = leerCarrito()
        let cantidad = 0

        carrito.forEach(producto => {
            cantidad = cantidad + producto.cantidad
        })

        numero.textContent = cantidad
    }
}

function agregarAlCarrito(producto, categoria) {
    const carrito = leerCarrito()
    const clave = categoria + "-" + producto.nombre
    const encontrado = carrito.find(item => item.clave === clave)

    if (encontrado) {
        encontrado.cantidad = encontrado.cantidad + 1
    } else {
        carrito.push({
            clave: clave,
            categoria: categoria,
            nombre: producto.nombre,
            imagen: producto.imagen,
            alt: producto.alt,
            cantidad: 1
        })
    }

    guardarCarrito(carrito)
    actualizarNumeroCarrito()
}

function borrarDelCarrito(clave) {
    const carrito = leerCarrito()
    const nuevoCarrito = carrito.filter(item => item.clave !== clave)

    guardarCarrito(nuevoCarrito)
    mostrarCarrito()
    actualizarNumeroCarrito()
}

function mostrarCarrito() {
    const lista = document.getElementById("listaCarrito")

    if (!lista) {
        return
    }

    const carrito = leerCarrito()
    lista.innerHTML = ""

    if (!carrito.length) {
        const aviso = document.createElement("p")
        aviso.className = "carrito-vacio"
        aviso.textContent = "Todavía no agregaste productos al carrito."
        lista.append(aviso)
        return
    }

    carrito.forEach(item => {
        const fila = document.createElement("article")
        fila.className = "fila-carrito"

        const foto = document.createElement("img")
        foto.src = item.imagen
        foto.alt = item.alt

        const info = document.createElement("div")

        const categoria = document.createElement("p")
        categoria.className = "categoria-carrito"
        categoria.textContent = item.categoria

        const nombre = document.createElement("h2")
        nombre.textContent = item.nombre

        const cantidad = document.createElement("p")
        cantidad.textContent = "Cantidad: " + item.cantidad

        const quitar = document.createElement("button")
        quitar.className = "boton-quitar"
        quitar.type = "button"
        quitar.textContent = "Quitar"
        quitar.addEventListener("click", () => borrarDelCarrito(item.clave))

        info.append(categoria, nombre, cantidad, quitar)
        fila.append(foto, info)
        lista.append(fila)
    })
}

actualizarNumeroCarrito()
mostrarCarrito()
