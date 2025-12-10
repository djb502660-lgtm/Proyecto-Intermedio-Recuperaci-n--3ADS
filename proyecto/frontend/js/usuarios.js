import { API_URL } from "./config.js";

const form = document.getElementById("user-form");
const msg = document.getElementById("user-msg");
const tableBody = document.querySelector("#users-table tbody");
const reloadBtn = document.getElementById("reload-btn");

function showMsg(text, type = "info") {
    msg.className = `alert alert-${type}`;
    msg.textContent = text;
    msg.classList.remove("d-none");
}

function clearMsg() {
    msg.classList.add("d-none");
}

async function fetchUsuarios() {
    try {
        const res = await fetch(`${API_URL}?accion=listar_usuarios`);
        const json = await res.json();
        if (json.status !== "success") throw new Error(json.message || "No se pudo cargar");

        tableBody.innerHTML = "";
        json.usuarios.forEach((u) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>${u.id_usuario}</td>
                <td>${u.nombre}</td>
                <td>${u.correo ?? "-"}</td>
                <td>${u.telefono ?? "-"}</td>
            `;
            tableBody.appendChild(tr);
        });
    } catch (err) {
        showMsg(err.message, "danger");
    }
}

async function registrar(data) {
    const body = new FormData();
    body.append("accion", "crear_miembro");
    ["nombre", "correo", "telefono"].forEach((k) => body.append(k, data.get(k) || ""));

    const res = await fetch(API_URL, { method: "POST", body });
    const json = await res.json();
    if (json.status === "success") {
        showMsg("Usuario creado", "success");
        form.reset();
        fetchUsuarios();
    } else {
        showMsg(json.message || "Error al crear usuario", "danger");
    }
}

if (form) {
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        clearMsg();
        await registrar(new FormData(form));
    });
}

if (reloadBtn) {
    reloadBtn.addEventListener("click", fetchUsuarios);
}

document.addEventListener("DOMContentLoaded", fetchUsuarios);

