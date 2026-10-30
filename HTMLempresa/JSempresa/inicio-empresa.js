/**
 * A.P.P.E. — Menú de administración: acceso a las solicitudes de registro.
 */
// «Solicitudes de registro» solo existe para el Administrador; además
// muestra cuántas hay pendientes.
(function () {
    API.configurar("./PHPempresa/");
    API.get("check.php").then((sesion) => {
        const enlaceRegistro = document.querySelector('a[href="solicitudesregistro.html"]');
        if (!sesion || !sesion.exito || sesion.rol !== "admin") {
            if (enlaceRegistro) { enlaceRegistro.style.display = "none"; }
            return;
        }
        API.get("solicitudesregistro.php", { accion: "listar", estado: "pendiente" })
            .then((datos) => {
                const n = (datos.solicitudes || []).length;
                if (n > 0 && enlaceRegistro) {
                    enlaceRegistro.textContent = "Solicitudes de registro (" + n + " pendientes)";
                }
            })
            .catch(() => { });
    }).catch(() => { });
})();
