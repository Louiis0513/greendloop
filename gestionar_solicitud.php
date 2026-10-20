
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

        // En un trueque también se libera el objeto ofrecido que fue elegido.
        $idOfrecidoElegido = 0;
        $stmtOf = $mysqli->prepare(
            "SELECT id_producto_ofrecido FROM solicitud_ofrecimientos
             WHERE id_solicitud = ? AND elegido = 1"
        );
        $stmtOf->bind_param("i", $idSolicitud);
        $stmtOf->execute();
        $filaOf = $stmtOf->get_result()->fetch_assoc();
        if ($filaOf) {
            $idOfrecidoElegido = (int) $filaOf["id_producto_ofrecido"];
        }
        $stmtOf->close();

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

        $idsLiberar = [$idProducto];
        if ($idOfrecidoElegido > 0) {
            $idsLiberar[] = $idOfrecidoElegido;
        }

        foreach ($idsLiberar as $idLiberar) {
            $stmt = $mysqli->prepare(
                "SELECT COUNT(*) AS total
                 FROM solicitudes s
                 LEFT JOIN solicitud_ofrecimientos o
                    ON o.id_solicitud = s.id_solicitud AND o.elegido = 1
                 WHERE s.estado = 'aceptada'
                   AND (s.id_producto = ? OR o.id_producto_ofrecido = ?)"
            );

            $stmt->bind_param("ii", $idLiberar, $idLiberar);
            $stmt->execute();
            $activas = $stmt->get_result()->fetch_assoc();

            if ((int) $activas["total"] === 0) {
                $stmt = $mysqli->prepare(
                    "UPDATE productos
                     SET disponible = 1
                     WHERE id_producto = ?"
                );

                $stmt->bind_param("i", $idLiberar);
                $stmt->execute();
            }
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

/* ACEPTAR TRUEQUE ELIGIENDO 1 PRODUCTO OFRECIDO */
if ($accion === "aceptar_trueque") {
    $idOfrecido = (int) ($_POST["id_producto_ofrecido"] ?? 0);

    if ($idOfrecido <= 0) {
        guardarMensaje("Elige uno de los objetos ofrecidos para cerrar el trueque.");
        header("Location: mis_solicitudes.php?vista=recibidas");
        exit;
    }

    $mysqli->begin_transaction();

    try {
        $stmt = $mysqli->prepare(
            "SELECT s.id_producto, s.id_solicitante, p.tipo_publicacion
             FROM solicitudes s
             JOIN productos p ON p.id_producto = s.id_producto
             WHERE s.id_solicitud = ?
               AND s.id_propietario = ?
               AND s.estado = 'pendiente'
             FOR UPDATE"
        );
        $stmt->bind_param("ii", $idSolicitud, $idUsuario);
        $stmt->execute();
        $solicitud = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$solicitud || !esTrueque($solicitud["tipo_publicacion"])) {
            throw new Exception("La solicitud no existe, ya fue gestionada o no es un trueque.");
        }

        $idProducto = (int) $solicitud["id_producto"];
        $idSolicitante = (int) $solicitud["id_solicitante"];

        // El ofrecido elegido debe estar en la propuesta, ser del solicitante y seguir disponible.
        $stmt = $mysqli->prepare(
            "SELECT o.id_ofrecimiento
             FROM solicitud_ofrecimientos o
             JOIN productos po ON po.id_producto = o.id_producto_ofrecido
             WHERE o.id_solicitud = ?
               AND o.id_producto_ofrecido = ?
               AND po.id_usuario = ?
               AND po.disponible = 1
             FOR UPDATE"
        );
        $stmt->bind_param("iii", $idSolicitud, $idOfrecido, $idSolicitante);
        $stmt->execute();
        $ofrecimiento = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$ofrecimiento) {
            throw new Exception("Ese objeto ofrecido ya no está disponible.");
        }

        $stmt = $mysqli->prepare(
            "UPDATE solicitud_ofrecimientos SET elegido = 0 WHERE id_solicitud = ?"
        );
        $stmt->bind_param("i", $idSolicitud);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare(
            "UPDATE solicitud_ofrecimientos SET elegido = 1
             WHERE id_solicitud = ? AND id_producto_ofrecido = ?"
        );
        $stmt->bind_param("ii", $idSolicitud, $idOfrecido);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare(
            "UPDATE solicitudes SET estado = 'aceptada'
             WHERE id_solicitud = ? AND estado = 'pendiente'"
        );
        $stmt->bind_param("i", $idSolicitud);
        $stmt->execute();
        $stmt->close();

        // Ambos objetos salen del catálogo: el pedido y el ofrecido elegido.
        $stmt = $mysqli->prepare(
            "UPDATE productos SET disponible = 0 WHERE id_producto IN (?, ?)"
        );
        $stmt->bind_param("ii", $idProducto, $idOfrecido);
        $stmt->execute();
        $stmt->close();

        // Las demás solicitudes pendientes sobre cualquiera de los dos objetos se cierran.
        $stmt = $mysqli->prepare(
            "UPDATE solicitudes SET estado = 'rechazada'
             WHERE estado = 'pendiente'
               AND id_solicitud <> ?
               AND (id_producto = ? OR id_producto = ?)"
        );
        $stmt->bind_param("iii", $idSolicitud, $idProducto, $idOfrecido);
        $stmt->execute();
        $stmt->close();

        $mysqli->commit();
        guardarMensaje("Trueque aceptado. Ya pueden acordar el encuentro para hacer el cambio.");
    } catch (Throwable $e) {
        $mysqli->rollback();
        guardarMensaje("No se pudo aceptar el trueque: " . $e->getMessage());
    }

    header("Location: mis_solicitudes.php?vista=recibidas");
    exit;
}

/* ACEPTAR O RECHAZAR SOLICITUD */
if (!in_array($accion, ["aceptada", "rechazada"], true)) {
    guardarMensaje("Acción no válida.");
    header("Location: mis_solicitudes.php");
    exit;
}

$stmt = $mysqli->prepare(
    "SELECT s.id_producto, p.tipo_publicacion
     FROM solicitudes s
     JOIN productos p ON p.id_producto = s.id_producto
     WHERE s.id_solicitud = ?
       AND s.id_propietario = ?
       AND s.estado = 'pendiente'"
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

// Un trueque no se acepta sin elegir primero el objeto ofrecido.
if ($accion === "aceptada" && esTrueque($solicitud["tipo_publicacion"])) {
    guardarMensaje("Este es un trueque: elige primero uno de los objetos ofrecidos.");
    header("Location: mis_solicitudes.php?vista=recibidas");
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
