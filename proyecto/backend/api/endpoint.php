<?php
// API unificada para estadísticas, usuarios y autenticación
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../dbconexion/db_conexion.php";

// Clave requerida para registrar administradores (puedes cambiarla)
const ADMIN_KEY = "biblioteca123";

$accion = $_GET["accion"] ?? $_POST["accion"] ?? null;

if (!$accion) {
    echo json_encode(["status" => "error", "message" => "No se especificó una acción."]);
    exit;
}

try {
    $conn = dbconexion::conectar();
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Error de conexión: " . $e->getMessage()]);
    exit;
}

/**
 * Garantiza que exista la columna password_hash en la tabla usuarios.
 */
function asegurarColumnaPassword(PDO $conn): void
{
    $stmt = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'password_hash'");
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existe) {
        $conn->exec("ALTER TABLE usuarios ADD COLUMN password_hash VARCHAR(255) NULL AFTER telefono");
    }
}

/**
 * Garantiza que exista la columna rol (para distinguir administradores).
 */
function asegurarColumnaRol(PDO $conn): void
{
    $stmt = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'rol'");
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existe) {
        $conn->exec("ALTER TABLE usuarios ADD COLUMN rol VARCHAR(20) NOT NULL DEFAULT 'admin' AFTER password_hash");
    }
}

switch ($accion) {
    case "contar_usuarios":
        $sql = "SELECT COUNT(*) AS total FROM usuarios";
        break;
    case "contar_libros":
        $sql = "SELECT COUNT(*) AS total FROM libros";
        break;
    case "contar_prestamos":
        $sql = "SELECT COUNT(*) AS total FROM prestamos";
        break;
    case "listar_usuarios":
        try {
            $sqlList = "SELECT id_usuario, nombre, correo, telefono FROM usuarios ORDER BY id_usuario DESC";
            $stmt = $conn->prepare($sqlList);
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(["status" => "success", "usuarios" => $usuarios]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        exit;
    case "registrar_usuario":
        $nombre = trim($_POST["nombre"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $password = trim($_POST["password"] ?? "");
        $claveAdmin = trim($_POST["clave_admin"] ?? "");

        if (!$nombre || !$correo || !$password) {
            echo json_encode(["status" => "error", "message" => "Nombre, correo y contraseña son obligatorios."]);
            exit;
        }

        if ($claveAdmin !== ADMIN_KEY) {
            echo json_encode(["status" => "error", "message" => "Clave de administrador incorrecta."]);
            exit;
        }

        try {
            asegurarColumnaPassword($conn);
            asegurarColumnaRol($conn);
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $rol = "admin";
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol) VALUES (:nombre, :correo, :telefono, :hash, :rol)");
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":correo", $correo);
            $stmt->bindParam(":telefono", $telefono);
            $stmt->bindParam(":hash", $hash);
            $stmt->bindParam(":rol", $rol);
            $stmt->execute();
            echo json_encode(["status" => "success", "message" => "Usuario registrado"]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        exit;
    case "crear_miembro":
        // Alta de usuarios sin contraseña (solo administrados por el admin)
        $nombre = trim($_POST["nombre"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");

        if (!$nombre || !$correo) {
            echo json_encode(["status" => "error", "message" => "Nombre y correo son obligatorios."]);
            exit;
        }

        try {
            asegurarColumnaPassword($conn);
            asegurarColumnaRol($conn);
            $rol = "usuario";
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol) VALUES (:nombre, :correo, :telefono, NULL, :rol)");
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":correo", $correo);
            $stmt->bindParam(":telefono", $telefono);
            $stmt->bindParam(":rol", $rol);
            $stmt->execute();
            echo json_encode(["status" => "success", "message" => "Usuario registrado"]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        exit;
    case "login":
        $correo = trim($_POST["correo"] ?? "");
        $password = trim($_POST["password"] ?? "");

        if (!$correo || !$password) {
            echo json_encode(["status" => "error", "message" => "Correo y contraseña son obligatorios."]);
            exit;
        }

        try {
            asegurarColumnaPassword($conn);
            asegurarColumnaRol($conn);
            $stmt = $conn->prepare("SELECT id_usuario, nombre, correo, password_hash, rol FROM usuarios WHERE correo = :correo LIMIT 1");
            $stmt->bindParam(":correo", $correo);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario || !password_verify($password, $usuario["password_hash"])) {
                echo json_encode(["status" => "error", "message" => "Credenciales inválidas."]);
                exit;
            }

            if (($usuario["rol"] ?? "") !== "admin") {
                echo json_encode(["status" => "error", "message" => "Acceso restringido a administradores."]);
                exit;
            }

            unset($usuario["password_hash"]);
            echo json_encode(["status" => "success", "user" => $usuario]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        exit;
    default:
        echo json_encode(["status" => "error", "message" => "Acción no válida"]);
        exit;
}

// Acciones de conteo (usa $sql)
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "total" => $data["total"]
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
