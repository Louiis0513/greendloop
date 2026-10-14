<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

$resultado = $mysqli->query(
    "SELECT p.*, u.nombre AS propietario
     FROM productos p
     JOIN usuarios u ON p.id_usuario = u.id_usuario
     WHERE p.disponible = 1
     ORDER BY p.fecha_publicacion DESC"
);
$productos = $resultado->fetch_all(MYSQLI_ASSOC);

require __DIR__ . "/includes/header.php";
?>

    <h2>Catálogo de objetos</h2>

    <?php if (!usuarioLogueado()): ?>
        <p><a href="login.php">Inicia sesión</a> para solicitar objetos.</p>
    <?php endif; ?>

    <?php if (count($productos) === 0): ?>
        <p>Todavía no hay objetos publicados.</p>
    <?php endif; ?>

    <div class="productos">
    <?php foreach ($productos as $p): ?>
        <article class="producto">
            <h3><?= e($p["nombre"]) ?></h3>
            <p><?= e($p["descripcion"]) ?></p>
            <p>Categoría: <?= e($p["categoria"]) ?></p>
            <p>Tipo: <?= e($p["tipo_publicacion"]) ?></p>
            <p>Publicado por: <?= e($p["propietario"]) ?></p>

            <?php if (usuarioLogueado() &&
                $_SESSION["usuario"] != $p["id_usuario"]): ?>
                <form action="solicitar.php" method="post">
                    <input type="hidden" name="id_producto" value="<?= $p["id_producto"] ?>">
                    <textarea name="mensaje" placeholder="Escribe tu solicitud" required minlength="5"></textarea>
                    <button>Solicitar objeto</button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    </div>

<?php require __DIR__ . "/includes/footer.php"; ?>
