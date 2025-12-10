import { API_URL } from "./config.js";

const LIBROS_URL = "http://localhost/Proyecto-Intermedio-Recuperaci-n--3ADS/proyecto/backend/query/libros.php";
// --- ELEMENTOS DEL DOM ---
const tablaLibros = document.getElementById("tablaLibros");
const formAgregar = document.getElementById("formAgregarLibro");
const formEditar = document.getElementById("formEditarLibro");
const formEliminar = document.getElementById("formEliminarLibro");

/**
 * Carga y muestra los libros en la tabla.
 */
async function cargarLibros() {
    try {
        const response = await fetch(`${LIBROS_URL}?action=mostrar_libros`);
        
        // Clonamos la respuesta para poder leerla dos veces (una como texto, otra como json)
        const responseClone = response.clone();
        
        let data;
        try {
            data = await response.json();
        } catch (jsonError) {
            // Si falla el parseo de JSON, leemos la respuesta como texto.
            const errorText = await responseClone.text();
            console.error("La respuesta del servidor no es un JSON válido:", errorText);
            throw new Error("El servidor devolvió un error inesperado. Revisa la consola del navegador para ver los detalles del backend.");
        }

        const tbody = tablaLibros.querySelector("tbody");
        tbody.innerHTML = ""; // Limpiar tabla

        if (data.success && data.data?.length > 0) {
            const rows = data.data.map(libro => `
                <tr>
                    <td>${libro.id_libro}</td>
                    <td>${libro.titulo}</td>
                    <td>${libro.autor}</td>
                    <td>${libro.anio}</td>
                    <td>${libro.categoria}</td>
                    <td>${libro.stock}</td>
                    <td class="text-center">
                        <a href="editar_libro.html?id=${libro.id_libro}" class="btn btn-warning btn-sm">✏️ Editar</a>
                        <a href="eliminar_libro.html?id=${libro.id_libro}" class="btn btn-danger btn-sm">🗑️ Eliminar</a>
                        <a href="ver_libro.html?id=${libro.id_libro}" class="btn btn-secondary btn-sm">👁️ Ver</a>
                    </td>
                </tr>
            `).join('');
            tbody.innerHTML = rows;
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center">${data.message || "No hay libros registrados."}</td></tr>`;
        }
    } catch (error) {
        console.error("Error al cargar los libros:", error);
        const tbody = tablaLibros.querySelector("tbody");
        // Mostramos un mensaje más descriptivo
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger"><b>Error al cargar los datos:</b> ${error.message}</td></tr>`;
    }
}

/**
 * Carga los datos de un libro específico en el formulario de edición.
 * @param {string} id - El ID del libro a cargar.
 */
async function cargarLibroParaEditar(id) {
    try {
        const res = await fetch(`${LIBROS_URL}?action=obtener_libro&id_libro=${id}`);
        const data = await res.json();

        if (data.success && data.data) {
            const libro = data.data;
            document.getElementById("id_libro").value = libro.id_libro;
            document.getElementById("titulo").value = libro.titulo;
            document.getElementById("autor").value = libro.autor;
            document.getElementById("anio").value = libro.anio;
            document.getElementById("categoria").value = libro.categoria;
            document.getElementById("stock").value = libro.stock;
        } else {
            alert(data.message || "No se pudieron cargar los datos del libro.");
            window.location.href = "libros.html";
        }
    } catch (error) {
        console.error("Error al cargar el libro:", error);
        alert("Error de conexión al cargar los datos del libro.");
    }
}

/**
 * Envía los datos de un formulario a la API.
 * @param {Event} e - El evento de submit del formulario.
 */
async function enviarFormulario(e) {
    e.preventDefault();
    const formData = new FormData(e.target);

    try {
        const res = await fetch(LIBROS_URL, { method: "POST", body: formData });
        const data = await res.json();

        alert(data.message || "Operación procesada.");

        if (data.success) {
            window.location.href = "libros.html";
        }
    } catch (error) {
        console.error("Error al enviar el formulario:", error);
        alert("Error de conexión con el servidor.");
    }
}

// --- LÓGICA DE EJECUCIÓN ---

document.addEventListener("DOMContentLoaded", () => {
    const params = new URLSearchParams(window.location.search);
    const id = params.get("id");

    // Si estamos en la página de listar libros, los cargamos.
    if (tablaLibros) {
        cargarLibros();
    }

    // Si estamos en la página de agregar libro, configuramos el formulario.
    if (formAgregar) {
        formAgregar.addEventListener("submit", enviarFormulario);
    }

    // Si estamos en la página de editar libro, cargamos los datos y configuramos el formulario.
    if (formEditar) {
        if (id) {
            cargarLibroParaEditar(id);
            formEditar.addEventListener("submit", enviarFormulario);
        } else {
            alert("No se especificó un ID de libro para editar.");
            window.location.href = "libros.html";
        }
    }

    // Si estamos en la página de eliminar libro, configuramos el formulario.
    if (formEliminar && id) {
        document.getElementById("id_libro").value = id;
        formEliminar.addEventListener("submit", enviarFormulario);
    }
});
