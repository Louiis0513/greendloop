<?php
session_start();

require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();

$idUsuario = $_SESSION["usuario"];

$stmt = $mysqli->prepare(
    "SELECT id_producto, nombre, descripcion, categoria,
            tipo_publicacion, disponible, fecha_publicacion, imagen
     FROM productos
     WHERE id_usuario = ?
     ORDER BY fecha_publicacion DESC"
);

$stmt->bind_param("i", $idUsuario);
$stmt->execute();

$productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require __DIR__ . "/includes/header.php";
?>

<div class="pagina-titulo">
    <h2>Mis publicaciones</h2>
    <p>Administra los objetos que has publicado en Green Loop.</p>
</div>

<p class="acciones-top">
    <a href="publicar.php" class="boton">Publicar un objeto</a>
</p>

<?php if (count($productos) === 0): ?>
    <p class="vacio">Todavía no has publicado objetos. Cuando publiques uno, aparecerá aquí.</p>
<?php else: ?>
    <div class="productos">
        <?php foreach ($productos as $p): ?>
            <article class="producto">
                <div class="producto-media">
                    <?php if (!empty($p["imagen"])): ?>
                        <img
                            src="<?= e($p["imagen"]) ?>"
                            alt="<?= e($p["nombre"]) ?>"
                        >
                    <?php endif; ?>
                </div>
                <div class="producto-cuerpo">
                    <div class="badges">
                        <span class="badge"><?= e($p["categoria"]) ?></span>
                        <span class="badge badge-tipo"><?= e($p["tipo_publicacion"]) ?></span>
                        <span class="badge badge-estado <?= $p["disponible"] ? "es-aceptada" : "es-cancelada" ?>">
                            <?= $p["disponible"] ? "Disponible" : "No disponible" ?>
                        </span>
                    </div>

                    <h3><?= e($p["nombre"]) ?></h3>
                    <p class="producto-desc"><?= e($p["descripcion"]) ?></p>

                    <div class="acciones-fila">
                        <a href="editar_publicacion.php?id=<?= (int) $p["id_producto"] ?>" class="boton secundario">
                            Editar
                        </a>

                        <form action="cambiar_disponibilidad.php" method="post" class="form-inline">
                            <input type="hidden" name="id_producto" value="<?= (int) $p["id_producto"] ?>">
                            <input type="hidden" name="disponible" value="<?= $p["disponible"] ? 0 : 1 ?>">
                            <button type="submit" class="secundario">
                                <?= $p["disponible"] ? "Marcar no disponible" : "Volver a publicar" ?>
                            </button>
                        </form>

                        <form
                            action="eliminar_publicacion.php"
                            method="post"
                            class="form-inline"
                            onsubmit="return confirm('¿Seguro que quieres eliminar esta publicación?');"
                        >
                            <input type="hidden" name="id_producto" value="<?= (int) $p["id_producto"] ?>">
                            <button type="submit" class="peligro">Eliminar</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . "/includes/footer.php"; ?>
