<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();

$idUsuario = $_SESSION["usuario"];

$stmt = $mysqli->prepare(
    "SELECT s.*, p.nombre AS objeto
     FROM solicitudes s
     JOIN productos p ON s.id_producto = p.id_producto
     WHERE s.id_solicitante = ? OR s.id_propietario = ?
     ORDER BY s.fecha_solicitud DESC"
);
$stmt->bind_param("ii", $idUsuario, $idUsuario);
$stmt->execute();
$solicitudes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require __DIR__ . "/includes/header.php";
?>

    <h2>Solicitudes y negociaciones</h2>

    <?php if (count($solicitudes) === 0): ?>
        <p>Aún no tienes solicitudes. <a href="catalogo.php">Explora el catálogo</a> para pedir un objeto.</p>
    <?php endif; ?>

    <?php foreach ($solicitudes as $s): ?>
    <article class="producto">
        <h3><?= e($s["objeto"]) ?></h3>
        <p><?= e($s["mensaje"]) ?></p>
        <p>Estado: <?= e($s["estado"]) ?></p>

        <form action="negociar.php" method="post">
            <input type="hidden" name="id_solicitud" value="<?= $s["id_solicitud"] ?>">
            <textarea name="detalles" placeholder="Detalles de negociación"><?= e($s["detalles_negociacion"]) ?></textarea>
            <input name="lugar" placeholder="Lugar de encuentro" value="<?= e($s["lugar_encuentro"]) ?>">
            <label>Fecha del encuentro</label>
            <input type="date" name="fecha" value="<?= e($s["fecha_encuentro"]) ?>">
            <label>Hora del encuentro</label>
            <input type="time" name="hora" value="<?= e($s["hora_encuentro"]) ?>">
            <button>Guardar negociación</button>
        </form>
    </article>
    <?php endforeach; ?>

<?php require __DIR__ . "/includes/footer.php"; ?>
