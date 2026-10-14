<?php
session_start();
require_once __DIR__ . "/includes/funciones.php";

require __DIR__ . "/includes/header.php";
?>

    <section class="portada">
        <h1>Comparte, intercambia y regala</h1>
        <p>Green Loop conecta personas que quieren reutilizar objetos
        en lugar de tirarlos a la basura.</p>

        <div class="acciones">
            <a href="catalogo.php" class="boton">Explorar el catálogo</a>
            <?php if (usuarioLogueado()): ?>
                <a href="publicar.php" class="boton secundario">Publicar un objeto</a>
            <?php else: ?>
                <a href="registro.php" class="boton secundario">Crear cuenta</a>
            <?php endif; ?>
        </div>
    </section>

<?php require __DIR__ . "/includes/footer.php"; ?>
