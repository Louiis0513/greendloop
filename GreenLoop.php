<?php
session_start();

$host = "localhost";
$dbname = "green_loop";
$user = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Error de conexión. Importa database.sql en phpMyAdmin.");
}

function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}

$pagina = $_GET["page"] ?? "inicio";
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";

    if ($accion === "registro") {
        $nombre = trim($_POST["nombre"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $clave = $_POST["clave"] ?? "";

        if (strlen($nombre) < 3 ||
            !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
            strlen($clave) < 8) {
            $mensaje = "Revisa los datos ingresados.";
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO usuarios (nombre, correo, password_hash)
                 VALUES (?, ?, ?)"
            );

            try {
                $stmt->execute([
                    $nombre,
                    $correo,
                    password_hash($clave, PASSWORD_DEFAULT)
                ]);
                $mensaje = "Cuenta creada correctamente.";
            } catch (PDOException $e) {
                $mensaje = "El correo ya está registrado.";
            }
        }
    }

    if ($accion === "login") {
        $stmt = $pdo->prepare(
            "SELECT * FROM usuarios WHERE correo = ?"
        );
        $stmt->execute([trim($_POST["correo"] ?? "")]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario &&
            password_verify($_POST["clave"] ?? "", $usuario["password_hash"])) {
            session_regenerate_id(true);
            $_SESSION["usuario"] = $usuario["id_usuario"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["rol"] = $usuario["rol"];
            header("Location: index.php");
            exit;
        }

        $mensaje = "Correo o contraseña incorrectos.";
    }

    if ($accion === "publicar" && isset($_SESSION["usuario"])) {
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
            $stmt = $pdo->prepare(
                "INSERT INTO productos
                (id_usuario, nombre, descripcion, categoria, tipo_publicacion, disponible)
                VALUES (?, ?, ?, ?, ?, 1)"
            );

            $stmt->execute([
                $_SESSION["usuario"],
                $nombre,
                $descripcion,
                $categoria,
                $tipo
            ]);

            $mensaje = "¡Objeto publicado en Green Loop!";
        }
    }

    if ($accion === "solicitar" && isset($_SESSION["usuario"])) {
        $id = (int)($_POST["id_producto"] ?? 0);

        $stmt = $pdo->prepare(
            "SELECT * FROM productos WHERE id_producto = ? AND disponible = 1"
        );
        $stmt->execute([$id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto ||
            $producto["id_usuario"] == $_SESSION["usuario"]) {
            $mensaje = "No puedes solicitar este objeto.";
        } else {
            $tipo = $producto["tipo_publicacion"];
            $texto = trim($_POST["mensaje"] ?? "");

            $stmt = $pdo->prepare(
                "INSERT INTO solicitudes
                (id_producto, id_solicitante, id_propietario,
                 tipo_solicitud, mensaje)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $id,
                $_SESSION["usuario"],
                $producto["id_usuario"],
                $tipo,
                $texto
            ]);

            $mensaje = "Solicitud enviada correctamente.";
        }
    }

    if ($accion === "negociar" && isset($_SESSION["usuario"])) {
        $id = (int)($_POST["id_solicitud"] ?? 0);

        $stmt = $pdo->prepare(
            "UPDATE solicitudes
             SET detalles_negociacion = ?, lugar_encuentro = ?,
                 fecha_encuentro = ?, hora_encuentro = ?
             WHERE id_solicitud = ?
             AND (id_solicitante = ? OR id_propietario = ?)"
        );

        $stmt->execute([
            trim($_POST["detalles"] ?? ""),
            trim($_POST["lugar"] ?? ""),
            $_POST["fecha"] ?: null,
            $_POST["hora"] ?: null,
            $id,
            $_SESSION["usuario"],
            $_SESSION["usuario"]
        ]);

        $mensaje = "Negociación actualizada.";
    }

    if ($accion === "salir") {
        session_destroy();
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Green Loop</title>
<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f7ef;
    color: #244333;
}
header {
    background: #1c6245;
    color: white;
    padding: 20px;
    text-align: center;
}
nav {
    background: #dceedd;
    padding: 15px;
    text-align: center;
}
nav a {
    margin: 8px;
    color: #1c6245;
    text-decoration: none;
    font-weight: bold;
}
main {
    max-width: 1000px;
    margin: 30px auto;
    padding: 20px;
}
h1, h2 { color: #1c6245; }
form, .producto {
    background: white;
    padding: 20px;
    margin: 15px 0;
    border-radius: 12px;
    box-shadow: 0 2px 8px #0001;
}
input, textarea, select, button {
    box-sizing: border-box;
    width: 100%;
    padding: 12px;
    margin: 7px 0;
    border: 1px solid #c5d8c8;
    border-radius: 7px;
}
button {
    background: #1c6245;
    color: white;
    cursor: pointer;
    border: none;
}
.productos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 15px;
}
.mensaje {
    background: #dceedd;
    padding: 12px;
    border-radius: 8px;
}
</style>
</head>
<body>

<header>("Location: GreenLoop.php");
</header>

<nav>
    <a href="GreenLoop.php?page=inicio">Inicio</a>
    <a href="GreenLoop.php?page=catalogo">Explorar objetos</a>
    <a href="GreenLoop.php?page=registro">Crear cuenta</a>
    <a href="GreenLoop.php?page=login">Iniciar sesión</a>
    <a href="GreenLoop.php?page=publicar">Publicar</a>
    <a href="GreenLoop.php?page=mis_solicitudes">Mis solicitudes</a>

    <?php if (isset($_SESSION["usuario"])): ?>
        <form method="post" style="display:inline;box-shadow:none;padding:0">
            <button type="submit" name="accion" value="salir">
                Cerrar sesión
            </button>
        </form>
    <?php endif; ?>
    
</nav>


<main>

<?php if ($mensaje !== ""): ?>
    <p class="mensaje"><?= e($mensaje) ?></p>
<?php endif; ?>

<?php if ($pagina === "inicio"): ?>

    <h2>Comparte, intercambia y regala</h2>
    <p>Green Loop conecta personas que quieren reutilizar objetos
    en lugar de tirarlos a la basura.</p>
    <p><a href="index.php?page=catalogo">Explorar el catálogo</a></p>

<?php elseif ($pagina === "registro"): ?>

    <h2>Crear cuenta</h2>
    <form method="post">
        <input type="hidden" name="accion" value="registro">
        <input name="nombre" placeholder="Nombre completo" required minlength="3">
        <input type="email" name="correo" placeholder="Correo electrónico" required>
        <input type="password" name="clave" placeholder="Contraseña (mínimo 8 caracteres)" required minlength="8">
        <button>Registrarme</button>
    </form>

<?php elseif ($pagina === "login"): ?>

    <h2>Iniciar sesión</h2>
    <form method="post">
        <input type="hidden" name="accion" value="login">
        <input type="email" name="correo" placeholder="Correo electrónico" required>
        <input type="password" name="clave" placeholder="Contraseña" required>
        <button>Entrar</button>
    </form>

<?php elseif ($pagina === "publicar"): ?>

    <h2>Publicar un objeto</h2>

    <?php if (isset($_SESSION["usuario"])): ?>
    <form method="post">
        <input type="hidden" name="accion" value="publicar">
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
    <?php else: ?>
        <p>Debes iniciar sesión para publicar.</p>
    <?php endif; ?>

<?php elseif ($pagina === "catalogo"): ?>

    <h2>Catálogo de objetos</h2>
    <div class="productos">
    <?php
    $stmt = $pdo->query(
        "SELECT p.*, u.nombre AS propietario
         FROM productos p
         JOIN usuarios u ON p.id_usuario = u.id_usuario
         WHERE p.disponible = 1
         ORDER BY p.fecha_publicacion DESC"
    );

    foreach ($stmt as $p):
    ?>
        <article class="producto">
            <h3><?= e($p["nombre"]) ?></h3>
            <p><?= e($p["descripcion"]) ?></p>
            <p>Categoría: <?= e($p["categoria"]) ?></p>
            <p>Tipo: <?= e($p["tipo_publicacion"]) ?></p>
            <p>Publicado por: <?= e($p["propietario"]) ?></p>

            <?php if (isset($_SESSION["usuario"]) &&
                $_SESSION["usuario"] != $p["id_usuario"]): ?>
                <form method="post">
                    <input type="hidden" name="accion" value="solicitar">
                    <input type="hidden" name="id_producto" value="<?= $p["id_producto"] ?>">
                    <textarea name="mensaje" placeholder="Escribe tu solicitud" required minlength="5"></textarea>
                    <button>Solicitar objeto</button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    </div>

<?php elseif ($pagina === "mis_solicitudes"): ?>

    <h2>Solicitudes y negociaciones</h2>

    <?php if (isset($_SESSION["usuario"])):
        $stmt = $pdo->prepare(
            "SELECT s.*, p.nombre AS objeto
             FROM solicitudes s
             JOIN productos p ON s.id_producto = p.id_producto
             WHERE s.id_solicitante = ? OR s.id_propietario = ?
             ORDER BY s.fecha_solicitud DESC"
        );
        $stmt->execute([$_SESSION["usuario"], $_SESSION["usuario"]]);

        foreach ($stmt as $s):
    ?>
        <article class="producto">
            <h3><?= e($s["objeto"]) ?></h3>
            <p><?= e($s["mensaje"]) ?></p>
            <p>Estado: <?= e($s["estado"]) ?></p>

            <form method="post">
                <input type="hidden" name="accion" value="negociar">
                <input type="hidden" name="id_solicitud" value="<?= $s["id_solicitud"] ?>">
                <textarea name="detalles" placeholder="Detalles de negociación"><?= e($s["detalles_negociacion"]) ?></textarea>
                <input name="lugar" placeholder="Lugar de encuentro" value="<?= e($s["lugar_encuentro"]) ?>">
                <label>Fecha del encuentro</label>
                <input type="date" name="fecha" value="<?= e($s["fecha_encuentro"]) ?>">
                <label>Hora del encuentro</label>
                <input type="time" name="hora" value="<?= e($s["hora_encuentro"]) ?>">
                <button>Guardar negociación</button>
            </form>
        </article>
    <?php endforeach; else: ?>
        <p>Inicia sesión para ver tus solicitudes.</p>
    <?php endif; ?>

<?php endif; ?>

</main>
</body>
</html>