<?php
// Punto de entrada para operaciones CRUD sobre la tabla `libros`.
// Devuelve respuestas JSON y reutiliza la conexión PDO centralizada.

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../dbconexion/db_conexion.php";

$conn = dbconexion::conectar();
$accion = $_POST["action"] ?? $_GET["action"] ?? "";

function responder(bool $success, array $payload = [], int $status = 200): void
{
    http_response_code($status);
    echo json_encode(["success" => $success] + $payload);
    exit();
}

// Crear libro
if ($accion === "crear_libro") {
    $titulo = trim($_POST["titulo"] ?? "");
    $autor = trim($_POST["autor"] ?? "");
    $anio = (int)($_POST["anio"] ?? 0);
    $categoria = trim($_POST["categoria"] ?? "");
    $stock = (int)($_POST["stock"] ?? 0);

    if (!$titulo || !$autor || !$anio) {
        responder(false, ["message" => "Datos incompletos."], 400);
    }

    try {
        $sql = "INSERT INTO libros (titulo, autor, anio, categoria, stock)
                VALUES (:titulo, :autor, :anio, :categoria, :stock)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":titulo", $titulo);
        $stmt->bindParam(":autor", $autor);
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":categoria", $categoria);
        $stmt->bindParam(":stock", $stock, PDO::PARAM_INT);
        $stmt->execute();

        responder(true, ["message" => "Libro agregado."]);
    } catch (Exception $e) {
        responder(false, ["message" => $e->getMessage()], 500);
    }
}

// Listar libros
if ($accion === "mostrar_libros") {
    try {
        $stmt = $conn->query("SELECT * FROM libros ORDER BY id_libro DESC");
        responder(true, ["data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        responder(false, ["message" => $e->getMessage()], 500);
    }
}

// Obtener libro por ID
if ($accion === "obtener_libro") {
    $id = (int)($_GET["id_libro"] ?? 0);

    if (!$id) {
        responder(false, ["message" => "ID requerido."], 400);
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM libros WHERE id_libro = :id LIMIT 1");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        responder(true, ["data" => $stmt->fetch(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        responder(false, ["message" => $e->getMessage()], 500);
    }
}

// Editar libro
if ($accion === "editar_libro") {
    $id = (int)($_POST["id_libro"] ?? 0);
    $titulo = trim($_POST["titulo"] ?? "");
    $autor = trim($_POST["autor"] ?? "");
    $anio = (int)($_POST["anio"] ?? 0);
    $categoria = trim($_POST["categoria"] ?? "");
    $stock = (int)($_POST["stock"] ?? 0);

    if (!$id || !$titulo || !$autor || !$anio) {
        responder(false, ["message" => "Datos incompletos."], 400);
    }

    try {
        $sql = "UPDATE libros
                SET titulo = :titulo,
                    autor = :autor,
                    anio = :anio,
                    categoria = :categoria,
                    stock = :stock
                WHERE id_libro = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":titulo", $titulo);
        $stmt->bindParam(":autor", $autor);
        $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
        $stmt->bindParam(":categoria", $categoria);
        $stmt->bindParam(":stock", $stock, PDO::PARAM_INT);
        $stmt->execute();

        responder(true, ["message" => "Libro actualizado."]);
    } catch (Exception $e) {
        responder(false, ["message" => $e->getMessage()], 500);
    }
}

// Eliminar libro
if ($accion === "eliminar_libro") {
    $id = (int)($_POST["id_libro"] ?? 0);

    if (!$id) {
        responder(false, ["message" => "ID requerido."], 400);
    }

    try {
        $stmt = $conn->prepare("DELETE FROM libros WHERE id_libro = :id LIMIT 1");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        responder(true, ["message" => "Libro eliminado."]);
    } catch (Exception $e) {
        responder(false, ["message" => $e->getMessage()], 500);
    }
}

// Acción no reconocida
responder(false, ["message" => "Acción no válida."], 400);
