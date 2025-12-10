<?php
// Punto de entrada CRUD para la tabla `prestamos`.
// Devuelve JSON y usa PDO compartido.

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../dbconexion/db_conexion.php";

$conn = dbconexion::conectar();
$accion = $_POST["action"] ?? $_GET["action"] ?? "";
$primaryKey = "id_prestamos"; // coincide con el dump SQL

function responder(bool $success, array $payload = [], int $status = 200): void
{
    http_response_code($status);
    echo json_encode(["success" => $success] + $payload);
    exit();
}

// Crear préstamo
if ($accion === "crear_prestamo") {
    $id_usuario = (int)($_POST["id_usuario"] ?? 0);
    $id_libro = (int)($_POST["id_libro"] ?? 0);
    $fecha_prestamo = trim($_POST["fecha_prestamo"] ?? "");
    $fecha_devolucion = trim($_POST["fecha_devolucion"] ?? "");
    $estado = trim($_POST["estado"] ?? "pendiente");

    if (!$id_usuario || !$id_libro || !$fecha_prestamo) {
        responder(false, ["message" => "Datos incompletos."], 400);
    }

    try {
        $sql = "INSERT INTO prestamos (id_usuario, id_libro, fecha_prestamo, fecha_devolucion, estado)
                VALUES (:id_usuario, :id_libro, :fecha_prestamo, :fecha_devolucion, :estado)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":id_libro", $id_libro, PDO::PARAM_INT);
        $stmt->bindParam(":fecha_prestamo", $fecha_prestamo);
        $stmt->bindParam(":fecha_devolucion", $fecha_devolucion);
        $stmt->bindParam(":estado", $estado);
        $stmt->execute();

        responder(true, ["message" => "Préstamo registrado."]);
    } catch (Exception $e) {
        responder(false, ["message" => $e->getMessage()], 500);
    }
}

// Listar préstamos (con nombres de usuario/libro)
if ($accion === "mostrar_prestamos") {
    try {
        $sql = "SELECT p.$primaryKey, p.id_usuario, p.id_libro, p.fecha_prestamo, p.fecha_devolucion, p.estado,
                       u.nombre AS usuario, l.titulo AS libro
                FROM prestamos p
                INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                INNER JOIN libros l ON p.id_libro = l.id_libro
                ORDER BY p.$primaryKey DESC";
        $stmt = $conn->query($sql);
        responder(true, ["data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        responder(false, ["message" => $e->getMessage()], 500);
    }
}

// Obtener préstamo por ID
if ($accion === "obtener_prestamo") {
    $id = (int)($_GET[$primaryKey] ?? 0);

    if (!$id) {
        responder(false, ["message" => "ID requerido."], 400);
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM prestamos WHERE $primaryKey = :id LIMIT 1");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        responder(true, ["data" => $stmt->fetch(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        responder(false, ["message" => $e->getMessage()], 500);
    }
}

// Editar préstamo
if ($accion === "editar_prestamo") {
    $id = (int)($_POST[$primaryKey] ?? 0);
    $id_usuario = (int)($_POST["id_usuario"] ?? 0);
    $id_libro = (int)($_POST["id_libro"] ?? 0);
    $fecha_prestamo = trim($_POST["fecha_prestamo"] ?? "");
    $fecha_devolucion = trim($_POST["fecha_devolucion"] ?? "");
    $estado = trim($_POST["estado"] ?? "");

    if (!$id || !$id_usuario || !$id_libro) {
        responder(false, ["message" => "Datos incompletos."], 400);
    }

    try {
        $sql = "UPDATE prestamos
                SET id_usuario = :id_usuario,
                    id_libro = :id_libro,
                    fecha_prestamo = :fecha_prestamo,
                    fecha_devolucion = :fecha_devolucion,
                    estado = :estado
                WHERE $primaryKey = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":id_libro", $id_libro, PDO::PARAM_INT);
        $stmt->bindParam(":fecha_prestamo", $fecha_prestamo);
        $stmt->bindParam(":fecha_devolucion", $fecha_devolucion);
        $stmt->bindParam(":estado", $estado);
        $stmt->execute();

        responder(true, ["message" => "Préstamo actualizado."]);
    } catch (Exception $e) {
        responder(false, ["message" => $e->getMessage()], 500);
    }
}

// Eliminar préstamo
if ($accion === "eliminar_prestamo") {
    $id = (int)($_POST[$primaryKey] ?? 0);

    if (!$id) {
        responder(false, ["message" => "ID requerido."], 400);
    }

    try {
        $stmt = $conn->prepare("DELETE FROM prestamos WHERE $primaryKey = :id LIMIT 1");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        responder(true, ["message" => "Préstamo eliminado."]);
    } catch (Exception $e) {
        responder(false, ["message" => $e->getMessage()], 500);
    }
}

// Acción no reconocida
responder(false, ["message" => "Acción no válida."], 400);
