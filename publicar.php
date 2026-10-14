<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "");
    $tipo = $_POST["tipo"] ?? "";

    if (strlen($nombre) < 3 ||
        strlen($descripcion) < 10 ||
        $categoria === "" ||
        !in_array($tipo, ["regalo", "intercambio"], true)) {
        $mensaje = "Completa correctamente todos los campos.";
    } else {
        $idUsuario = $_SESSION["usuario"];

        $stmt = $mysqli->prepare(
            "INSERT INTO productos
            (id_usuario, nombre, descripcion, categoria, tipo_publicacion, disponible)
            VALUES (?, ?, ?, ?, ?, 1)"
        );
        $stmt->bind_param("issss", $idUsuario, $nombre, $descripcion, $categoria, $tipo);
        $stmt->execute();

        guardarMensaje("¡Objeto publicado en Green Loop!");
        header("Location: catalogo.php");
        exit;
    }
}

require __DIR__ . "/includes/header.php";
?>

    <h2>Publicar un objeto</h2>

    <form method="post">
        <input name="nombre" placeholder="Nombre del objeto" required minlength="3">
        <textarea name="descripcion" placeholder="Describe el objeto" required minlength="10"></textarea>
        <select name="categoria" required>
            <option value="">Selecciona categoría</option>
            <option>Ropa</option>
            <option>Libros</option>
            <option>Tecnología</option>
            <option>Hogar</option>
            <option>Juguetes</option>
            <option>Otros</option>
        </select>
        <select name="tipo" required>
            <option value="intercambio">Intercambio</option>
            <option value="regalo">Regalo</option>
        </select>
        <button>Publicar objeto</button>
    </form>

<?php require __DIR__ . "/includes/footer.php"; ?>
