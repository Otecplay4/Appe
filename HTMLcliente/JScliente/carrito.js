/**
 * A.P.P.E. — Carrito de compras (almacenamiento local).
 *
 * El carrito es local al navegador: es una simulación, no hay pasarela de
 * pago. El contenido se inserta siempre con `textContent`.
 */
const Carrito = (() => {
    "use strict";

    const CLAVE = "appe_carrito";

    if (typeof API !== "undefined") {
        API.configurar("./PHPcliente/");
    }

    let comprando = false;

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
        const cat = categoria || producto.categoria || "";
        const lista = leer();
        const id = clave({ id: producto.id, categoria: cat });

        const ejecutarAgregar = () => {
            let nuevaLista = leer();

            // Si es un plan, eliminamos cualquier plan anterior para dejar uno solo
            if (cat === "planes") {
                nuevaLista = nuevaLista.filter((item) => item.categoria !== "planes");
            }

            const existente = nuevaLista.find((item) => item.clave === id);

            if (existente) {
                // Solo los planes se mantienen en cantidad 1. Los productos sí se stackean.
                if (cat === "planes") {
                    existente.cantidad = 1;
                } else {
                    existente.cantidad = (Number(existente.cantidad) || 0) + 1;
                }
            } else {
                nuevaLista.push({
                    clave: id,
                    id: producto.id,
                    nombre: producto.nombre,
                    precio: Number(producto.precio || 0),
                    imagen: producto.imagen || "",
                    categoria: cat,
                    cantidad: 1,
                    id_plan: producto.id_plan || null,
                    periodo: producto.periodo || null,
                });
            }

            guardar(nuevaLista);
            actualizarNumero();
            mostrar();

            if (typeof Swal !== "undefined") {
                Swal.fire({
                    title: "Agregado",
                    text: producto.nombre + " se agregó al carrito." + (cat === "planes" ? " Confirmá la compra desde el carrito." : ""),
                    icon: "success",
                    timer: cat === "planes" ? 2200 : 1500,
                });
            }
        };

        // Aviso solo para planes en caso de querer cambiar el seleccionado para el carrito
        if (cat === "planes") {
            const planExistente = lista.find((item) => item.categoria === "planes");

            if (planExistente && planExistente.nombre !== producto.nombre) {
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        title: "¿Seguro desea reemplazar su plan?",
                        text: "Ya tienes seleccionado '" + planExistente.nombre + "'. ¿Deseas reemplazarlo por '" + producto.nombre + "'?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Sí, reemplazar",
                        cancelButtonText: "Cancelar"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            ejecutarAgregar();
                        }
                    });
                    return;
                } else {
                    if (window.confirm("¿Seguro desea reemplazar su plan actual (" + planExistente.nombre + ") por " + producto.nombre + "?")) {
                        ejecutarAgregar();
                        return;
                    } else {
                        return;
                    }
                }
            }
        }

        // Si no es plan, o es el mismo plan exacto, se ejecuta directo
        ejecutarAgregar();
    }

    function quitar(item) {
        guardar(leer().filter((otro) => otro.clave !== item.clave));
        actualizarNumero();
        mostrar();
    }

    /**
     * Datos que el servidor necesita para contratar el plan del carrito.
     * Los carritos guardados antes de este cambio no traen `id_plan` ni
     * `periodo`: se deducen del id («plan-3») y del nombre («Plan · Anual»).
     */
    function datosPlan(item) {
        const idPlan = Number(item.id_plan) || parseInt(String(item.id).replace("plan-", ""), 10) || 0;
        let periodo = item.periodo ? String(item.periodo).toLowerCase() : "";

        if (!periodo) {
            const partes = String(item.nombre || "").split("·");
            periodo = partes.length > 1 ? partes[partes.length - 1].trim().toLowerCase() : "mensual";
        }

        return { id_plan: idPlan, periodo: periodo };
    }

    function procesarCompra() {
        const items = leer();
        if (items.length === 0 || comprando) {
            return;
        }

        const confirmar = () => {
            if (typeof Swal !== "undefined") {
                return Swal.fire({
                    title: "¿Desea confirmar su compra?",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Sí",
                    cancelButtonText: "No"
                }).then((resultado) => resultado.isConfirmed);
            }

            return Promise.resolve(window.confirm("¿Desea confirmar su compra?"));
        };

        const permitido = typeof Auth !== "undefined"
            ? Auth.requerirLogin("Necesitás iniciar sesión para completar la compra.")
            : Promise.resolve(true);

        permitido.then((puede) => {
            if (!puede) {
                return;
            }

            confirmar().then((aceptado) => {
                if (aceptado) {
                    finalizarCompra();
                }
            });
        });
    }

    function avisar(config, respaldo) {
        if (typeof Swal !== "undefined") {
            Swal.fire(config);
        } else {
            window.alert(respaldo);
        }
    }

    function finalizarCompra() {
        const items = leer();
        const plan = items.find((item) => item.categoria === "planes");

        comprando = true;

        // El plan se registra en el servidor (cliente, vigencia y cuota). Los
        // productos siguen siendo una simulación local.
        const registro = plan
            ? API.post("comprarplan.php", datosPlan(plan))
            : Promise.resolve();

        registro
            .then(() => {
                guardar([]);
                actualizarNumero();
                mostrar();

                if (plan) {
                    avisar({
                        title: "¡Compra realizada!",
                        text: "Ya puedes ver tu plan y vencimiento en tu perfil.",
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false,
                    }, "¡Compra realizada! Ya puedes ver tu plan en tu perfil.");

                    setTimeout(() => {
                        window.location.href = "./perfil.html";
                    }, 2000);
                } else {
                    avisar({
                        title: "Compra realizada",
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false,
                    }, "Compra realizada");
                }
            })
            .catch((error) => {
                // El carrito se conserva para poder reintentar.
                const mensaje = error && error.mensaje ? error.mensaje : "No se pudo completar la compra.";
                avisar({ title: "No se pudo comprar", text: mensaje, icon: "error" }, mensaje);
            })
            .then(() => {
                comprando = false;
            });
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

        const botonComprar = Dom.boton("Comprar", () => {
            procesarCompra();
        }, { clases: ["boton-comprar", "boton-accion-carrito"] });

        lista.appendChild(botonComprar);
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