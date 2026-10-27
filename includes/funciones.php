<?php
require_once __DIR__ . '/cache.php';

function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}

function usuarioLogueado() {
    return isset($_SESSION["usuario"]);
}

function requiereLogin() {
    if (!usuarioLogueado()) {
        guardarMensaje("Inicia sesión para continuar.");
        header("Location: login.php");
        exit;
    }
}

// Para login y registro: quien ya inició sesión no tiene nada que hacer ahí.
function soloVisitantes() {
    if (usuarioLogueado()) {
        header("Location: catalogo.php");
        exit;
    }
}

// Etiqueta visible del tipo: en BD se guarda "intercambio", en pantalla "Trueque".
function tipoEtiqueta($tipo) {
    $tipo = strtolower(trim((string) $tipo));
    return ($tipo === "intercambio" || $tipo === "trueque") ? "Trueque" : "Regalo";
}

function esTrueque($tipo) {
    $tipo = strtolower(trim((string) $tipo));
    return $tipo === "intercambio" || $tipo === "trueque";
}

function claseActiva($archivo) {
    return basename($_SERVER["PHP_SELF"]) === $archivo ? "activo" : "";
}

// Mensaje "flash": se guarda antes de redirigir y se muestra una sola vez.
function guardarMensaje($texto) {
    $_SESSION["mensaje"] = $texto;
}

function obtenerMensaje() {
    $mensaje = $_SESSION["mensaje"] ?? "";
    unset($_SESSION["mensaje"]);
    return $mensaje;
}

/** Categorías fijas del catálogo / publicar. */
function categoriasCatalogo(): array
{
    return ["Ropa", "Libros", "Tecnología", "Hogar", "Juguetes", "Otros"];
}

function enviarHeadersCache(string $modo = 'privado'): void
{
    if (headers_sent()) {
        return;
    }
    if ($modo === 'publico') {
        // HTML breve; la fuente de verdad del catálogo es el caché de archivos (TTL 4 h + parche al editar).
        header('Cache-Control: public, max-age=120, stale-while-revalidate=60');
        header('Vary: Cookie');
    } else {
        header('Cache-Control: no-store, private');
    }
}

/* ——— Lecturas cacheadas (hosting compartido) ——— */

/**
 * Catálogo disponible. Cachea sin búsqueda de texto (tipo ± categoría).
 * @return list<array<string,mixed>>
 */
function obtenerCatalogo(mysqli $db, string $tipo = '', string $categoria = '', string $q = ''): array
{
    $q = trim($q);
    $tipoKey = cacheNormalizarTipoFiltro($tipo);
    $tipo = $tipoKey === 'all' ? '' : $tipoKey;
    $categoria = trim($categoria);
    if ($categoria !== '' && !in_array($categoria, categoriasCatalogo(), true)) {
        $categoria = '';
    }
    $usarCache = ($q === '');
    $cacheKey = cacheCatalogoKey($tipo, $categoria);

    if ($usarCache) {
        $cached = cacheGet($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $sql = "SELECT p.*, u.nombre AS propietario
            FROM productos p
            JOIN usuarios u ON p.id_usuario = u.id_usuario
            WHERE p.disponible = 1";
    $types = '';
    $params = [];

    if ($q !== '') {
        $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
        $types .= 'ss';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
    }

    if ($tipo === 'regalo') {
        $sql .= " AND LOWER(p.tipo_publicacion) IN ('regalo')";
    } elseif ($tipo === 'intercambio') {
        $sql .= " AND LOWER(p.tipo_publicacion) IN ('intercambio', 'trueque')";
    }

    if ($categoria !== '') {
        $sql .= " AND p.categoria = ?";
        $types .= 's';
        $params[] = $categoria;
    }

    $sql .= " ORDER BY p.fecha_publicacion DESC";

    $stmt = $db->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if ($usarCache) {
        cacheSet($cacheKey, $rows, CACHE_TTL_DEFAULT);
    }
    return $rows;
}

/** Últimos N productos disponibles (home). */
function obtenerRecientes(mysqli $db, int $limite = 6): array
{
    $key = 'recientes';
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return array_slice($cached, 0, $limite);
    }

    $limite = max(1, min(24, $limite));
    $stmt = $db->prepare(
        "SELECT p.*, u.nombre AS propietario
         FROM productos p
         JOIN usuarios u ON p.id_usuario = u.id_usuario
         WHERE p.disponible = 1
         ORDER BY p.fecha_publicacion DESC
         LIMIT ?"
    );
    $stmt->bind_param('i', $limite);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    cacheSet($key, $rows, CACHE_TTL_DEFAULT);
    return $rows;
}

/** Contadores del home. */
function obtenerStats(mysqli $db): array
{
    $cached = cacheGet('stats');
    if (is_array($cached)) {
        return $cached;
    }
    $row = $db->query(
        "SELECT
            (SELECT COUNT(*) FROM productos WHERE disponible = 1) AS objetos,
            (SELECT COUNT(*) FROM usuarios) AS vecinos,
            (SELECT COUNT(*) FROM solicitudes) AS trueques"
    )->fetch_assoc();
    $stats = [
        'objetos' => (int) ($row['objetos'] ?? 0),
        'vecinos' => (int) ($row['vecinos'] ?? 0),
        'trueques' => (int) ($row['trueques'] ?? 0),
    ];
    cacheSet('stats', $stats, CACHE_TTL_STATS);
    return $stats;
}

/** Producto disponible con propietario (ficha / solicitar). */
function obtenerProductoDisponible(mysqli $db, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $key = 'producto_' . $id;
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }
    if ($cached === false) {
        return null;
    }
    $stmt = $db->prepare(
        "SELECT p.*, u.nombre AS propietario
         FROM productos p
         JOIN usuarios u ON p.id_usuario = u.id_usuario
         WHERE p.id_producto = ? AND p.disponible = 1"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        cacheSet($key, $row, CACHE_TTL_PRODUCTO);
        return $row;
    }
    cacheSet($key, false, CACHE_TTL_NEGATIVO);
    return null;
}

/* ——— Parche de caché (write-through): sin wipe total ——— */

/** Carga producto + propietario (disponible o no). */
function cargarProductoConPropietario(mysqli $db, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $db->prepare(
        "SELECT p.*, u.nombre AS propietario
         FROM productos p
         JOIN usuarios u ON p.id_usuario = u.id_usuario
         WHERE p.id_producto = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function productoEntraEnFiltroCatalogo(array $producto, string $tipo, ?string $categoriaSlug): bool
{
    if ((int) ($producto['disponible'] ?? 0) !== 1) {
        return false;
    }
    if ($tipo === 'regalo') {
        if (strtolower((string) ($producto['tipo_publicacion'] ?? '')) !== 'regalo') {
            return false;
        }
    } elseif ($tipo === 'intercambio') {
        if (!esTrueque($producto['tipo_publicacion'] ?? '')) {
            return false;
        }
    }
    if ($categoriaSlug !== null) {
        $slug = cacheSlugCategoria((string) ($producto['categoria'] ?? ''));
        if ($slug !== $categoriaSlug) {
            return false;
        }
    }
    return true;
}

/** Quita id de una lista de productos cacheada. */
function cacheListaQuitarProducto(array $lista, int $idProducto): array
{
    $out = [];
    foreach ($lista as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ((int) ($row['id_producto'] ?? 0) === $idProducto) {
            continue;
        }
        $out[] = $row;
    }
    return $out;
}

/** Inserta/reemplaza producto en lista ordenada por fecha_publicacion DESC. */
function cacheListaUpsertProducto(array $lista, array $producto): array
{
    $id = (int) ($producto['id_producto'] ?? 0);
    $lista = cacheListaQuitarProducto($lista, $id);
    $lista[] = $producto;
    usort($lista, static function (array $a, array $b): int {
        $fa = (string) ($a['fecha_publicacion'] ?? '');
        $fb = (string) ($b['fecha_publicacion'] ?? '');
        $cmp = strcmp($fb, $fa);
        if ($cmp !== 0) {
            return $cmp;
        }
        return ((int) ($b['id_producto'] ?? 0)) <=> ((int) ($a['id_producto'] ?? 0));
    });
    return $lista;
}

/**
 * Parchea ficha + catálogos existentes + recientes + stats tras create/update/toggle.
 * Una sola lectura del producto; no regenera catálogos desde MySQL.
 */
function cacheParcheProducto(mysqli $db, int $idProducto): void
{
    $idProducto = (int) $idProducto;
    if ($idProducto <= 0) {
        return;
    }
    $ahora = cargarProductoConPropietario($db, $idProducto);

    if ($ahora && (int) ($ahora['disponible'] ?? 0) === 1) {
        cachePatch('producto_' . $idProducto, $ahora, CACHE_TTL_PRODUCTO);
    } else {
        cachePatch('producto_' . $idProducto, false, CACHE_TTL_NEGATIVO);
    }

    foreach (cacheKeysByPrefix('catalogo_') as $key) {
        [$tipo, $catSlug] = cacheParseCatalogoKey($key);
        if ($catSlug === false) {
            continue;
        }
        cachePatchIfPresent($key, static function ($data) use ($ahora, $idProducto, $tipo, $catSlug) {
            if (!is_array($data)) {
                return $data;
            }
            $data = cacheListaQuitarProducto($data, $idProducto);
            if ($ahora && productoEntraEnFiltroCatalogo($ahora, $tipo, $catSlug)) {
                $data = cacheListaUpsertProducto($data, $ahora);
            }
            return $data;
        });
    }

    cacheParcheRecientes($db);
    cacheParcheStats($db);
}

/** Elimina producto del caché (tras DELETE). */
function cacheParcheProductoEliminado(mysqli $db, int $idProducto): void
{
    $idProducto = (int) $idProducto;
    cacheForget('producto_' . $idProducto);
    foreach (cacheKeysByPrefix('catalogo_') as $key) {
        cachePatchIfPresent($key, static function ($data) use ($idProducto) {
            return is_array($data) ? cacheListaQuitarProducto($data, $idProducto) : $data;
        });
    }
    cacheParcheRecientes($db);
    cacheParcheStats($db);
}

/** Recalcula recientes solo si la clave ya existe (lazy). */
function cacheParcheRecientes(mysqli $db, int $limite = 6): void
{
    if (cachePeek('recientes') === null) {
        return;
    }
    $limite = max(1, min(24, $limite));
    $stmt = $db->prepare(
        "SELECT p.*, u.nombre AS propietario
         FROM productos p
         JOIN usuarios u ON p.id_usuario = u.id_usuario
         WHERE p.disponible = 1
         ORDER BY p.fecha_publicacion DESC
         LIMIT ?"
    );
    $stmt->bind_param('i', $limite);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    cachePatch('recientes', $rows, CACHE_TTL_DEFAULT);
}

/** Recalcula stats solo si la clave ya existe (lazy). */
function cacheParcheStats(mysqli $db): void
{
    if (cachePeek('stats') === null) {
        return;
    }
    $row = $db->query(
        "SELECT
            (SELECT COUNT(*) FROM productos WHERE disponible = 1) AS objetos,
            (SELECT COUNT(*) FROM usuarios) AS vecinos,
            (SELECT COUNT(*) FROM solicitudes) AS trueques"
    )->fetch_assoc();
    cachePatch('stats', [
        'objetos' => (int) ($row['objetos'] ?? 0),
        'vecinos' => (int) ($row['vecinos'] ?? 0),
        'trueques' => (int) ($row['trueques'] ?? 0),
    ], CACHE_TTL_STATS);
}
