
<?php
session_start();

require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

soloVisitantes();

$mensaje = "";
$nombre = "";
$correo = "";
$telefono = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $clave = $_POST["clave"] ?? "";

    $telefonoLimpio = preg_replace('/[\s()+-]/', '', $telefono);

    if (
        strlen($nombre) < 3 ||
        strlen($nombre) > 100 ||
        !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
        !preg_match('/^[0-9]{7,15}$/', $telefonoLimpio) ||
        strlen($clave) < 8
    ) {
        $mensaje = "Revisa los datos. Verifica el teléfono y la contraseña.";
    } else {
        $passwordHash = password_hash($clave, PASSWORD_DEFAULT);

        $stmt = $mysqli->prepare(
            "INSERT INTO usuarios
             (nombre, correo, telefono, password_hash)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssss",
            $nombre,
            $correo,
            $telefono,
            $passwordHash
        );

        try {
            $stmt->execute();

            guardarMensaje("Cuenta creada correctamente.");
            header("Location: login.php");
            exit;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                $mensaje = "El correo ya está registrado.";
            } else {
                $mensaje = "No se pudo crear la cuenta. Inténtalo nuevamente.";
            }
        }
    }
}

require __DIR__ . "/includes/header.php";
?>

<section>
    <h2>Crear cuenta</h2>

    <p>Únete a Green Loop y dale una segunda vida a los objetos.</p>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje"><?= e($mensaje) ?></p>
    <?php endif; ?>

    <form method="post" action="registro.php">
        <label for="nombre">Nombre completo</label>
        <input
            type="text"
            id="nombre"
            name="nombre"
            placeholder="Nombre completo"
            value="<?= e($nombre) ?>"
            minlength="3"
            maxlength="100"
            required
        >

        <label for="correo">Correo electrónico</label>
        <input
            type="email"
            id="correo"
            name="correo"
            placeholder="Correo electrónico"
            value="<?= e($correo) ?>"
            required
        >

        <label for="telefono">Teléfono de contacto</label>
        <input
            type="tel"
            id="telefono"
            name="telefono"
            placeholder="Ej. 3001234567"
            value="<?= e($telefono) ?>"
            required
        >

        <label for="clave">Contraseña</label>
        <input
            type="password"
            id="clave"
            name="clave"
            placeholder="Mínimo 8 caracteres"
            minlength="8"
            required
        >

        <button type="submit">Registrarme</button>
    </form>

    <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
</section>

<?php require __DIR__ . "/includes/footer.php"; ?>
