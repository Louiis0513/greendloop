<?php
session_start();

require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();

$idUsuario = (int) $_SESSION["usuario"];

$stmt = $mysqli->prepare(
    "SELECT s.*,
            p.nombre AS objeto,
            solicitante.telefono AS telefono_solicitante,
            propietario.telefono AS telefono_propietario
     FROM solicitudes s
     JOIN productos p
        ON s.id_producto = p.id_producto
     JOIN usuarios solicitante
        ON s.id_solicitante = solicitante.id_usuario
     JOIN usuarios propietario
        ON s.id_propietario = propietario.id_usuario
     WHERE s.id_solicitante = ? OR s.id_propietario = ?
     ORDER BY s.fecha_solicitud DESC"
);

$stmt->bind_param("ii", $idUsuario, $idUsuario);
$stmt->execute();

$solicitudes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require __DIR__ . "/includes/header.php";
?>

<div class="pagina-titulo">
    <h2>Solicitudes y negociaciones</h2>
    <p>Gestiona pedidos, aceptaciones y detalles de encuentro.</p>
</div>

<?php if (count($solicitudes) === 0): ?>
    <p class="vacio">
        Aún no tienes solicitudes.
        <a href="catalogo.php">Explora el catálogo</a>
        para pedir un objeto.
    </p>
<?php else: ?>
    <div class="lista-solicitudes">
        <?php foreach ($solicitudes as $s): ?>
            <?php
            $estadoActual = strtolower(trim($s["estado"] ?? ""));
            $nombresEstados = [
                "pendiente" => "Pendiente",
                "aceptada" => "Aceptada",
                "rechazada" => "Rechazada",
                "cancelada" => "Cancelada"
            ];
            $estadoTexto = $nombresEstados[$estadoActual] ?? "Sin estado";
            ?>
            <article class="solicitud">
                <div class="solicitud-cabecera">
                    <h3><?= e($s["objeto"]) ?></h3>
                    <span class="badge badge-estado es-<?= e($estadoActual) ?>">
                        <?= e($estadoTexto) ?>
                    </span>
                </div>

                <p><?= e($s["mensaje"]) ?></p>

                <?php if ($estadoActual === "aceptada"): ?>
                    <?php if ((int) $s["id_solicitante"] === $idUsuario): ?>
                        <p class="contacto-box">
                            Teléfono del propietario:
                            <strong><?= e($s["telefono_propietario"] ?: "No registrado") ?></strong>
                        </p>
                    <?php else: ?>
                        <p class="contacto-box">
                            Teléfono de quien solicitó:
                            <strong><?= e($s["telefono_solicitante"] ?: "No registrado") ?></strong>
                        </p>
                    <?php endif; ?>

                    <form
                        action="gestionar_solicitud.php"
                        method="post"
                        class="form-inline"
                        onsubmit="return confirm('¿Seguro que deseas cancelar esta negociación?');"
                    >
                        <input type="hidden" name="id_solicitud" value="<?= (int) $s["id_solicitud"] ?>">
                        <button type="submit" name="accion" value="cancelada" class="peligro">
                            Cancelar negociación
                        </button>
                    </form>
                <?php endif; ?>

                <?php if (
                    (int) $s["id_propietario"] === $idUsuario
                    && $estadoActual === "pendiente"
                ): ?>
                    <p>¿Qué deseas hacer con esta solicitud?</p>
                    <div class="acciones-fila">
                        <form action="gestionar_solicitud.php" method="post" class="form-inline">
                            <input type="hidden" name="id_solicitud" value="<?= (int) $s["id_solicitud"] ?>">
                            <button type="submit" name="accion" value="aceptada">Aceptar</button>
                        </form>
                        <form action="gestionar_solicitud.php" method="post" class="form-inline">
                            <input type="hidden" name="id_solicitud" value="<?= (int) $s["id_solicitud"] ?>">
                            <button type="submit" name="accion" value="rechazada" class="secundario">Rechazar</button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($estadoActual === "pendiente" || $estadoActual === "aceptada"): ?>
                    <form action="negociar.php" method="post">
                        <h4>Detalles de negociación</h4>
                        <input type="hidden" name="id_solicitud" value="<?= (int) $s["id_solicitud"] ?>">

                        <label for="detalles-<?= (int) $s["id_solicitud"] ?>">Detalles</label>
                        <textarea
                            id="detalles-<?= (int) $s["id_solicitud"] ?>"
                            name="detalles"
                            placeholder="Notas de la negociación"
                        ><?= e($s["detalles_negociacion"] ?? "") ?></textarea>

                        <div class="solicitud-grid">
                            <div>
                                <label for="lugar-<?= (int) $s["id_solicitud"] ?>">Lugar de encuentro</label>
                                <input
                                    id="lugar-<?= (int) $s["id_solicitud"] ?>"
                                    name="lugar"
                                    placeholder="Lugar de encuentro"
                                    value="<?= e($s["lugar_encuentro"] ?? "") ?>"
                                >
                            </div>
                            <div>
                                <label for="fecha-<?= (int) $s["id_solicitud"] ?>">Fecha</label>
                                <input
                                    type="date"
                                    id="fecha-<?= (int) $s["id_solicitud"] ?>"
                                    name="fecha"
                                    value="<?= e($s["fecha_encuentro"] ?? "") ?>"
                                >
                            </div>
                            <div>
                                <label for="hora-<?= (int) $s["id_solicitud"] ?>">Hora</label>
                                <input
                                    type="time"
                                    id="hora-<?= (int) $s["id_solicitud"] ?>"
                                    name="hora"
                                    value="<?= e($s["hora_encuentro"] ?? "") ?>"
                                >
                            </div>
                        </div>

                        <button type="submit">Guardar negociación</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . "/includes/footer.php"; ?>
