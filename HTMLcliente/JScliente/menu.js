const menuToggle = document.getElementById("menuToggle")
const navMenu = document.getElementById("navMenu")


if(menuToggle && navMenu){
    menuToggle.addEventListener("click", () => {
        menuToggle.classList.toggle("activo")
        navMenu.classList.toggle("abierto")

        const abierto = navMenu.classList.contains("abierto")
        menuToggle.setAttribute("aria-expanded", abierto)
    })

    navMenu.querySelectorAll(".nav-boton").forEach(boton => {
        boton.addEventListener("click", () => {
            menuToggle.classList.remove("activo")
            navMenu.classList.remove("abierto")
            menuToggle.setAttribute("aria-expanded", "false")
        })
    })
}

// Selección de duración: un solo botón activo por plan. Usa delegación para
// funcionar también con los botones que se crean dinámicamente.
document.addEventListener("click", (e) => {
    const boton = e.target.closest(".boton-tiempo")
    if (!boton) return

    const grupo = boton.closest(".grilla-detalle, .grilla-tiempos") || document
    grupo.querySelectorAll(".boton-tiempo").forEach(b => b.classList.remove("seleccionado"))
    boton.classList.add("seleccionado")
})
