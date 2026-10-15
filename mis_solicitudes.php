
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

<section>
    <h2>Solicitudes y negociaciones</h2>

    <?php if (count($solicitudes) === 0): ?>
        <p>
            Aún no tienes solicitudes.
            <a href="catalogo.php">Explora el catálogo</a>
            para pedir un objeto.
        </p>
    <?php endif; ?>

    <?php foreach ($solicitudes as $s): ?>
        <article class="producto">
            <h3><?= e($s["objeto"]) ?></h3>

            <p><?= e($s["mensaje"]) ?></p>
            <p>
    Estado:
    <strong>
        <?php
        $estadoActual = strtolower(trim($s["estado"] ?? ""));

        $nombresEstados = [
            "pendiente" => "Pendiente",
            "aceptada" => "Aceptada",
            "rechazada" => "Rechazada",
            "cancelada" => "Cancelada"
        ];

        echo e($nombresEstados[$estadoActual] ?? "Sin estado");
        ?>
    </strong>
</p>
            
<?php if ($s["estado"] === "aceptada"): ?>

    <form action="gestionar_solicitud.php" method="post"
          onsubmit="return confirm('¿Seguro que deseas cancelar esta negociación?');">

        <input type="hidden" name="id_solicitud"
               value="<?= (int) $s["id_solicitud"] ?>">

        <button type="submit" name="accion" value="cancelada">
            ❌ Cancelar negociación
        </button>
    </form>

<?php endif; ?>

<?php if ($s["estado"] === "aceptada"): ?>

    <?php if ((int) $s["id_solicitante"] === $idUsuario): ?>
        <p>
            📞 Teléfono del propietario:
            <strong>
                <?= e($s["telefono_propietario"] ?: "No registrado") ?>
            </strong>
        </p>
    <?php else: ?>
        <p>
            📞 Teléfono de quien solicitó:
            <strong>
                <?= e($s["telefono_solicitante"] ?: "No registrado") ?>
            </strong>
        </p>
    <?php endif; ?>

<?php endif; ?>

            <?php if (
                (int) $s["id_propietario"] === $idUsuario
                && $s["estado"] === "pendiente"
            ): ?>
                <p>¿Qué deseas hacer con esta solicitud?</p>

                <form action="gestionar_solicitud.php" method="post">
                    <input type="hidden" name="id_solicitud"
                           value="<?= (int) $s["id_solicitud"] ?>">

                    <button type="submit" name="accion" value="aceptada">
                        Aceptar solicitud
                    </button>

                    <button type="submit" name="accion" value="rechazada">
                        Rechazar solicitud
                    </button>
                </form>
            <?php endif; ?>

            <?php if (
                $s["estado"] === "pendiente"
                || $s["estado"] === "aceptada"
            ): ?>
                <h4>Detalles de negociación</h4>

                <form action="negociar.php" method="post">
                    <input type="hidden" name="id_solicitud"
                           value="<?= (int) $s["id_solicitud"] ?>">

                    <label>Detalles</label>
                    <textarea name="detalles"
                              placeholder="Detalles de negociación"><?= e($s["detalles_negociacion"] ?? "") ?></textarea>

                    <label>Lugar de encuentro</label>
                    <input name="lugar"
                           placeholder="Lugar de encuentro"
                           value="<?= e($s["lugar_encuentro"] ?? "") ?>">

                    <label>Fecha del encuentro</label>
                    <input type="date" name="fecha"
                           value="<?= e($s["fecha_encuentro"] ?? "") ?>">

                    <label>Hora del encuentro</label>
                    <input type="time" name="hora"
                           value="<?= e($s["hora_encuentro"] ?? "") ?>">

                    <button type="submit">Guardar negociación</button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>

<?php require __DIR__ . "/includes/footer.php"; ?>
