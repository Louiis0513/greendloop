<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

soloVisitantes();

$mensaje = "";

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

    $mensaje = "Datos erroneos.";
}

require __DIR__ . "/includes/header.php";
?>

    <h2>Iniciar sesión</h2>
    <form method="post">
        <input type="email" name="correo" placeholder="Correo electrónico" required>
        <input type="password" name="clave" placeholder="Contraseña" required>
        <button>Entrar</button>
    </form>
    <p>¿No tienes cuenta? <a href="registro.php">Créala aquí</a></p>

<?php require __DIR__ . "/includes/footer.php"; ?>
