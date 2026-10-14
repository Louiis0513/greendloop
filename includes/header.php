<?php
$mensaje = $mensaje ?? "";
if ($mensaje === "") {
    $mensaje = obtenerMensaje();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Green Loop</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<nav class="nav">
    <a href="index.php" class="logo">Green Loop</a>

    <div class="nav-links">
        <a href="catalogo.php" class="<?= claseActiva("catalogo.php") ?>">Explorar objetos</a>

        <?php if (usuarioLogueado()): ?>
            <a href="mis_solicitudes.php" class="<?= claseActiva("mis_solicitudes.php") ?>">Mis solicitudes</a>
            <a href="publicar.php" class="btn <?= claseActiva("publicar.php") ?>">+ Publicar</a>
        <?php endif; ?>
    </div>

    <div class="nav-cuenta">
        <?php if (usuarioLogueado()): ?>
            <span>Hola, <?= e($_SESSION["nombre"]) ?></span>
            <form action="logout.php" method="post">
                <button type="submit" class="link">Salir</button>
            </form>
        <?php else: ?>
            <a href="login.php" class="<?= claseActiva("login.php") ?>">Iniciar sesión</a>
            <a href="registro.php" class="btn">Crear cuenta</a>
        <?php endif; ?>
    </div>
</nav>

<main>

<?php if ($mensaje !== ""): ?>
    <p class="mensaje"><?= e($mensaje) ?></p>
<?php endif; ?>
