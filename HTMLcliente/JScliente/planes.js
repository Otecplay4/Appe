const MOBILE_BREAKPOINT = 640

document.querySelectorAll(".caja-plan").forEach(banner => {
    banner.addEventListener("click", (e) => {
        // En mobile el banner es un link real hacia plan-1.html / plan-2.html,
        // así que dejamos que navegue normalmente.
        if(window.innerWidth <= MOBILE_BREAKPOINT) return

        e.preventDefault()

        const fila = banner.closest(".fila-plan")
        if(!fila) return

        const yaAbierta = fila.classList.contains("abierta")

        // Si querés que se pueda tener más de una fila abierta a la vez,
        // borrá este bloque que cierra las demás filas.
        document.querySelectorAll(".fila-plan.abierta").forEach(otra => {
            if(otra !== fila){
                otra.classList.remove("abierta")
                otra.querySelector(".caja-plan").setAttribute("aria-expanded", "false")
                otra.querySelector(".detalle-plan").setAttribute("aria-hidden", "true")
            }
        })

        fila.classList.toggle("abierta", !yaAbierta)
        banner.setAttribute("aria-expanded", String(!yaAbierta))
        fila.querySelector(".detalle-plan").setAttribute("aria-hidden", String(yaAbierta))
    })
})
