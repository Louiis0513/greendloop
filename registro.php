<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

soloVisitantes();

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $clave = $_POST["clave"] ?? "";

    if (strlen($nombre) < 3 ||
        !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
        strlen($clave) < 8) {
        $mensaje = "Revisa los datos ingresados.";
    } else {
        $passwordHash = password_hash($clave, PASSWORD_DEFAULT);

        $stmt = $mysqli->prepare(
            "INSERT INTO usuarios (nombre, correo, password_hash)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $nombre, $correo, $passwordHash);

        try {
            $stmt->execute();
            guardarMensaje("Cuenta creada correctamente.");
            header("Location: login.php");
            exit;
        } catch (mysqli_sql_exception $e) {
            $mensaje = "El correo ya está registrado.";
        }
    }
}

require __DIR__ . "/includes/header.php";
?>

    <h2>Crear cuenta</h2>
    <form method="post">
        <input name="nombre" placeholder="Nombre completo" required minlength="3">
        <input type="email" name="correo" placeholder="Correo electrónico" required>
        <input type="password" name="clave" placeholder="Contraseña (mínimo 8 caracteres)" required minlength="8">
        <button>Registrarme</button>
    </form>
    <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>

<?php require __DIR__ . "/includes/footer.php"; ?>
