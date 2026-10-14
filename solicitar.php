<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: catalogo.php");
    exit;
}

$id = (int)($_POST["id_producto"] ?? 0);
$idUsuario = $_SESSION["usuario"];

$stmt = $mysqli->prepare(
    "SELECT * FROM productos WHERE id_producto = ? AND disponible = 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();

if (!$producto ||
    $producto["id_usuario"] == $idUsuario) {
    guardarMensaje("No puedes solicitar este objeto.");
} else {
    $tipo = $producto["tipo_publicacion"];
    $texto = trim($_POST["mensaje"] ?? "");
    $idPropietario = $producto["id_usuario"];

    $stmt = $mysqli->prepare(
        "INSERT INTO solicitudes
        (id_producto, id_solicitante, id_propietario,
         tipo_solicitud, mensaje)
        VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iiiss", $id, $idUsuario, $idPropietario, $tipo, $texto);
    $stmt->execute();

    guardarMensaje("Solicitud enviada correctamente.");
}

header("Location: catalogo.php");
exit;
