
<?php
session_start();

require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();

// Solo permite recibir solicitudes mediante POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: mis_publicaciones.php");
    exit;
}

$idUsuario = (int) $_SESSION["usuario"];
$idProducto = (int) ($_POST["id_producto"] ?? 0);
$disponible = $_POST["disponible"] ?? "";

// Validar los datos recibidos.
if (
    $idProducto <= 0 ||
    !in_array($disponible, ["0", "1"], true)
) {
    guardarMensaje("Datos no válidos.");
    header("Location: mis_publicaciones.php");
    exit;
}

// Actualizar únicamente los objetos del usuario que inició sesión.
$stmt = $mysqli->prepare(
    "UPDATE productos
     SET disponible = ?
     WHERE id_producto = ? AND id_usuario = ?"
);

$stmt->bind_param(
    "iii",
    $disponible,
    $idProducto,
    $idUsuario
);

$stmt->execute();

guardarMensaje("Se ha actualizado la disponibilidad.");
header("Location: mis_publicaciones.php");
exit;
