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

<section>
    <h2>Mis publicaciones</h2>

    <p>Aquí puedes administrar los objetos que has publicado.</p>

    <p>
        <a href="publicar.php">Publicar un objeto</a>
    </p>

    <?php if (count($productos) === 0): ?>

        <p>Todavía no has publicado objetos.</p>
        <p>Cuando publiques uno, aparecerá aquí.</p>

    <?php else: ?>

        <div class="productos">

            <?php foreach ($productos as $p): ?>

                <article class="producto">
                    
<?php if (!empty($p["imagen"])): ?>
    <img
        src="<?= e($p["imagen"]) ?>"
        alt="<?= e($p["nombre"]) ?>"
        style="width: 100%; max-width: 300px; height: 200px; object-fit: cover; border-radius: 10px;"
    >
<?php endif; ?>

                    <h3><?= e($p["nombre"]) ?></h3>

                    <p><?= e($p["descripcion"]) ?></p>
                    <p>Categoría: <?= e($p["categoria"]) ?></p>
                    <p>Tipo: <?= e($p["tipo_publicacion"]) ?></p>

                    <p>
                        Estado:
                        <?= $p["disponible"] ? "Disponible" : "No disponible" ?>
                    </p>

                    <a href="editar_publicacion.php?id=<?= (int)$p["id_producto"] ?>">
                        <button type="button">Editar</button>
                    </a>

                    <form action="eliminar_publicacion.php" method="post"
                          onsubmit="return confirm('¿Seguro que quieres eliminar esta publicación?');">

                        <input type="hidden" name="id_producto"
                               value="<?= (int)$p["id_producto"] ?>">

                        <button type="submit">Eliminar</button>
                    </form>
                    
<form action="cambiar_disponibilidad.php" method="post">
    <input type="hidden" name="id_producto"
           value="<?= (int)$p["id_producto"] ?>">

    <input type="hidden" name="disponible"
           value="<?= $p["disponible"] ? 0 : 1 ?>">

    <button type="submit">
        <?= $p["disponible"]
            ? "Marcar como no disponible"
            : "Volver a publicar" ?>
    </button>
</form>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>
</section>

<?php require __DIR__ . "/includes/footer.php"; ?>