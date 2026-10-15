<?php
session_start();

require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();

$idUsuario = (int) $_SESSION["usuario"];
$mensaje = "";
$tipoMensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $telefono = trim($_POST["telefono"] ?? "");
    $telefonoLimpio = preg_replace('/[\s()+-]/', '', $telefono);

    if (
        $telefono !== "" &&
        !preg_match('/^[0-9]{7,15}$/', $telefonoLimpio)
    ) {
        $mensaje = "Escribe un teléfono válido de 7 a 15 dígitos.";
        $tipoMensaje = "error";
    } else {
        $stmt = $mysqli->prepare(
            "UPDATE usuarios
             SET telefono = ?
             WHERE id_usuario = ?"
        );

        $stmt->bind_param("si", $telefono, $idUsuario);
        $stmt->execute();
        $stmt->close();

        $mensaje = "¡Tu teléfono se guardó correctamente!";
        $tipoMensaje = "exito";
    }
}

$stmt = $mysqli->prepare(
    "SELECT nombre, correo, telefono
     FROM usuarios
     WHERE id_usuario = ?"
);

$stmt->bind_param("i", $idUsuario);
$stmt->execute();

$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

require __DIR__ . "/includes/header.php";
?>

<section class="perfil-pagina">

    <div class="perfil-encabezado">
        <div class="perfil-icono" aria-hidden="true">♻</div>
        <h2>Mi perfil</h2>
        <p>Tus datos, tu comunidad y una segunda vida para los objetos.</p>
    </div>

    <?php if ($mensaje !== ""): ?>
        <p class="mensaje <?= $tipoMensaje === "error" ? "mensaje-error" : "" ?>">
            <?= e($mensaje) ?>
        </p>
    <?php endif; ?>

    <?php if ($usuario): ?>
        <div class="form-panel">
            <h3>Información de tu cuenta</h3>

            <div class="perfil-dato">
                <span class="perfil-etiqueta">Nombre completo</span>
                <span class="perfil-valor"><?= e($usuario["nombre"]) ?></span>
            </div>

            <div class="perfil-dato">
                <span class="perfil-etiqueta">Correo electrónico</span>
                <span class="perfil-valor"><?= e($usuario["correo"]) ?></span>
            </div>

            <form method="post" action="perfil.php" class="form-inline">
                <label for="telefono">Teléfono de contacto</label>
                <input
                    type="tel"
                    id="telefono"
                    name="telefono"
                    placeholder="Ej. 3001234567"
                    value="<?= e($usuario["telefono"] ?? "") ?>"
                    maxlength="20"
                >
                <p class="perfil-ayuda">
                    Registra tu número para facilitar la coordinación
                    de los intercambios y regalos.
                </p>
                <button type="submit">Guardar teléfono</button>
            </form>
        </div>
    <?php else: ?>
        <p class="mensaje mensaje-error">No se encontró tu cuenta.</p>
    <?php endif; ?>

</section>

<?php require __DIR__ . "/includes/footer.php"; ?>
