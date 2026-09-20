
let form = document.getElementById("formulario")
form.addEventListener("submit", (e) => {

    e.preventDefault()
    let f = new FormData(form)

    fetch("./PHPempresa/img.php", {
        method: "POST",
        body: f
    }) .then(r => r.text())
        .then(datos => {
            console.log(datos);
            
        })

    

})

function actualizar(){
    fetch("./PHPempresa/mostrarprod.php")

        .then(r => r.json())
        .then(datos => {
            console.log(datos)
            contenedor.innerHTML =""
            datos.forEach(pj => {

                contenedor.innerHTML += `
                    <div>
                        <h2>${pj.nombre}</h2>
                        <img src=./img/${pj.id}.jpg>
                        <button onclick=eliminar(${pj.id})>X</button>
                        <button onclick=actualizar(${pj.id})>Actualizar</button>
                    </div>`
            })

        })
}

actualizar()

function eliminar (idu){
    fetch("./PHPempresa/eliminarpro.php?id="+idu)
    .then(r=>r.text())
    .then(datos=>{
        console.log(datos)
        actualizar()
    })
}

function cambioimg(){
    fetch("./PHPempresa/actualizarprod.php")

        .then(r => r.json())
        .then(datos => {
            console.log(datos)
            contenedor.innerHTML =""
            datos.forEach(pj => {

                contenedor.innerHTML += `
                    <div>
                        <h2>${pj.nombre}</h2>
                        <img src=./img/${pj.id}.jpg>
                        <button onclick=eliminar(${pj.id})>X</button>
                        <button onclick=actualizar(${pj.id})>Actualizar</button>
                    </div>`
            })

        })
}
//consumibles
let form = document.getElementById("formulario")
form.addEventListener("submit", (e) => {

    e.preventDefault()
    let f = new FormData(form)

    fetch("./PHPempresa/img.php", {
        method: "POST",
        body: f
    }) .then(r => r.text())
        .then(datos => {
            console.log(datos);
            
        })

    

})

function actualizar(){
    fetch("./PHPempresa/mostrarprod.php")

        .then(r => r.json())
        .then(datos => {
            console.log(datos)
            contenedor.innerHTML =""
            datos.forEach(pj => {

                contenedor.innerHTML += `
                    <div>
                        <h2>${pj.nombre}</h2>
                        <img src=./img/${pj.id}.jpg>
                        <button onclick=eliminar(${pj.id})>X</button>
                        <button onclick=actualizar(${pj.id})>Actualizar</button>
                    </div>`
            })

        })
}

actualizar()

function eliminar (idu){
    fetch("./PHPempresa/eliminarpro.php?id="+idu)
    .then(r=>r.text())
    .then(datos=>{
        console.log(datos)
        actualizar()
    })
}

function cambioimg(){
    fetch("./PHPempresa/actualizprod.php")

        .then(r => r.json())
        .then(datos => {
            console.log(datos)
            contenedor.innerHTML =""
            datos.forEach(pj => {

                contenedor.innerHTML += `
                    <div>
                        <h2>${pj.nombre}</h2>
                        <img src=./img/${pj.id}.jpg>
                        <button onclick=eliminar(${pj.id})>X</button>
                        <button onclick=actualizar(${pj.id})>Actualizar</button>
                    </div>`
            })

        })
}

//accesorios

let formulario = document.getElementById("formulario")
form.addEventListener("submit", (e) => {

    e.preventDefault()
    let f = new FormData(form)

    fetch("./PHPempresa/img.php", {
        method: "POST",
        body: f
    }) .then(r => r.text())
        .then(datos => {
            console.log(datos);
            
        })

    

})

function actualizar(){
    fetch("./PHPempresa/mostrarprod.php")

        .then(r => r.json())
        .then(datos => {
            console.log(datos)
            contenedor.innerHTML =""
            datos.forEach(pj => {

                contenedor.innerHTML += `
                    <div>
                        <h2>${pj.nombre}</h2>
                        <img src=./img/${pj.id}.jpg>
                        <button onclick=eliminar(${pj.id})>X</button>
                        <button onclick=actualizar(${pj.id})>Actualizar</button>
                    </div>`
            })

        })
}

actualizar()

function eliminar (idu){
    fetch("./PHPempresa/eliminarpro.php?id="+idu)
    .then(r=>r.text())
    .then(datos=>{
        console.log(datos)
        actualizar()
    })
}

function cambioimg(){
    fetch("./PHPempresa/actualizprod.php")

        .then(r => r.json())
        .then(datos => {
            console.log(datos)
            contenedor.innerHTML =""
            datos.forEach(pj => {

                contenedor.innerHTML += `
                    <div>
                        <h2>${pj.nombre}</h2>
                        <img src=./img/${pj.id}.jpg>
                        <button onclick=eliminar(${pj.id})>X</button>
                        <button onclick=actualizar(${pj.id})>Actualizar</button>
                    </div>`
            })

        })
}

//atuendos

let form = document.getElementById("formulario")
form.addEventListener("submit", (e) => {

    e.preventDefault()
    let f = new FormData(form)

    fetch("./PHPempresa/img.php", {
        method: "POST",
        body: f
    }) .then(r => r.text())
        .then(datos => {
            console.log(datos);
            
        })

    

})

function actualizar(){
    fetch("./PHPempresa/mostrarprod.php")

        .then(r => r.json())
        .then(datos => {
            console.log(datos)
            contenedor.innerHTML =""
            datos.forEach(pj => {

                contenedor.innerHTML += `
                    <div>
                        <h2>${pj.nombre}</h2>
                        <img src=./img/${pj.id}.jpg>
                        <button onclick=eliminar(${pj.id})>X</button>
                        <button onclick=actualizar(${pj.id})>Actualizar</button>
                    </div>`
            })

        })
}

actualizar()

function eliminar (idu){
    fetch("./PHPempresa/eliminarpro.php?id="+idu)
    .then(r=>r.text())
    .then(datos=>{
        console.log(datos)
        actualizar()
    })
}

function cambioimg(){
    fetch("./PHPempresa/actualizprod.php")

        .then(r => r.json())
        .then(datos => {
            console.log(datos)
            contenedor.innerHTML =""
            datos.forEach(pj => {

                contenedor.innerHTML += `
                    <div>
                        <h2>${pj.nombre}</h2>
                        <img src=./img/${pj.id}.jpg>
                        <button onclick=eliminar(${pj.id})>X</button>
                        <button onclick=actualizar(${pj.id})>Actualizar</button>
                    </div>`
            })

        })
}