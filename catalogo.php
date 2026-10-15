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

if ($tipo === "regalo") {
    $condiciones[] = "LOWER(p.tipo_publicacion) IN ('regalo')";
} elseif ($tipo === "intercambio") {
    $condiciones[] = "LOWER(p.tipo_publicacion) IN ('intercambio', 'trueque')";
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

$categorias = [
    ["categoria" => "Ropa"],
    ["categoria" => "Libros"],
    ["categoria" => "Tecnología"],
    ["categoria" => "Hogar"],
    ["categoria" => "Juguetes"],
    ["categoria" => "Otros"]
];

$logueado = usuarioLogueado();
$idUsuarioActual = $logueado ? (int) $_SESSION["usuario"] : 0;

require __DIR__ . "/includes/header.php";
?>

<div class="pagina-titulo">
    <h2>Catálogo de objetos</h2>
    <p>Encuentra objetos para regalar o intercambiar con la comunidad.</p>
</div>

<?php if (!$logueado): ?>
    <p class="mensaje">
        <a href="login.php">Inicia sesión</a> para solicitar objetos.
    </p>
<?php endif; ?>

<form method="get" action="catalogo.php" class="filtros-catalogo">
    <div class="filtro-campo">
        <label for="buscar">Buscar</label>
        <input
            type="search"
            id="buscar"
            name="buscar"
            placeholder="Ej. mochila, libro..."
            value="<?= e($busqueda) ?>"
        >
    </div>

    <div class="filtro-campo">
        <label for="tipo">Tipo</label>
        <select name="tipo" id="tipo">
            <option value="">Todos</option>
            <option value="regalo" <?= $tipo === "regalo" ? "selected" : "" ?>>Regalo</option>
            <option value="intercambio" <?= $tipo === "intercambio" ? "selected" : "" ?>>Intercambio</option>
        </select>
    </div>

    <div class="filtro-campo">
        <label for="categoria">Categoría</label>
        <select name="categoria" id="categoria">
            <option value="">Todas</option>
            <?php foreach ($categorias as $cat): ?>
                <option
                    value="<?= e($cat["categoria"]) ?>"
                    <?= $categoria === $cat["categoria"] ? "selected" : "" ?>
                >
                    <?= e($cat["categoria"]) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filtros-acciones">
        <button type="submit">Filtrar</button>
        <a href="catalogo.php" class="boton secundario">Limpiar</a>
    </div>
</form>

<?php if ($busqueda !== "" || $tipo !== "" || $categoria !== ""): ?>
    <div class="filtros-chips">
        <?php if ($busqueda !== ""): ?>
            <span class="chip">
                “<?= e($busqueda) ?>”
                <a href="catalogo.php?<?= e(http_build_query(array_filter(["tipo" => $tipo, "categoria" => $categoria]))) ?>" aria-label="Quitar búsqueda">×</a>
            </span>
        <?php endif; ?>
        <?php if ($tipo !== ""): ?>
            <span class="chip">
                <?= e($tipo) ?>
                <a href="catalogo.php?<?= e(http_build_query(array_filter(["buscar" => $busqueda, "categoria" => $categoria]))) ?>" aria-label="Quitar tipo">×</a>
            </span>
        <?php endif; ?>
        <?php if ($categoria !== ""): ?>
            <span class="chip">
                <?= e($categoria) ?>
                <a href="catalogo.php?<?= e(http_build_query(array_filter(["buscar" => $busqueda, "tipo" => $tipo]))) ?>" aria-label="Quitar categoría">×</a>
            </span>
        <?php endif; ?>
    </div>
<?php endif; ?>

<p class="resultado-meta">
    <span>Objetos encontrados: <strong><?= count($productos) ?></strong></span>
</p>

<?php if (count($productos) === 0): ?>
    <p class="vacio">No encontramos objetos con esos filtros.</p>
<?php else: ?>
    <div class="productos">
        <?php foreach ($productos as $p): ?>
            <?php
            $esPropio = $logueado && $idUsuarioActual === (int) $p["id_usuario"];
            $puedeSolicitar = $logueado && !$esPropio;
            ?>
            <article class="producto">
                <div class="producto-media">
                    <?php if (!empty($p["imagen"])): ?>
                        <img
                            src="<?= e($p["imagen"]) ?>"
                            alt="<?= e($p["nombre"]) ?>"
                        >
                    <?php endif; ?>
                </div>
                <div class="producto-cuerpo">
                    <div class="badges">
                        <span class="badge"><?= e($p["categoria"]) ?></span>
                        <span class="badge badge-tipo"><?= e($p["tipo_publicacion"]) ?></span>
                    </div>
                    <h3><?= e($p["nombre"]) ?></h3>
                    <p class="producto-desc"><?= e($p["descripcion"]) ?></p>
                    <p class="producto-autor">Publicado por <?= e($p["propietario"]) ?></p>

                    <?php if ($puedeSolicitar): ?>
                        <button
                            type="button"
                            class="boton"
                            data-abrir-solicitud
                            data-id="<?= (int) $p["id_producto"] ?>"
                            data-nombre="<?= e($p["nombre"]) ?>"
                            data-descripcion="<?= e($p["descripcion"]) ?>"
                            data-categoria="<?= e($p["categoria"]) ?>"
                            data-tipo="<?= e($p["tipo_publicacion"]) ?>"
                            data-autor="<?= e($p["propietario"]) ?>"
                            data-imagen="<?= e($p["imagen"] ?? "") ?>"
                        >
                            Solicitar objeto
                        </button>
                    <?php elseif ($esPropio): ?>
                        <a href="mis_publicaciones.php" class="boton secundario">Tu publicación</a>
                    <?php else: ?>
                        <a href="login.php" class="boton secundario">Inicia sesión para solicitar</a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($logueado): ?>
<div
    class="modal"
    id="modal-solicitud"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-titulo"
>
    <div class="modal-backdrop" data-cerrar-modal></div>
    <div class="modal-dialog">
        <button type="button" class="modal-cerrar" aria-label="Cerrar">×</button>
        <div class="modal-media">
            <img id="modal-imagen" alt="">
        </div>
        <div class="modal-cuerpo">
            <div class="badges" id="modal-meta"></div>
            <h3 id="modal-titulo"></h3>
            <p id="modal-descripcion"></p>
            <p class="producto-autor" id="modal-autor"></p>

            <form action="solicitar.php" method="post">
                <input type="hidden" name="id_producto" id="modal-id-producto" value="">
                <label for="modal-mensaje">Tu mensaje</label>
                <textarea
                    id="modal-mensaje"
                    name="mensaje"
                    placeholder="Cuéntale por qué te interesa este objeto"
                    required
                    minlength="5"
                ></textarea>
                <button type="submit">Enviar solicitud</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . "/includes/footer.php"; ?>
