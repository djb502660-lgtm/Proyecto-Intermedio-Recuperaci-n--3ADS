// Limpia la sesión local y regresa al login
function doLogout() {
    localStorage.removeItem("usuario");
    window.location.href = "../auth/login.html";
}

const logoutBtn = document.getElementById("logout-btn");
if (logoutBtn) {
    logoutBtn.addEventListener("click", doLogout);
}

