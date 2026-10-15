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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="<?= e($claseBody ?? "") ?>">

<nav class="nav" aria-label="Principal">
    <a href="index.php" class="logo">Green Loop</a>

    <button
        type="button"
        class="nav-toggle"
        id="nav-toggle"
        aria-expanded="false"
        aria-controls="nav-panel"
        aria-label="Abrir menú"
    >
        <span aria-hidden="true"></span>
    </button>

    <div class="nav-overlay" id="nav-overlay"></div>

    <div class="nav-panel" id="nav-panel">
        <div class="nav-links">
            <a href="catalogo.php" class="<?= claseActiva("catalogo.php") ?>">Explorar</a>

            <?php if (usuarioLogueado()): ?>
                <a href="publicar.php" class="<?= claseActiva("publicar.php") ?>">Publicar</a>
                <a href="mis_publicaciones.php" class="<?= claseActiva("mis_publicaciones.php") ?>">Mis publicaciones</a>
                <a href="mis_solicitudes.php" class="<?= claseActiva("mis_solicitudes.php") ?>">Mis solicitudes</a>
                <a href="perfil.php" class="<?= claseActiva("perfil.php") ?>">Mi perfil</a>
            <?php endif; ?>
        </div>

        <div class="nav-cuenta">
            <?php if (usuarioLogueado()): ?>
                <div class="nav-saludo">
                    <span class="nav-saludo-etiqueta">Bienvenido</span>
                    <span class="nav-saludo-nombre"><?= e($_SESSION["nombre"]) ?></span>
                </div>
                <form action="logout.php" method="post">
                    <button type="submit" class="link">Salir</button>
                </form>
            <?php else: ?>
                <a href="login.php" class="<?= claseActiva("login.php") ?>">Iniciar sesión</a>
                <a href="registro.php" class="<?= claseActiva("registro.php") ?>">Crear cuenta</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main>

<?php if ($mensaje !== ""): ?>
    <p class="mensaje"><?= e($mensaje) ?></p>
<?php endif; ?>
