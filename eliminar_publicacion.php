
<?php
session_start();

require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();
enviarHeadersCache('privado');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: mis_publicaciones.php");
    exit;
}

$idUsuario = (int) $_SESSION["usuario"];
$idProducto = (int) ($_POST["id_producto"] ?? 0);

if ($idProducto <= 0) {
    guardarMensaje("Publicación no válida.");
    header("Location: mis_publicaciones.php");
    exit;
}

// Comprobar que la publicación sea del usuario.
$stmt = $mysqli->prepare(
    "SELECT id_producto
     FROM productos
     WHERE id_producto = ? AND id_usuario = ?"
);
$stmt->bind_param("ii", $idProducto, $idUsuario);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();

if (!$producto) {
    guardarMensaje("No se encontró tu publicación.");
    header("Location: mis_publicaciones.php");
    exit;
}

// Revisar si existen solicitudes asociadas.
$stmt = $mysqli->prepare(
    "SELECT id_solicitud
     FROM solicitudes
     WHERE id_producto = ?
     LIMIT 1"
);
$stmt->bind_param("i", $idProducto);
$stmt->execute();
$tieneSolicitudes = $stmt->get_result()->num_rows > 0;

if ($tieneSolicitudes) {
    guardarMensaje(
        "No puedes eliminar esta publicación porque tiene solicitudes asociadas. Puedes dejarla como no disponible."
    );
    header("Location: mis_publicaciones.php");
    exit;
}

// Eliminar únicamente la publicación del usuario.
$stmt = $mysqli->prepare(
    "DELETE FROM productos
     WHERE id_producto = ? AND id_usuario = ?"
);
$stmt->bind_param("ii", $idProducto, $idUsuario);
$stmt->execute();
$stmt->close();

cacheParcheProductoEliminado($mysqli, $idProducto);

guardarMensaje("Publicación eliminada correctamente.");
header("Location: mis_publicaciones.php");
exit;
