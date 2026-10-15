    <?php
    session_start();
    require_once __DIR__ . "/includes/conexion.php";
    require_once __DIR__ . "/includes/funciones.php";

    requiereLogin();

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header("Location: mis_solicitudes.php");
        exit;
    }

    $id = (int)($_POST["id_solicitud"] ?? 0);
    $idUsuario = $_SESSION["usuario"];
    $detalles = trim($_POST["detalles"] ?? "");
    $lugar = trim($_POST["lugar"] ?? "");
    $fecha = ($_POST["fecha"] ?? "") ?: null;
    $hora = ($_POST["hora"] ?? "") ?: null;

    $stmt = $mysqli->prepare(
        "UPDATE solicitudes
        SET detalles_negociacion = ?, lugar_encuentro = ?,
            fecha_encuentro = ?, hora_encuentro = ?
        WHERE id_solicitud = ?
        AND (id_solicitante = ? OR id_propietario = ?)"
    );
    $stmt->bind_param("ssssiii", $detalles, $lugar, $fecha, $hora, $id, $idUsuario, $idUsuario);
    $stmt->execute();

    guardarMensaje("Negociación actualizada.");
    header("Location: mis_solicitudes.php");
    exit;
