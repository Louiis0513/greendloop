<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

soloVisitantes();
enviarHeadersCache('privado');

$mensaje = "";
$claseMensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST["correo"] ?? "");

    $stmt = $mysqli->prepare(
        "SELECT * FROM usuarios WHERE correo = ?"
    );
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();

    if ($usuario &&
        password_verify($_POST["clave"] ?? "", $usuario["password_hash"])) {
        session_regenerate_id(true);
        $_SESSION["usuario"] = $usuario["id_usuario"];
        $_SESSION["nombre"] = $usuario["nombre"];
        $_SESSION["rol"] = $usuario["rol"];
        header("Location: catalogo.php");
        exit;
    }

    $mensaje = "Correo o contraseña incorrectos.";
    $claseMensaje = "mensaje-error";
}

require __DIR__ . "/includes/header.php";
?>

<section class="auth-wrap">
    <div class="pagina-titulo">
        <h2>Iniciar sesión</h2>
        <p>Entra para publicar, solicitar y negociar objetos.</p>
    </div>

    <div class="demo-box">
        <strong>Cuenta demo (presentación)</strong><br>
        Correo: <code>admin@demo.com</code><br>
        Contraseña: <code>Admin123!</code>
    </div>

    <form method="post">
        <label for="correo">Correo electrónico</label>
        <input type="email" id="correo" name="correo" placeholder="Correo electrónico"
               value="<?= e($_POST["correo"] ?? "admin@demo.com") ?>" required autocomplete="username">

        <label for="clave">Contraseña</label>
        <input type="password" id="clave" name="clave" placeholder="Contraseña" required autocomplete="current-password">

        <button type="submit">Entrar</button>
    </form>

    <p>¿No tienes cuenta? <a href="registro.php">Créala aquí</a></p>
</section>

<?php require __DIR__ . "/includes/footer.php"; ?>
