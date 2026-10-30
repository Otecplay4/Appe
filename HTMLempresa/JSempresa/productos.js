/**
 * A.P.P.E. — Gestión de productos (empresa).
 *
 * Requisito funcional RF-08 · Caso de uso CU-05.
 */
(function () {
    "use strict";

    API.configurar("./PHPempresa/");

    const formulario = document.getElementById("formularioProducto");
    const contenedor = document.getElementById("contenedor");
    const mensaje = document.getElementById("productoMensaje");
    const botonCancelar = document.getElementById("cancelarEdicion");
    const tituloForm = document.getElementById("tituloFormProducto");
    const campoId = document.getElementById("idProducto");
    const campoArchivo = document.getElementById("archivoProducto");
    const notaImagen = document.getElementById("notaImagen");

    let idEnEdicion = null;

    const TIPOS = {
        consumible: "Consumible",
        accesorio: "Accesorio",
        atuendo: "Atuendo",
    };

    function salirDeEdicion() {
        idEnEdicion = null;

        if (campoId) {
            campoId.value = "";
        }
        if (formulario) {
            formulario.reset();
        }
        if (campoArchivo) {
            // Al crear, la imagen es obligatoria; al editar es opcional.
            campoArchivo.required = true;
        }
        if (tituloForm) {
            tituloForm.textContent = "Nuevo producto";
        }
        if (botonCancelar) {
            botonCancelar.hidden = true;
        }
        if (notaImagen) {
            notaImagen.textContent = "La imagen es obligatoria al crear un producto.";
        }
    }

    function pintarProducto(producto) {
        if (!contenedor) {
            return;
        }

        const tarjeta = Dom.crear("article", { clases: ["tarjeta"] });

        if (producto.extension) {
            const imagen = Dom.crear("img", {
                clases: ["imagen-producto"],
                atributos: {
                    // El directorio de imágenes está protegido: se pide a
                    // través del endpoint, que valida la sesión.
                    src: "./PHPempresa/imagen.php?tipo=producto&id=" + encodeURIComponent(producto.id_producto),
                    alt: producto.nombre,
                },
            });
            imagen.addEventListener("error", () => {
                imagen.style.display = "none";
            });
            tarjeta.appendChild(imagen);
        } else {
            tarjeta.appendChild(Dom.texto("span", "Sin imagen", { clases: ["imagen-producto", "sin-imagen"] }));
        }
        tarjeta.appendChild(Dom.texto("h3", producto.nombre, { clases: ["nombre-producto"] }));

        if (producto.descripcion) {
            tarjeta.appendChild(Dom.texto("p", producto.descripcion, { clases: ["descripcion-producto"] }));
        }

        tarjeta.appendChild(Dom.texto("span", TIPOS[producto.tipo_de_producto] || producto.tipo_de_producto, { clases: ["categoria"] }));
        tarjeta.appendChild(Dom.texto("span", Dom.precio(producto.precio), { clases: ["precio"] }));
        tarjeta.appendChild(Dom.texto("span", "Stock: " + producto.stock, { clases: ["stock"] }));
        tarjeta.appendChild(Dom.texto("span", producto.activo ? "Publicado" : "Oculto", { clases: ["estado-producto"] }));

        const acciones = Dom.crear("div", { clases: ["acciones"] });

        const botonEditar = Dom.boton("Actualizar", () => {
            idEnEdicion = producto.id_producto;

            if (campoId) {
                campoId.value = String(producto.id_producto);
            }
            if (formulario) {
                formulario.elements["nombre"].value = producto.nombre;
                formulario.elements["descripcion"].value = producto.descripcion || "";
                formulario.elements["precio"].value = producto.precio;
                formulario.elements["stock"].value = producto.stock;
                formulario.elements["tipo_de_producto"].value = producto.tipo_de_producto;
            }
            if (tituloForm) {
                tituloForm.textContent = "Editar producto #" + producto.id_producto;
            }
            if (campoArchivo) {
                // Al editar no hace falta volver a subir la imagen.
                campoArchivo.required = false;
                campoArchivo.value = "";
            }
            if (botonCancelar) {
                botonCancelar.hidden = false;
            }
            if (notaImagen) {
                notaImagen.textContent = "La imagen es opcional al editar: si no subís una nueva se conserva la actual.";
            }

            formulario.scrollIntoView({ behavior: "smooth", block: "center" });
        }, { clases: ["btnActualizar"] });

        const botonEliminar = Dom.boton("Eliminar", () => {
            const confirmar = () => {
                API.post("eliminarpro.php", { id: producto.id_producto })
                    .then(() => {
                        Dom.mensaje(mensaje, "El producto fue eliminado.", "exito");
                        actualizar();
                    })
                    .catch((error) => {
                        Dom.mensaje(mensaje, error.mensaje || "No se pudo eliminar el producto.", "error");
                    });
            };

            if (typeof Swal !== "undefined") {
                Swal.fire({
                    title: "¿Eliminar el producto?",
                    text: "Se borrará la imagen y el registro.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Sí, eliminar",
                    cancelButtonText: "Cancelar",
                }).then((resultado) => {
                    if (resultado.isConfirmed) {
                        confirmar();
                    }
                });
            } else if (window.confirm("¿Eliminar el producto?")) {
                confirmar();
            }
        }, { clases: ["btnEliminar"] });

        acciones.appendChild(botonEditar);
        acciones.appendChild(botonEliminar);
        tarjeta.appendChild(acciones);

        contenedor.appendChild(tarjeta);
    }

    function actualizar() {
        if (!contenedor) {
            return;
        }

        Dom.limpiar(contenedor);

        API.get("mostrarprod.php")
            .then((datos) => {
                const productos = datos.productos || [];

                if (productos.length === 0) {
                    contenedor.appendChild(Dom.texto("p", "Todavía no hay productos cargados.", { clases: ["sin-productos"] }));
                    return;
                }

                productos.forEach(pintarProducto);
            })
            .catch((error) => {
                Dom.mensaje(mensaje, error.mensaje || "No se pudo cargar el listado de productos.", "error");
            });
    }

    if (formulario) {
        formulario.addEventListener("submit", (evento) => {
            evento.preventDefault();

            const datos = new FormData(formulario);
            const nombre = String(datos.get("nombre") || "").trim();

            if (nombre === "") {
                Dom.mensaje(mensaje, "El nombre del producto es obligatorio.", "error");
                return;
            }

            if (idEnEdicion === null) {
                API.postForm("img.php", datos)
                    .then(() => {
                        Dom.mensaje(mensaje, "Producto creado correctamente.", "exito");
                        salirDeEdicion();
                        actualizar();
                    })
                    .catch((error) => {
                        Dom.mensaje(mensaje, error.mensaje || "No se pudo crear el producto.", "error");
                    });
                return;
            }

            datos.set("id", String(idEnEdicion));

            API.postForm("actualizarprod.php", datos)
                .then(() => {
                    Dom.mensaje(mensaje, "Producto actualizado correctamente.", "exito");
                    salirDeEdicion();
                    actualizar();
                })
                .catch((error) => {
                    Dom.mensaje(mensaje, error.mensaje || "No se pudo actualizar el producto.", "error");
                });
        });
    }

    if (botonCancelar) {
        botonCancelar.addEventListener("click", salirDeEdicion);
    }

    actualizar();
})();
