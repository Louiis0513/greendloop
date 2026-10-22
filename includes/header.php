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
    <meta name="description" content="Green Loop: regala o intercambia objetos usados con tu comunidad. Sin dinero, sin desperdicio.">
    <meta name="theme-color" content="#0a0e0c">
    <link rel="icon" type="image/jpeg" href="img/Logo.jpeg">
    <title>Green Loop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css?v=<?= (int) @filemtime(__DIR__ . "/../css/estilos.css") ?>">
</head>
<body class="<?= e($claseBody ?? "") ?>">

<?php
$navPendientes = 0;
if (usuarioLogueado() && isset($mysqli) && $mysqli instanceof mysqli) {
    $stmtNav = $mysqli->prepare(
        "SELECT COUNT(*) AS total FROM solicitudes WHERE id_propietario = ? AND estado = 'pendiente'"
    );
    if ($stmtNav) {
        $idNav = (int) $_SESSION["usuario"];
        $stmtNav->bind_param("i", $idNav);
        $stmtNav->execute();
        $filaNav = $stmtNav->get_result()->fetch_assoc();
        $navPendientes = (int) ($filaNav["total"] ?? 0);
        $stmtNav->close();
    }
}
$navNombre = trim((string) ($_SESSION["nombre"] ?? "Cuenta"));
$navInicial = mb_strtoupper(mb_substr($navNombre !== "" ? $navNombre : "C", 0, 1, "UTF-8"), "UTF-8");
?>
<nav class="nav" aria-label="Principal">
    <a href="index.php" class="logo">
        <img src="img/Logo.jpeg" alt="" width="36" height="36">
        <span>Green Loop</span>
    </a>

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
                <a href="mis_publicaciones.php" class="<?= claseActiva("mis_publicaciones.php") ?>">Mis publicaciones</a>
                <a href="mis_solicitudes.php" class="<?= claseActiva("mis_solicitudes.php") ?>">Mis solicitudes<?php if ($navPendientes > 0): ?> <span class="nav-badge"><?= $navPendientes ?></span><?php endif; ?></a>
            <?php endif; ?>
        </div>

        <div class="nav-cuenta">
            <?php if (usuarioLogueado()): ?>
                <a href="publicar.php" class="nav-cta <?= claseActiva("publicar.php") ?>">+ Publicar</a>
                <div class="nav-perfil" id="nav-perfil">
                    <button
                        type="button"
                        class="nav-perfil-btn"
                        id="nav-perfil-btn"
                        aria-expanded="false"
                        aria-haspopup="true"
                        aria-controls="nav-perfil-menu"
                    >
                        <span class="nav-avatar" aria-hidden="true"><?= e($navInicial) ?></span>
                        <span class="nav-perfil-nombre"><?= e($navNombre) ?></span>
                        <span class="nav-chevron" aria-hidden="true">▾</span>
                    </button>
                    <div class="nav-perfil-menu" id="nav-perfil-menu" role="menu">
                        <a href="perfil.php" class="<?= claseActiva("perfil.php") ?>" role="menuitem">Mi perfil</a>
                        <a href="mis_publicaciones.php" role="menuitem">Mis publicaciones</a>
                        <a href="mis_solicitudes.php" role="menuitem">Mis solicitudes<?php if ($navPendientes > 0): ?> <span class="nav-badge"><?= $navPendientes ?></span><?php endif; ?></a>
                        <form action="logout.php" method="post" class="nav-salir">
                            <button type="submit" role="menuitem">Salir</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="<?= claseActiva("login.php") ?>">Iniciar sesión</a>
                <a href="registro.php" class="nav-cta <?= claseActiva("registro.php") ?>">Crear cuenta</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main>

<?php if ($mensaje !== ""): ?>
    <p class="mensaje <?= e($claseMensaje ?? "") ?>"><?= e($mensaje) ?></p>
<?php endif; ?>
