<?php
declare(strict_types=1);

/**
 * Caché en archivos para hosting compartido (sin Redis/Memcached).
 * - TTL largo (4 h) como resync de seguridad.
 * - Escritura atómica (tmp + rename).
 * - Preferir parche (write-through) frente a invalidar todo.
 */
const CACHE_DIR = __DIR__ . '/../cache';
const CACHE_TTL_DEFAULT = 14400;   // 4 h — catálogo / recientes / ficha
const CACHE_TTL_STATS = 14400;     // 4 h
const CACHE_TTL_PRODUCTO = 14400;  // 4 h
const CACHE_TTL_NEGATIVO = 300;    // 5 min — miss (producto no disponible)

function cacheDir(): string
{
    if (!is_dir(CACHE_DIR)) {
        @mkdir(CACHE_DIR, 0775, true);
    }
    return CACHE_DIR;
}

function cachePath(string $key): string
{
    $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $key) ?: 'key';
    return cacheDir() . '/' . $safe . '.cache';
}

function cacheGet(string $key): mixed
{
    $path = cachePath($key);
    if (!is_file($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return null;
    }
    $payload = @unserialize($raw, ['allowed_classes' => false]);
    if (!is_array($payload) || !array_key_exists('expires', $payload) || !array_key_exists('data', $payload)) {
        @unlink($path);
        return null;
    }
    if ((int) $payload['expires'] < time()) {
        @unlink($path);
        return null;
    }
    return $payload['data'];
}

/**
 * Lee data + expires restantes sin borrar si está vencido (null si no hay archivo válido).
 * @return array{data:mixed,ttl:int}|null
 */
function cachePeek(string $key): ?array
{
    $path = cachePath($key);
    if (!is_file($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return null;
    }
    $payload = @unserialize($raw, ['allowed_classes' => false]);
    if (!is_array($payload) || !array_key_exists('expires', $payload) || !array_key_exists('data', $payload)) {
        @unlink($path);
        return null;
    }
    $ttl = (int) $payload['expires'] - time();
    if ($ttl < 1) {
        @unlink($path);
        return null;
    }
    return ['data' => $payload['data'], 'ttl' => $ttl];
}

function cacheSet(string $key, mixed $data, int $ttl = CACHE_TTL_DEFAULT): void
{
    $path = cachePath($key);
    $payload = serialize([
        'expires' => time() + max(1, $ttl),
        'data' => $data,
    ]);
    $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $payload, LOCK_EX) === false) {
        return;
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        @file_put_contents($path, $payload, LOCK_EX);
    }
}

/** Actualiza data y renueva TTL al valor dado (por defecto 4 h). */
function cachePatch(string $key, mixed $data, int $ttl = CACHE_TTL_DEFAULT): void
{
    cacheSet($key, $data, $ttl);
}

/**
 * Si la clave existe y es válida, aplica $fn(data) y guarda.
 * No crea la clave si no existe (lazy: el público la genera).
 * @param callable(mixed):mixed $fn
 */
function cachePatchIfPresent(string $key, callable $fn, int $ttl = CACHE_TTL_DEFAULT): void
{
    $peek = cachePeek($key);
    if ($peek === null) {
        return;
    }
    $nuevo = $fn($peek['data']);
    cacheSet($key, $nuevo, $ttl);
}

function cacheForget(string $key): void
{
    $path = cachePath($key);
    if (is_file($path)) {
        @unlink($path);
    }
}

function cacheForgetPrefix(string $prefix): void
{
    $dir = cacheDir();
    $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $prefix) ?: '';
    if ($safe === '' || !is_dir($dir)) {
        return;
    }
    foreach (glob($dir . '/' . $safe . '*.cache') ?: [] as $file) {
        @unlink($file);
    }
}

/**
 * Claves lógicas existentes con un prefijo (sin extensión).
 * @return list<string>
 */
function cacheKeysByPrefix(string $prefix): array
{
    $dir = cacheDir();
    $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $prefix) ?: '';
    if ($safe === '' || !is_dir($dir)) {
        return [];
    }
    $keys = [];
    foreach (glob($dir . '/' . $safe . '*.cache') ?: [] as $file) {
        $base = basename($file, '.cache');
        if ($base !== '') {
            $keys[] = $base;
        }
    }
    return $keys;
}

/** Clave de catálogo: catalogo_t_{tipo}_c_{all|slug}. */
function cacheCatalogoKey(string $tipo = '', string $categoria = ''): string
{
    $tipoKey = cacheNormalizarTipoFiltro($tipo);
    $catKey = $categoria !== '' ? cacheSlugCategoria($categoria) : 'all';
    return 'catalogo_t_' . $tipoKey . '_c_' . $catKey;
}

/**
 * Parsea catalogo_t_{tipo}_c_{cat} → [tipo, categoria|null].
 * categoria null = "all"; false = clave inválida.
 * @return array{0:string,1:?string|false}
 */
function cacheParseCatalogoKey(string $key): array
{
    if (!preg_match('/^catalogo_t_(all|regalo|intercambio)_c_([a-z0-9_-]+)$/', $key, $m)) {
        return ['', false];
    }
    $tipo = $m[1] === 'all' ? '' : $m[1];
    $cat = $m[2] === 'all' ? null : $m[2];
    return [$tipo, $cat];
}

function cacheNormalizarTipoFiltro(string $tipo): string
{
    $tipo = strtolower(trim($tipo));
    if ($tipo === 'regalo') {
        return 'regalo';
    }
    if ($tipo === 'intercambio' || $tipo === 'trueque') {
        return 'intercambio';
    }
    return 'all';
}

function cacheSlugCategoria(string $categoria): string
{
    $safe = strtolower(trim($categoria));
    $safe = preg_replace('/[^a-z0-9]+/', '_', $safe) ?: 'cat';
    return trim($safe, '_') ?: 'cat';
}

/**
 * Invalidación total (resync de emergencia). Preferir funciones cacheParche* .
 */
function cacheInvalidarCatalogo(): void
{
    cacheForget('recientes');
    cacheForget('stats');
    cacheForgetPrefix('catalogo_');
    cacheForgetPrefix('producto_');
}
