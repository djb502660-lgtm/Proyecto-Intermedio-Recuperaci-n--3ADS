import { API_URL } from "./config.js";

const loginForm = document.getElementById("login-form");
const registerForm = document.getElementById("register-form");
const loginMsg = document.getElementById("login-msg");
const registerMsg = document.getElementById("register-msg");
const sessionBanner = document.getElementById("session-banner");
const sessionUser = document.getElementById("session-user");
const logoutBtn = document.getElementById("logout-btn");

function showMsg(el, message, type = "info") {
    if (!el) return;
    el.className = `alert alert-${type}`;
    el.textContent = message;
    el.classList.remove("d-none");
}

function hideMsg(el) {
    if (!el) return;
    el.classList.add("d-none");
}

function saveSession(user) {
    localStorage.setItem("usuario", JSON.stringify(user));
    renderSession();
}

function clearSession() {
    localStorage.removeItem("usuario");
    renderSession();
}

function renderSession() {
    const user = JSON.parse(localStorage.getItem("usuario") || "null");
    if (!sessionBanner || !sessionUser) return;
    if (user) {
        sessionUser.textContent = `${user.nombre} (${user.correo})`;
        sessionBanner.style.display = "flex";
    } else {
        sessionBanner.style.display = "none";
    }
}

async function login(data) {
    const body = new FormData();
    body.append("accion", "login");
    body.append("correo", data.get("correo"));
    body.append("password", data.get("password"));

    const res = await fetch(API_URL, { method: "POST", body });
    const json = await res.json();
    if (json.status === "success") {
        saveSession(json.user);
        showMsg(loginMsg, "Inicio de sesión exitoso", "success");
    } else {
        showMsg(loginMsg, json.message || "No se pudo iniciar sesión", "danger");
    }
}

async function register(data) {
    const body = new FormData();
    body.append("accion", "registrar_usuario");
    ["nombre", "correo", "telefono", "password", "clave_admin"].forEach((k) => body.append(k, data.get(k) || ""));

    const res = await fetch(API_URL, { method: "POST", body });
    const json = await res.json();
    if (json.status === "success") {
        showMsg(registerMsg, "Cuenta creada, ahora puedes iniciar sesión", "success");
        registerForm.reset();
    } else {
        showMsg(registerMsg, json.message || "No se pudo registrar", "danger");
    }
}

if (loginForm) {
    loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        hideMsg(loginMsg);
        await login(new FormData(loginForm));
    });
}

if (registerForm) {
    registerForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        hideMsg(registerMsg);
        await register(new FormData(registerForm));
    });
}

if (logoutBtn) {
    logoutBtn.addEventListener("click", clearSession);
}

document.addEventListener("DOMContentLoaded", renderSession);

