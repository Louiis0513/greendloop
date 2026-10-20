<?php
session_start();

require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();

$idUsuario = (int) $_SESSION["usuario"];
$filtro = trim($_GET["vista"] ?? "todas");
if (!in_array($filtro, ["todas", "recibidas", "enviadas", "negociacion"], true)) {
    $filtro = "todas";
}

$stmt = $mysqli->prepare(
    "SELECT s.*,
            p.nombre AS objeto,
            p.tipo_publicacion,
            p.imagen,
            solicitante.nombre AS nombre_solicitante,
            solicitante.telefono AS telefono_solicitante,
            propietario.nombre AS nombre_propietario,
            propietario.telefono AS telefono_propietario
     FROM solicitudes s
     JOIN productos p
        ON s.id_producto = p.id_producto
     JOIN usuarios solicitante
        ON s.id_solicitante = solicitante.id_usuario
     JOIN usuarios propietario
        ON s.id_propietario = propietario.id_usuario
     WHERE s.id_solicitante = ? OR s.id_propietario = ?
     ORDER BY s.fecha_solicitud DESC"
);

$stmt->bind_param("ii", $idUsuario, $idUsuario);
$stmt->execute();

$todas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Productos ofrecidos en cada trueque, agrupados por solicitud.
$ofrecimientosPorSolicitud = [];
if (count($todas) > 0) {
    $idsSolicitudes = array_map(
        fn ($s) => (int) $s["id_solicitud"],
        $todas
    );
    $marcas = implode(",", array_fill(0, count($idsSolicitudes), "?"));
    $stmt = $mysqli->prepare(
        "SELECT o.id_solicitud, o.elegido,
                p.id_producto, p.nombre, p.categoria, p.imagen
         FROM solicitud_ofrecimientos o
         JOIN productos p ON p.id_producto = o.id_producto_ofrecido
         WHERE o.id_solicitud IN ($marcas)
         ORDER BY o.elegido DESC, o.id_ofrecimiento ASC"
    );
    $stmt->bind_param(str_repeat("i", count($idsSolicitudes)), ...$idsSolicitudes);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $of) {
        $ofrecimientosPorSolicitud[(int) $of["id_solicitud"]][] = $of;
    }
    $stmt->close();
}

$conteos = [
    "todas" => count($todas),
    "recibidas" => 0,
    "enviadas" => 0,
    "negociacion" => 0
];

foreach ($todas as $s) {
    if ((int) $s["id_propietario"] === $idUsuario) {
        $conteos["recibidas"]++;
    }
    if ((int) $s["id_solicitante"] === $idUsuario) {
        $conteos["enviadas"]++;
    }
    $estado = strtolower(trim($s["estado"] ?? ""));
    if ($estado === "pendiente" || $estado === "aceptada") {
        $conteos["negociacion"]++;
    }
}

$solicitudes = array_values(array_filter($todas, function ($s) use ($filtro, $idUsuario) {
    $estado = strtolower(trim($s["estado"] ?? ""));
    if ($filtro === "recibidas") {
        return (int) $s["id_propietario"] === $idUsuario;
    }
    if ($filtro === "enviadas") {
        return (int) $s["id_solicitante"] === $idUsuario;
    }
    if ($filtro === "negociacion") {
        return $estado === "pendiente" || $estado === "aceptada";
    }
    return true;
}));

require __DIR__ . "/includes/header.php";
?>

<div class="pagina-titulo">
    <h2>Solicitudes y negociaciones</h2>
    <p>Gestiona pedidos, aceptaciones y detalles de encuentro.</p>
</div>

<nav class="tabs-flujo" aria-label="Filtrar solicitudes">
    <a href="mis_solicitudes.php?vista=todas" class="<?= $filtro === "todas" ? "activo" : "" ?>">
        Todas <span class="count">(<?= (int) $conteos["todas"] ?>)</span>
    </a>
    <a href="mis_solicitudes.php?vista=recibidas" class="<?= $filtro === "recibidas" ? "activo" : "" ?>">
        Recibidas <span class="count">(<?= (int) $conteos["recibidas"] ?>)</span>
    </a>
    <a href="mis_solicitudes.php?vista=enviadas" class="<?= $filtro === "enviadas" ? "activo" : "" ?>">
        Enviadas <span class="count">(<?= (int) $conteos["enviadas"] ?>)</span>
    </a>
    <a href="mis_solicitudes.php?vista=negociacion" class="<?= $filtro === "negociacion" ? "activo" : "" ?>">
        En negociación <span class="count">(<?= (int) $conteos["negociacion"] ?>)</span>
    </a>
</nav>

<?php if (count($solicitudes) === 0): ?>
    <p class="vacio">
        No hay solicitudes en esta vista.
        <a href="catalogo.php">Explora el catálogo</a>
        para pedir un objeto.
    </p>
<?php else: ?>
    <div class="lista-solicitudes">
        <?php foreach ($solicitudes as $s): ?>
            <?php
            $estadoActual = strtolower(trim($s["estado"] ?? ""));
            $nombresEstados = [
                "pendiente" => "Pendiente",
                "aceptada" => "Aceptada",
                "rechazada" => "Rechazada",
                "cancelada" => "Cancelada"
            ];
            $estadoTexto = $nombresEstados[$estadoActual] ?? "Sin estado";
            $tipoPub = $s["tipo_publicacion"] ?? $s["tipo_solicitud"] ?? "";
            $esTruequeSolicitud = esTrueque($tipoPub);
            $tipoTexto = $tipoPub !== "" ? tipoEtiqueta($tipoPub) : "Solicitud";
            $esPropietario = (int) $s["id_propietario"] === $idUsuario;
            $rolTexto = $esPropietario ? "Te la enviaron" : "La enviaste tú";
            ?>
            <article class="solicitud">
                <div class="solicitud-cabecera">
                    <h3><?= e($s["objeto"]) ?></h3>
                    <div class="badges">
                        <span class="badge badge-tipo <?= $esTruequeSolicitud ? "es-trueque" : "es-regalo" ?>"><?= e($tipoTexto) ?></span>
                        <span class="badge badge-estado es-<?= e($estadoActual) ?>">
                            <?= e($estadoTexto) ?>
                        </span>
                    </div>
                </div>

                <div class="solicitud-meta">
                    <span class="solicitud-rol"><?= e($rolTexto) ?></span>
                    <span>
                        <?= $esPropietario
                            ? "De " . e($s["nombre_solicitante"])
                            : "Para " . e($s["nombre_propietario"]) ?>
                    </span>
                </div>

                <p><?= e($s["mensaje"]) ?></p>

                <?php if ($esTruequeSolicitud): ?>
                    <?php
                    $ofrecidosSolicitud = $ofrecimientosPorSolicitud[(int) $s["id_solicitud"]] ?? [];
                    $elegidoSolicitud = null;
                    foreach ($ofrecidosSolicitud as $of) {
                        if ((int) $of["elegido"] === 1) {
                            $elegidoSolicitud = $of;
                        }
                    }
                    $puedeElegir = $esPropietario
                        && $estadoActual === "pendiente"
                        && count($ofrecidosSolicitud) > 0;
                    ?>
                    <section class="trueque-ofrecidos" aria-label="Objetos ofrecidos en este trueque">
                        <?php if ($elegidoSolicitud && $estadoActual === "aceptada"): ?>
                            <h4>Trueque cerrado</h4>
                            <p class="trueque-resumen">
                                <strong><?= e($s["objeto"]) ?></strong>
                                <span aria-hidden="true">⇄</span>
                                <strong><?= e($elegidoSolicitud["nombre"]) ?></strong>
                            </p>
                        <?php elseif ($puedeElegir): ?>
                            <h4>Objetos que te ofrecen · elige uno</h4>
                        <?php else: ?>
                            <h4>Objetos ofrecidos en este trueque</h4>
                        <?php endif; ?>

                        <?php if (count($ofrecidosSolicitud) === 0): ?>
                            <p class="form-ayuda">Esta solicitud no trae objetos ofrecidos. Puedes rechazarla y pedir que la envíen de nuevo desde el catálogo.</p>
                        <?php else: ?>
                            <?php if ($puedeElegir): ?>
                                <form action="gestionar_solicitud.php" method="post" class="form-elegir-trueque">
                                    <input type="hidden" name="id_solicitud" value="<?= (int) $s["id_solicitud"] ?>">
                            <?php endif; ?>

                            <div class="ofrecidos-grid es-mini">
                                <?php foreach ($ofrecidosSolicitud as $of): ?>
                                    <?php $esElegido = (int) $of["elegido"] === 1; ?>
                                    <<?= $puedeElegir ? "label" : "div" ?> class="ofrecido-card es-mini<?= $esElegido ? " es-elegido" : "" ?>">
                                        <?php if ($puedeElegir): ?>
                                            <input
                                                type="radio"
                                                name="id_producto_ofrecido"
                                                value="<?= (int) $of["id_producto"] ?>"
                                                required
                                            >
                                        <?php endif; ?>
                                        <span class="ofrecido-media<?= empty($of["imagen"]) ? " sin-foto" : "" ?>">
                                            <?php if (!empty($of["imagen"])): ?>
                                                <img src="<?= e($of["imagen"]) ?>" alt="" loading="lazy">
                                            <?php endif; ?>
                                        </span>
                                        <span class="ofrecido-info">
                                            <strong><?= e($of["nombre"]) ?></strong>
                                            <span class="badge"><?= e($of["categoria"]) ?></span>
                                        </span>
                                        <?php if ($esElegido): ?>
                                            <span class="ofrecido-check">Elegido</span>
                                        <?php elseif ($puedeElegir): ?>
                                            <span class="ofrecido-check" aria-hidden="true">Elegir este</span>
                                        <?php endif; ?>
                                    </<?= $puedeElegir ? "label" : "div" ?>>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($puedeElegir): ?>
                                    <button type="submit" name="accion" value="aceptar_trueque">Aceptar trueque con el objeto elegido</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <?php if ($estadoActual === "aceptada"): ?>
                    <?php if ((int) $s["id_solicitante"] === $idUsuario): ?>
                        <p class="contacto-box">
                            Teléfono del propietario:
                            <strong><?= e($s["telefono_propietario"] ?: "No registrado") ?></strong>
                        </p>
                    <?php else: ?>
                        <p class="contacto-box">
                            Teléfono de quien solicitó:
                            <strong><?= e($s["telefono_solicitante"] ?: "No registrado") ?></strong>
                        </p>
                    <?php endif; ?>

                    <form
                        action="gestionar_solicitud.php"
                        method="post"
                        class="form-inline"
                        onsubmit="return confirm('¿Seguro que deseas cancelar esta negociación?');"
                    >
                        <input type="hidden" name="id_solicitud" value="<?= (int) $s["id_solicitud"] ?>">
                        <button type="submit" name="accion" value="cancelada" class="peligro">
                            Cancelar negociación
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($estadoActual === "pendiente" || $estadoActual === "aceptada"): ?>
                    <form action="negociar.php" method="post" class="form-negociacion">
                        <h4>Detalles de negociación</h4>
                        <p class="form-ayuda">Acuerda lugar, fecha y hora antes de aceptar o rechazar.</p>
                        <input type="hidden" name="id_solicitud" value="<?= (int) $s["id_solicitud"] ?>">

                        <label for="detalles-<?= (int) $s["id_solicitud"] ?>">Detalles</label>
                        <textarea
                            id="detalles-<?= (int) $s["id_solicitud"] ?>"
                            name="detalles"
                            placeholder="Notas de la negociación (qué ofreces a cambio, condiciones, etc.)"
                        ><?= e($s["detalles_negociacion"] ?? "") ?></textarea>

                        <div class="solicitud-grid">
                            <div>
                                <label for="lugar-<?= (int) $s["id_solicitud"] ?>">Lugar de encuentro</label>
                                <input
                                    id="lugar-<?= (int) $s["id_solicitud"] ?>"
                                    name="lugar"
                                    placeholder="Lugar de encuentro"
                                    value="<?= e($s["lugar_encuentro"] ?? "") ?>"
                                >
                            </div>
                            <div>
                                <label for="fecha-<?= (int) $s["id_solicitud"] ?>">Fecha</label>
                                <input
                                    type="date"
                                    id="fecha-<?= (int) $s["id_solicitud"] ?>"
                                    name="fecha"
                                    value="<?= e($s["fecha_encuentro"] ?? "") ?>"
                                >
                            </div>
                            <div>
                                <label for="hora-<?= (int) $s["id_solicitud"] ?>">Hora</label>
                                <input
                                    type="time"
                                    id="hora-<?= (int) $s["id_solicitud"] ?>"
                                    name="hora"
                                    value="<?= e($s["hora_encuentro"] ?? "") ?>"
                                >
                            </div>
                        </div>

                        <button type="submit">Guardar negociación</button>
                    </form>
                <?php endif; ?>

                <?php if (
                    (int) $s["id_propietario"] === $idUsuario
                    && $estadoActual === "pendiente"
                ): ?>
                    <div class="solicitud-decision">
                        <p><?= $esTruequeSolicitud ? "¿No te convence ninguno? Puedes rechazar la propuesta." : "¿Qué deseas hacer con esta solicitud?" ?></p>
                        <div class="acciones-fila">
                            <?php if (!$esTruequeSolicitud): ?>
                            <form action="gestionar_solicitud.php" method="post" class="form-inline">
                                <input type="hidden" name="id_solicitud" value="<?= (int) $s["id_solicitud"] ?>">
                                <button type="submit" name="accion" value="aceptada">Aceptar</button>
                            </form>
                            <?php endif; ?>
                            <form action="gestionar_solicitud.php" method="post" class="form-inline">
                                <input type="hidden" name="id_solicitud" value="<?= (int) $s["id_solicitud"] ?>">
                                <button type="submit" name="accion" value="rechazada" class="secundario">Rechazar</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . "/includes/footer.php"; ?>
