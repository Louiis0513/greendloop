
<?php
session_start();

require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();
enviarHeadersCache('privado');

$idUsuario = (int) $_SESSION["usuario"];
$idProducto = (int) ($_POST["id_producto"] ?? $_GET["id"] ?? 0);

if ($idProducto <= 0) {
    guardarMensaje("Publicación no válida.");
    header("Location: mis_publicaciones.php");
    exit;
}

$stmt = $mysqli->prepare(
    "SELECT nombre, descripcion, categoria, tipo_publicacion
     FROM productos
     WHERE id_producto = ? AND id_usuario = ?"
);
$stmt->bind_param("ii", $idProducto, $idUsuario);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();

if (!$producto) {
    guardarMensaje("No se encontró tu publicación.");
    header("Location: mis_publicaciones.php");
    exit;
}

$error = "";
$categorias = ["Ropa", "Libros", "Tecnología", "Hogar", "Juguetes", "Otros"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $categoria = $_POST["categoria"] ?? "";
    $tipo = $_POST["tipo_publicacion"] ?? "";

    if (strlen($nombre) < 3 || strlen($nombre) > 100) {
        $error = "El nombre debe tener entre 3 y 100 caracteres.";
    } elseif (strlen($descripcion) < 10) {
        $error = "La descripción debe tener al menos 10 caracteres.";
    } elseif (!in_array($categoria, $categorias, true)) {
        $error = "Selecciona una categoría válida.";
    } elseif (!in_array($tipo, ["regalo", "intercambio"], true)) {
        $error = "Selecciona un tipo válido.";
    } else {
        $stmt = $mysqli->prepare(
            "UPDATE productos
             SET nombre = ?, descripcion = ?, categoria = ?,
                 tipo_publicacion = ?
             WHERE id_producto = ? AND id_usuario = ?"
        );
        $stmt->bind_param(
            "ssssii",
            $nombre,
            $descripcion,
            $categoria,
            $tipo,
            $idProducto,
            $idUsuario
        );
        $stmt->execute();
        $stmt->close();

        cacheParcheProducto($mysqli, $idProducto);

        guardarMensaje("Publicación actualizada correctamente.");
        header("Location: mis_publicaciones.php");
        exit;
    }

    $producto["nombre"] = $nombre;
    $producto["descripcion"] = $descripcion;
    $producto["categoria"] = $categoria;
    $producto["tipo_publicacion"] = $tipo;
}

require __DIR__ . "/includes/header.php";
?>

<section class="auth-wrap form-ancho">
    <div class="pagina-titulo">
        <h2>Editar publicación</h2>
        <p>Actualiza los datos de tu objeto.</p>
    </div>

    <?php if ($error !== ""): ?>
        <p class="mensaje mensaje-error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="id_producto"
               value="<?= $idProducto ?>">

        <label>Nombre del objeto</label>
        <input type="text" name="nombre" maxlength="100"
               value="<?= e($producto["nombre"]) ?>" required>

        <label>Descripción</label>
        <textarea name="descripcion" required><?= e($producto["descripcion"]) ?></textarea>

        <label>Categoría</label>
        <select name="categoria" required>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= e($cat) ?>"
                    <?= $producto["categoria"] === $cat ? "selected" : "" ?>>
                    <?= e($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <fieldset class="tipo-selector">
            <legend>¿Cómo lo compartes?</legend>
            <div class="tipo-opciones">
                <label class="tipo-opcion es-regalo">
                    <input
                        type="radio"
                        name="tipo_publicacion"
                        value="regalo"
                        <?= $producto["tipo_publicacion"] === "regalo" ? "checked" : "" ?>
                    >
                    <span class="tipo-opcion-titulo">Regalo</span>
                    <span class="tipo-opcion-texto">Lo entregas sin pedir nada a cambio.</span>
                </label>
                <label class="tipo-opcion es-trueque">
                    <input
                        type="radio"
                        name="tipo_publicacion"
                        value="intercambio"
                        <?= $producto["tipo_publicacion"] === "intercambio" ? "checked" : "" ?>
                    >
                    <span class="tipo-opcion-titulo">Trueque</span>
                    <span class="tipo-opcion-texto">Quien lo pida te ofrecerá objetos suyos y tú eliges uno.</span>
                </label>
            </div>
        </fieldset>

        <button type="submit">Guardar cambios</button>
    </form>

    <p><a href="mis_publicaciones.php">Volver a mis publicaciones</a></p>
</section>

<?php require __DIR__ . "/includes/footer.php"; ?>
