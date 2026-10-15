
<?php
session_start();

require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $categoria = $_POST["categoria"] ?? "";
    $tipo = $_POST["tipo_publicacion"] ?? "";

    $categorias = [
        "Ropa", "Libros", "Tecnología",
        "Hogar", "Juguetes", "Otros"
    ];

    $imagen = null;

    // Validar los datos del objeto.
    if (strlen($nombre) < 3 || strlen($nombre) > 150) {
        $error = "El nombre debe tener entre 3 y 150 caracteres.";
    } elseif (strlen($descripcion) < 10) {
        $error = "La descripción debe tener al menos 10 caracteres.";
    } elseif (!in_array($categoria, $categorias, true)) {
        $error = "Selecciona una categoría válida.";
    } elseif (!in_array($tipo, ["regalo", "intercambio"], true)) {
        $error = "Selecciona un tipo de publicación válido.";
    }

    // Procesar la fotografía si se seleccionó una.
    if ($error === "" && isset($_FILES["imagen"])
        && $_FILES["imagen"]["error"] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES["imagen"]["error"] !== UPLOAD_ERR_OK) {
            $error = "No se pudo subir la imagen.";
        } elseif ($_FILES["imagen"]["size"] > 3 * 1024 * 1024) {
            $error = "La imagen no puede superar los 3 MB.";
        } else {
            $temporal = $_FILES["imagen"]["tmp_name"];
            $informacion = @getimagesize($temporal);

            if ($informacion === false) {
                $error = "El archivo seleccionado no es una imagen válida.";
            } else {
                $tiposPermitidos = [
                    IMAGETYPE_JPEG => "jpg",
                    IMAGETYPE_PNG => "png",
                    IMAGETYPE_WEBP => "webp"
                ];

                $tipoImagen = $informacion[2];

                if (!isset($tiposPermitidos[$tipoImagen])) {
                    $error = "Solo se permiten imágenes JPG, PNG o WEBP.";
                } else {
                    $carpeta = __DIR__ . "/img_productos/";

                    if (!is_dir($carpeta)) {
                        $error = "No se encuentra la carpeta img_productos.";
                    } else {
                        $nombreArchivo = bin2hex(random_bytes(16))
                            . "." . $tiposPermitidos[$tipoImagen];

                        if (move_uploaded_file(
                            $temporal,
                            $carpeta . $nombreArchivo
                        )) {
                            $imagen = "img_productos/" . $nombreArchivo;
                        } else {
                            $error = "No se pudo guardar la fotografía.";
                        }
                    }
                }
            }
        }
    }

    // Guardar la publicación en MySQL.
    if ($error === "") {
        $idUsuario = (int) $_SESSION["usuario"];

        $stmt = $mysqli->prepare(
            "INSERT INTO productos
            (id_usuario, nombre, descripcion, categoria,
             tipo_publicacion, imagen, disponible)
            VALUES (?, ?, ?, ?, ?, ?, 1)"
        );

        $stmt->bind_param(
            "isssss",
            $idUsuario,
            $nombre,
            $descripcion,
            $categoria,
            $tipo,
            $imagen
        );

        $stmt->execute();

        guardarMensaje("¡Objeto publicado correctamente!");
        header("Location: mis_publicaciones.php");
        exit;
    }
}

require __DIR__ . "/includes/header.php";
?>

<section class="auth-wrap form-ancho">
    <div class="pagina-titulo">
        <h2>Publicar un objeto</h2>
        <p>Dale una segunda vida a algo que ya no utilizas.</p>
    </div>

    <?php if ($error !== ""): ?>
        <p class="mensaje mensaje-error"><?= e($error) ?></p>
    <?php endif; ?>

    <form action="publicar.php" method="post"
          enctype="multipart/form-data">

        <label for="nombre">Nombre del objeto</label>
        <input
            type="text"
            id="nombre"
            name="nombre"
            maxlength="150"
            value="<?= e($_POST["nombre"] ?? "") ?>"
            required
        >

        <label for="descripcion">Descripción</label>
        <textarea
            id="descripcion"
            name="descripcion"
            required
            minlength="10"
        ><?= e($_POST["descripcion"] ?? "") ?></textarea>

        <label for="categoria">Categoría</label>
        <select id="categoria" name="categoria" required>
            <option value="">Selecciona una categoría</option>
            <?php foreach (["Ropa", "Libros", "Tecnología", "Hogar", "Juguetes", "Otros"] as $cat): ?>
                <option value="<?= e($cat) ?>"
                    <?= ($_POST["categoria"] ?? "") === $cat ? "selected" : "" ?>>
                    <?= e($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="tipo_publicacion">Tipo de publicación</label>
        <select id="tipo_publicacion" name="tipo_publicacion" required>
            <option value="regalo"
                <?= ($_POST["tipo_publicacion"] ?? "") === "regalo" ? "selected" : "" ?>>
                Regalo
            </option>
            <option value="intercambio"
                <?= ($_POST["tipo_publicacion"] ?? "intercambio") === "intercambio" ? "selected" : "" ?>>
                Intercambio
            </option>
        </select>

        <label for="imagen">Fotografía del objeto (opcional)</label>
        <input
            type="file"
            id="imagen"
            name="imagen"
            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
        >

        <p>Formatos permitidos: JPG, PNG y WEBP. Máximo 3 MB.</p>

        <button type="submit">Publicar objeto</button>
    </form>
</section>

<?php require __DIR__ . "/includes/footer.php"; ?>
