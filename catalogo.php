
<?php
session_start();

require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

$busqueda = trim($_GET["buscar"] ?? "");
$tipo = trim($_GET["tipo"] ?? "");
$categoria = trim($_GET["categoria"] ?? "");

$sql = "SELECT p.*, u.nombre AS propietario
        FROM productos p
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE p.disponible = 1";

$condiciones = [];
$parametros = [];
$tipos = "";

if ($busqueda !== "") {
    $condiciones[] = "(p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $parametros[] = "%" . $busqueda . "%";
    $parametros[] = "%" . $busqueda . "%";
    $tipos .= "ss";
}

if ($tipo !== "") {
    $condiciones[] = "p.tipo_publicacion = ?";
    $parametros[] = $tipo;
    $tipos .= "s";
}

if ($categoria !== "") {
    $condiciones[] = "p.categoria = ?";
    $parametros[] = $categoria;
    $tipos .= "s";
}

if (count($condiciones) > 0) {
    $sql .= " AND " . implode(" AND ", $condiciones);
}

$sql .= " ORDER BY p.fecha_publicacion DESC";

$stmt = $mysqli->prepare($sql);

if (count($parametros) > 0) {
    $stmt->bind_param($tipos, ...$parametros);
}

$stmt->execute();
$resultado = $stmt->get_result();
$productos = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Categorías del filtro */
$categorias = [
    ["categoria" => "Ropa"],
    ["categoria" => "Libros"],
    ["categoria" => "Tecnología"],
    ["categoria" => "Hogar"],
    ["categoria" => "Juguetes"],
    ["categoria" => "Otros"]
];

require __DIR__ . "/includes/header.php";
?>

<h2>Catálogo de objetos</h2>

<p>Encuentra objetos para regalar o intercambiar con la comunidad.</p>

<?php if (!usuarioLogueado()): ?>
    <p><a href="login.php">Inicia sesión</a> para solicitar objetos.</p>
<?php endif; ?>

<form method="get" action="catalogo.php" class="filtros-catalogo">

    <label for="buscar">Buscar objeto</label>
    <input
        type="search"
        id="buscar"
        name="buscar"
        placeholder="Ej. mochila, libro..."
        value="<?= e($busqueda) ?>"
    >

    <label for="tipo">Tipo de publicación</label>
    <select name="tipo" id="tipo">
        <option value="">Todos los tipos</option>
        <option value="Regalo" <?= $tipo === "Regalo" ? "selected" : "" ?>>
            Regalo
        </option>
        <option value="Trueque" <?= $tipo === "Trueque" ? "selected" : "" ?>>
            Trueque
        </option>
    </select>

    <label for="categoria">Categoría</label>
    <select name="categoria" id="categoria">
        <option value="">Todas las categorías</option>

        <?php foreach ($categorias as $cat): ?>
            <option
                value="<?= e($cat["categoria"]) ?>"
                <?= $categoria === $cat["categoria"] ? "selected" : "" ?>
            >
                <?= e($cat["categoria"]) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Buscar y filtrar</button>
    <a href="catalogo.php">Limpiar filtros</a>

</form>

<p>
    Objetos encontrados: <strong><?= count($productos) ?></strong>
</p>

<?php if (count($productos) === 0): ?>
    <p>No encontramos objetos con esos filtros.</p>
<?php endif; ?>

<div class="productos">

    <?php foreach ($productos as $p): ?>

        <article class="producto">

            <?php if (!empty($p["imagen"])): ?>
                <img
                    src="<?= e($p["imagen"]) ?>"
                    alt="<?= e($p["nombre"]) ?>"
                    style="width: 100%; max-width: 300px; height: 200px; object-fit: cover; border-radius: 10px;"
                >
            <?php endif; ?>

            <h3><?= e($p["nombre"]) ?></h3>

            <p><?= e($p["descripcion"]) ?></p>

            <p>Categoría: <?= e($p["categoria"]) ?></p>

            <p>Tipo: <?= e($p["tipo_publicacion"]) ?></p>

            <p>Publicado por: <?= e($p["propietario"]) ?></p>

            <?php if (
                usuarioLogueado() &&
                (int) $_SESSION["usuario"] !== (int) $p["id_usuario"]
            ): ?>
                <form action="solicitar.php" method="post">

                    <input
                        type="hidden"
                        name="id_producto"
                        value="<?= (int) $p["id_producto"] ?>"
                    >

                    <textarea
                        name="mensaje"
                        placeholder="Escribe tu solicitud"
                        required
                        minlength="5"
                    ></textarea>

                    <button type="submit">Solicitar objeto</button>

                </form>
            <?php endif; ?>

        </article>

    <?php endforeach; ?>

</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
