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