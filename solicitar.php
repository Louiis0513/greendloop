<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: catalogo.php");
    exit;
}

$id = (int) ($_POST["id_producto"] ?? 0);
$idUsuario = (int) $_SESSION["usuario"];
$texto = trim($_POST["mensaje"] ?? "");

$volverTrueque = "solicitar_producto.php?id_producto=" . $id;

if (strlen($texto) < 5) {
    guardarMensaje("Escribe un mensaje de al menos 5 caracteres.");
    header("Location: catalogo.php");
    exit;
}

$stmt = $mysqli->prepare(
    "SELECT * FROM productos WHERE id_producto = ? AND disponible = 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto || (int) $producto["id_usuario"] === $idUsuario) {
    guardarMensaje("No puedes solicitar este objeto.");
    header("Location: catalogo.php");
    exit;
}

$esTrueque = esTrueque($producto["tipo_publicacion"]);
$tipo = $esTrueque ? "intercambio" : "regalo";
$idPropietario = (int) $producto["id_usuario"];

// Evitar solicitudes pendientes duplicadas del mismo usuario sobre el mismo objeto.
$stmt = $mysqli->prepare(
    "SELECT COUNT(*) AS total FROM solicitudes
     WHERE id_producto = ? AND id_solicitante = ? AND estado = 'pendiente'"
);
$stmt->bind_param("ii", $id, $idUsuario);
$stmt->execute();
$duplicada = (int) $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

if ($duplicada > 0) {
    guardarMensaje("Ya tienes una solicitud pendiente para este objeto.");
    header("Location: mis_solicitudes.php?vista=enviadas");
    exit;
}

/* ---------- REGALO: solicitud simple, sin ofrecidos ---------- */
if (!$esTrueque) {
    $stmt = $mysqli->prepare(
        "INSERT INTO solicitudes
        (id_producto, id_solicitante, id_propietario, tipo_solicitud, mensaje)
        VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iiiss", $id, $idUsuario, $idPropietario, $tipo, $texto);
    $stmt->execute();
    $stmt->close();

    cacheParcheStats($mysqli);

    guardarMensaje("Solicitud de regalo enviada. Te avisaremos aquí cuando la respondan.");
    header("Location: mis_solicitudes.php?vista=enviadas");
    exit;
}

/* ---------- TRUEQUE: 1 a 5 productos propios ofrecidos ---------- */
$ofrecidosEntrada = $_POST["productos_ofrecidos"] ?? [];
if (!is_array($ofrecidosEntrada)) {
    $ofrecidosEntrada = [];
}

$ofrecidos = [];
foreach ($ofrecidosEntrada as $valor) {
    $idOfrecido = (int) $valor;
    if ($idOfrecido > 0) {
        $ofrecidos[$idOfrecido] = $idOfrecido; // sin duplicados
    }
}
$ofrecidos = array_values($ofrecidos);

if (count($ofrecidos) < 1 || count($ofrecidos) > 5) {
    guardarMensaje("Elige entre 1 y 5 objetos tuyos para ofrecer en el trueque.");
    header("Location: " . $volverTrueque);
    exit;
}

$mysqli->begin_transaction();

try {
    // Revalidar en servidor: son del solicitante, están disponibles,
    // son de tipo trueque y no incluyen el objeto pedido.
    $marcas = implode(",", array_fill(0, count($ofrecidos), "?"));
    $stmt = $mysqli->prepare(
        "SELECT id_producto FROM productos
         WHERE id_producto IN ($marcas)
           AND id_usuario = ?
           AND disponible = 1
           AND LOWER(tipo_publicacion) IN ('intercambio', 'trueque')
           AND id_producto <> ?
         FOR UPDATE"
    );
    $tipos = str_repeat("i", count($ofrecidos)) . "ii";
    $stmt->bind_param($tipos, ...[...$ofrecidos, $idUsuario, $id]);
    $stmt->execute();
    $validos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (count($validos) !== count($ofrecidos)) {
        throw new Exception("Alguno de los objetos ofrecidos ya no está disponible o no es tuyo.");
    }

    $stmt = $mysqli->prepare(
        "INSERT INTO solicitudes
        (id_producto, id_solicitante, id_propietario, tipo_solicitud, mensaje)
        VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iiiss", $id, $idUsuario, $idPropietario, $tipo, $texto);
    $stmt->execute();
    $idSolicitud = (int) $mysqli->insert_id;
    $stmt->close();

    $stmt = $mysqli->prepare(
        "INSERT INTO solicitud_ofrecimientos (id_solicitud, id_producto_ofrecido, elegido)
         VALUES (?, ?, 0)"
    );
    foreach ($ofrecidos as $idOfrecido) {
        $stmt->bind_param("ii", $idSolicitud, $idOfrecido);
        $stmt->execute();
    }
    $stmt->close();

    $mysqli->commit();
    cacheParcheStats($mysqli);
    guardarMensaje("Propuesta de trueque enviada. La otra persona elegirá uno de tus objetos.");
    header("Location: mis_solicitudes.php?vista=enviadas");
    exit;
} catch (Throwable $e) {
    $mysqli->rollback();
    guardarMensaje("No se pudo enviar el trueque: " . $e->getMessage());
    header("Location: " . $volverTrueque);
    exit;
}
