
<?php
session_start();

require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: mis_solicitudes.php");
    exit;
}

$idUsuario = (int) $_SESSION["usuario"];
$idSolicitud = (int) ($_POST["id_solicitud"] ?? 0);
$accion = $_POST["accion"] ?? "";

if ($idSolicitud <= 0) {
    guardarMensaje("Solicitud no válida.");
    header("Location: mis_solicitudes.php");
    exit;
}

/* CANCELAR NEGOCIACIÓN */
if ($accion === "cancelada") {
    $mysqli->begin_transaction();

    try {
        $stmt = $mysqli->prepare(
            "SELECT id_producto, id_solicitante,
                    id_propietario, estado
             FROM solicitudes
             WHERE id_solicitud = ?
             FOR UPDATE"
        );

        $stmt->bind_param("i", $idSolicitud);
        $stmt->execute();
        $solicitud = $stmt->get_result()->fetch_assoc();

        if (
            !$solicitud ||
            (
                (int) $solicitud["id_solicitante"] !== $idUsuario &&
                (int) $solicitud["id_propietario"] !== $idUsuario
            ) ||
            $solicitud["estado"] !== "aceptada"
        ) {
            throw new Exception(
                "No puedes cancelar esta negociación."
            );
        }

        $idProducto = (int) $solicitud["id_producto"];

        $stmt = $mysqli->prepare(
            "UPDATE solicitudes
             SET estado = 'cancelada'
             WHERE id_solicitud = ?
               AND estado = 'aceptada'"
        );

        $stmt->bind_param("i", $idSolicitud);
        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            throw new Exception(
                "No se pudo cancelar la negociación."
            );
        }

        $stmt = $mysqli->prepare(
            "SELECT COUNT(*) AS total
             FROM solicitudes
             WHERE id_producto = ?
               AND estado = 'aceptada'"
        );

        $stmt->bind_param("i", $idProducto);
        $stmt->execute();
        $activas = $stmt->get_result()->fetch_assoc();

        if ((int) $activas["total"] === 0) {
            $stmt = $mysqli->prepare(
                "UPDATE productos
                 SET disponible = 1
                 WHERE id_producto = ?"
            );

            $stmt->bind_param("i", $idProducto);
            $stmt->execute();
        }

        $mysqli->commit();

        guardarMensaje(
            "Negociación cancelada. Se revisó la disponibilidad del objeto."
        );
    } catch (Throwable $e) {
        $mysqli->rollback();

        guardarMensaje(
            "No se pudo cancelar: " . $e->getMessage()
        );
    }

    header("Location: mis_solicitudes.php");
    exit;
}

/* ACEPTAR O RECHAZAR SOLICITUD */
if (!in_array($accion, ["aceptada", "rechazada"], true)) {
    guardarMensaje("Acción no válida.");
    header("Location: mis_solicitudes.php");
    exit;
}

$stmt = $mysqli->prepare(
    "SELECT id_producto
     FROM solicitudes
     WHERE id_solicitud = ?
       AND id_propietario = ?
       AND estado = 'pendiente'"
);

$stmt->bind_param("ii", $idSolicitud, $idUsuario);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();

if (!$solicitud) {
    guardarMensaje(
        "La solicitud no existe, ya fue gestionada o no te pertenece."
    );
    header("Location: mis_solicitudes.php");
    exit;
}

$idProducto = (int) $solicitud["id_producto"];

$stmt = $mysqli->prepare(
    "UPDATE solicitudes
     SET estado = ?
     WHERE id_solicitud = ?
       AND id_propietario = ?
       AND estado = 'pendiente'"
);

$stmt->bind_param("sii", $accion, $idSolicitud, $idUsuario);
$stmt->execute();

if ($stmt->affected_rows === 1) {
    if ($accion === "aceptada") {
        $stmtProducto = $mysqli->prepare(
            "UPDATE productos
             SET disponible = 0
             WHERE id_producto = ?"
        );

        $stmtProducto->bind_param("i", $idProducto);
        $stmtProducto->execute();

        guardarMensaje(
            "Solicitud aceptada. El objeto ya no está disponible para nuevas solicitudes."
        );
    } else {
        guardarMensaje("Has rechazado la solicitud.");
    }
} else {
    guardarMensaje("No se pudo actualizar la solicitud.");
}

header("Location: mis_solicitudes.php");
exit;
