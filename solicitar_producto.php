<?php
session_start();

require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();
enviarHeadersCache('privado');

$idUsuario = (int) $_SESSION["usuario"];
$idProducto = (int) ($_GET["id_producto"] ?? 0);

if ($idProducto <= 0) {
    guardarMensaje("Objeto no válido.");
    header("Location: catalogo.php");
    exit;
}

$producto = obtenerProductoDisponible($mysqli, $idProducto);

if (!$producto) {
    guardarMensaje("Este objeto ya no está disponible.");
    header("Location: catalogo.php");
    exit;
}

if ((int) $producto["id_usuario"] === $idUsuario) {
    guardarMensaje("No puedes solicitar tu propio objeto.");
    header("Location: catalogo.php");
    exit;
}

// Los regalos se piden desde el modal del catálogo, sin ofrecer nada.
if (!esTrueque($producto["tipo_publicacion"])) {
    guardarMensaje("Este objeto es un regalo: pídelo desde el catálogo, sin ofrecer nada a cambio.");
    header("Location: catalogo.php");
    exit;
}

// Objetos propios que se pueden ofrecer: disponibles y publicados para trueque.
$stmt = $mysqli->prepare(
    "SELECT id_producto, nombre, descripcion, categoria, imagen
     FROM productos
     WHERE id_usuario = ?
       AND disponible = 1
       AND LOWER(tipo_publicacion) IN ('intercambio', 'trueque')
       AND id_producto <> ?
     ORDER BY fecha_publicacion DESC"
);
$stmt->bind_param("ii", $idUsuario, $idProducto);
$stmt->execute();
$misProductos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require __DIR__ . "/includes/header.php";
?>

<div class="pagina-titulo">
    <p class="volver"><a href="catalogo.php">← Volver al catálogo</a></p>
    <h2>Proponer un trueque</h2>
    <p>Elige qué objetos tuyos ofreces. <?= e($producto["propietario"]) ?> escogerá uno para hacer el cambio.</p>
</div>

<ol class="pasos-trueque" aria-label="Pasos del trueque">
    <li class="activo"><span>1</span> Lo que quieres</li>
    <li class="activo"><span>2</span> Lo que ofreces</li>
    <li><span>3</span> Tu mensaje</li>
</ol>

<div class="trueque-layout">
    <aside class="trueque-deseado" aria-label="Objeto que quieres">
        <p class="trueque-etiqueta">Quieres este objeto</p>
        <article class="producto">
            <div class="producto-media<?= empty($producto["imagen"]) ? " sin-foto" : "" ?>">
                <?php if (!empty($producto["imagen"])): ?>
                    <img src="<?= e($producto["imagen"]) ?>" alt="<?= e($producto["nombre"]) ?>">
                <?php endif; ?>
            </div>
            <div class="producto-cuerpo">
                <div class="badges">
                    <span class="badge"><?= e($producto["categoria"]) ?></span>
                    <span class="badge badge-tipo es-trueque">Trueque</span>
                </div>
                <h3><?= e($producto["nombre"]) ?></h3>
                <p class="producto-desc"><?= e($producto["descripcion"]) ?></p>
                <p class="producto-autor">Publicado por <?= e($producto["propietario"]) ?></p>
            </div>
        </article>
    </aside>

    <section class="trueque-panel" aria-label="Tus objetos para ofrecer">
        <?php if (count($misProductos) === 0): ?>
            <div class="vacio trueque-vacio">
                <h3>Todavía no tienes objetos para ofrecer</h3>
                <p>Para pedir un trueque necesitas al menos un objeto tuyo publicado como Trueque y disponible.</p>
                <a href="publicar.php" class="boton">Publicar un objeto para trueque</a>
                <a href="mis_publicaciones.php" class="boton secundario">Revisar mis publicaciones</a>
            </div>
        <?php else: ?>
            <form action="solicitar.php" method="post" class="form-trueque" data-form-trueque>
                <input type="hidden" name="id_producto" value="<?= (int) $producto["id_producto"] ?>">

                <fieldset class="ofrecidos-selector">
                    <legend>
                        Paso 2 · Lo que ofreces
                        <span class="ofrecidos-cuenta" data-ofrecidos-cuenta>Elige de 1 a 5 objetos</span>
                    </legend>

                    <div class="ofrecidos-grid">
                        <?php foreach ($misProductos as $mio): ?>
                            <label class="ofrecido-card">
                                <input
                                    type="checkbox"
                                    name="productos_ofrecidos[]"
                                    value="<?= (int) $mio["id_producto"] ?>"
                                    data-ofrecido-check
                                >
                                <span class="ofrecido-media<?= empty($mio["imagen"]) ? " sin-foto" : "" ?>">
                                    <?php if (!empty($mio["imagen"])): ?>
                                        <img src="<?= e($mio["imagen"]) ?>" alt="" loading="lazy">
                                    <?php endif; ?>
                                </span>
                                <span class="ofrecido-info">
                                    <strong><?= e($mio["nombre"]) ?></strong>
                                    <span class="badge"><?= e($mio["categoria"]) ?></span>
                                </span>
                                <span class="ofrecido-check" aria-hidden="true">Elegido</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="form-ayuda" data-ofrecidos-ayuda>Puedes ofrecer hasta 5. La otra persona elegirá solo uno.</p>
                </fieldset>

                <div class="trueque-mensaje">
                    <label for="mensaje">Paso 3 · Tu mensaje</label>
                    <textarea
                        id="mensaje"
                        name="mensaje"
                        required
                        minlength="5"
                        placeholder="Cuéntale en qué estado están tus objetos y cuándo podrían encontrarse"
                    ></textarea>
                </div>

                <div class="trueque-envio">
                    <p data-ofrecidos-resumen>Aún no elegiste ningún objeto.</p>
                    <button type="submit" data-ofrecidos-enviar disabled>Enviar propuesta de trueque</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
