<?php
session_start();

require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

requiereLogin();
enviarHeadersCache('privado');

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
    // "En negociación" es solo lo ya aceptado: falta acordar el encuentro.
    if (strtolower(trim($s["estado"] ?? "")) === "aceptada") {
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
        return $estado === "aceptada";
    }
    return true;
}));

require __DIR__ . "/includes/header.php";
?>

<div class="pagina-titulo">
    <h2>Solicitudes y negociaciones</h2>
    <p>Primero decide las solicitudes pendientes. Cuando estén aceptadas, acuerda el encuentro.</p>
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
            $idSolicitud = (int) $s["id_solicitud"];
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
            $contraparte = $esPropietario ? $s["nombre_solicitante"] : $s["nombre_propietario"];

            $ofrecidosSolicitud = $esTruequeSolicitud
                ? ($ofrecimientosPorSolicitud[$idSolicitud] ?? [])
                : [];
            $elegidoSolicitud = null;
            foreach ($ofrecidosSolicitud as $of) {
                if ((int) $of["elegido"] === 1) {
                    $elegidoSolicitud = $of;
                }
            }
            $puedeElegir = $esPropietario
                && $estadoActual === "pendiente"
                && count($ofrecidosSolicitud) > 0;

            $tieneEncuentro = trim((string) ($s["lugar_encuentro"] ?? "")) !== ""
                || !empty($s["fecha_encuentro"])
                || !empty($s["hora_encuentro"])
                || trim((string) ($s["detalles_negociacion"] ?? "")) !== "";

            $resumenEncuentro = [];
            if (trim((string) ($s["lugar_encuentro"] ?? "")) !== "") {
                $resumenEncuentro[] = $s["lugar_encuentro"];
            }
            if (!empty($s["fecha_encuentro"])) {
                $tsFecha = strtotime((string) $s["fecha_encuentro"]);
                $resumenEncuentro[] = $tsFecha ? date("d/m/Y", $tsFecha) : $s["fecha_encuentro"];
            }
            if (!empty($s["hora_encuentro"])) {
                $resumenEncuentro[] = substr((string) $s["hora_encuentro"], 0, 5);
            }

            // Siguiente paso y acción principal, según estado y rol.
            $pasoTexto = "Esta solicitud ya se cerró.";
            $toggleAbrir = "";
            $mostrarDetalle = false;

            if ($estadoActual === "pendiente" && $esPropietario && $esTruequeSolicitud && $puedeElegir) {
                $pasoTexto = "Te ofrecen " . count($ofrecidosSolicitud)
                    . (count($ofrecidosSolicitud) === 1 ? " objeto" : " objetos")
                    . ". Elige uno para cerrar el trueque.";
                $toggleAbrir = "Elegir objeto";
                $mostrarDetalle = true;
            } elseif ($estadoActual === "pendiente" && $esPropietario && $esTruequeSolicitud) {
                $pasoTexto = "No trae objetos ofrecidos. Puedes rechazarla y pedir que la envíen de nuevo.";
            } elseif ($estadoActual === "pendiente" && $esPropietario) {
                $pasoTexto = "Revisa el mensaje y decide si entregas este objeto.";
            } elseif ($estadoActual === "pendiente") {
                $pasoTexto = "Esperando respuesta de " . $contraparte . ".";
                if (count($ofrecidosSolicitud) > 0) {
                    $toggleAbrir = "Ver objetos ofrecidos";
                    $mostrarDetalle = true;
                }
            } elseif ($estadoActual === "aceptada") {
                $pasoTexto = $tieneEncuentro
                    ? "Encuentro en curso de acuerdo. Edítalo solo si cambia algo."
                    : "Ya está aceptada. Falta acordar lugar, fecha y hora del encuentro.";
                $toggleAbrir = $tieneEncuentro ? "Editar encuentro" : "Acordar encuentro";
                $mostrarDetalle = true;
            }
            ?>
            <article class="solicitud">
                <div class="solicitud-resumen">
                    <?php if (!empty($s["imagen"])): ?>
                        <img class="solicitud-thumb" src="<?= e($s["imagen"]) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <span class="solicitud-thumb solicitud-thumb-vacia" aria-hidden="true">Sin foto</span>
                    <?php endif; ?>

                    <div class="solicitud-resumen-info">
                        <div class="solicitud-cabecera">
                            <h3><?= e($s["objeto"]) ?></h3>
                            <div class="badges">
                                <span class="badge badge-tipo <?= $esTruequeSolicitud ? "es-trueque" : "es-regalo" ?>"><?= e($tipoTexto) ?></span>
                                <span class="badge badge-estado es-<?= e($estadoActual) ?>"><?= e($estadoTexto) ?></span>
                            </div>
                        </div>

                        <div class="solicitud-meta">
                            <span class="solicitud-rol"><?= e($rolTexto) ?></span>
                            <span><?= $esPropietario ? "De " : "Para " ?><?= e($contraparte) ?></span>
                            <?php if (!empty($s["fecha_solicitud"])): ?>
                                <span><?= e(date("d/m/Y", strtotime((string) $s["fecha_solicitud"]))) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (trim((string) ($s["mensaje"] ?? "")) !== ""): ?>
                            <p class="solicitud-mensaje"><?= e($s["mensaje"]) ?></p>
                        <?php endif; ?>

                        <?php if ($elegidoSolicitud && $estadoActual === "aceptada"): ?>
                            <p class="trueque-resumen solicitud-intercambio">
                                <strong><?= e($s["objeto"]) ?></strong>
                                <span aria-hidden="true">⇄</span>
                                <strong><?= e($elegidoSolicitud["nombre"]) ?></strong>
                            </p>
                        <?php endif; ?>

                        <?php if ($estadoActual === "aceptada" && count($resumenEncuentro) > 0): ?>
                            <p class="solicitud-encuentro">Encuentro: <strong><?= e(implode(" · ", $resumenEncuentro)) ?></strong></p>
                        <?php endif; ?>

                        <p class="solicitud-paso"><span>Siguiente paso:</span> <?= e($pasoTexto) ?></p>
                    </div>
                </div>

                <div class="solicitud-acciones">
                    <?php if ($estadoActual === "pendiente" && $esPropietario && !$esTruequeSolicitud): ?>
                        <form action="gestionar_solicitud.php" method="post" class="form-inline">
                            <input type="hidden" name="id_solicitud" value="<?= $idSolicitud ?>">
                            <button type="submit" name="accion" value="aceptada">Aceptar</button>
                        </form>
                        <form action="gestionar_solicitud.php" method="post" class="form-inline">
                            <input type="hidden" name="id_solicitud" value="<?= $idSolicitud ?>">
                            <button type="submit" name="accion" value="rechazada" class="secundario">Rechazar</button>
                        </form>
                    <?php elseif ($estadoActual === "pendiente" && $esPropietario && $esTruequeSolicitud && !$puedeElegir): ?>
                        <form action="gestionar_solicitud.php" method="post" class="form-inline">
                            <input type="hidden" name="id_solicitud" value="<?= $idSolicitud ?>">
                            <button type="submit" name="accion" value="rechazada" class="secundario">Rechazar</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($mostrarDetalle): ?>
                        <button
                            type="button"
                            class="solicitud-toggle <?= $estadoActual === "aceptada" || $puedeElegir ? "" : "secundario" ?>"
                            data-solicitud-toggle
                            aria-expanded="false"
                            aria-controls="detalle-<?= $idSolicitud ?>"
                            data-texto-abrir="<?= e($toggleAbrir) ?>"
                            data-texto-cerrar="Ocultar"
                        ><?= e($toggleAbrir) ?></button>
                    <?php endif; ?>
                </div>

                <?php if ($mostrarDetalle): ?>
                    <div class="solicitud-detalle" id="detalle-<?= $idSolicitud ?>">
                        <?php if ($esTruequeSolicitud): ?>
                            <section class="trueque-ofrecidos" aria-label="Objetos ofrecidos en este trueque">
                                <h4><?= $puedeElegir ? "Objetos que te ofrecen · elige uno" : "Objetos ofrecidos en este trueque" ?></h4>

                                <?php if (count($ofrecidosSolicitud) === 0): ?>
                                    <p class="form-ayuda">Esta solicitud no trae objetos ofrecidos.</p>
                                <?php else: ?>
                                    <?php if ($puedeElegir): ?>
                                        <form action="gestionar_solicitud.php" method="post" class="form-elegir-trueque">
                                            <input type="hidden" name="id_solicitud" value="<?= $idSolicitud ?>">
                                    <?php endif; ?>

                                    <div class="ofrecidos-grid es-mini">
                                        <?php foreach ($ofrecidosSolicitud as $of): ?>
                                            <?php $esElegido = (int) $of["elegido"] === 1; ?>
                                            <?php if ($puedeElegir): ?>
                                                <label class="ofrecido-card es-mini<?= $esElegido ? " es-elegido" : "" ?>">
                                                    <input type="radio" name="id_producto_ofrecido" value="<?= (int) $of["id_producto"] ?>" required>
                                            <?php else: ?>
                                                <div class="ofrecido-card es-mini<?= $esElegido ? " es-elegido" : "" ?>">
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
                                            <?= $puedeElegir ? "</label>" : "</div>" ?>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if ($puedeElegir): ?>
                                            <button type="submit" name="accion" value="aceptar_trueque">Aceptar trueque con el objeto elegido</button>
                                        </form>
                                        <form action="gestionar_solicitud.php" method="post" class="form-inline solicitud-rechazar">
                                            <input type="hidden" name="id_solicitud" value="<?= $idSolicitud ?>">
                                            <button type="submit" name="accion" value="rechazada" class="secundario">Ninguno me convence · Rechazar</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </section>
                        <?php endif; ?>

                        <?php if ($estadoActual === "aceptada"): ?>
                            <p class="contacto-box">
                                <?= $esPropietario ? "Teléfono de quien solicitó:" : "Teléfono del propietario:" ?>
                                <strong><?= e($esPropietario ? ($s["telefono_solicitante"] ?: "No registrado") : ($s["telefono_propietario"] ?: "No registrado")) ?></strong>
                            </p>

                            <form action="negociar.php" method="post" class="form-negociacion">
                                <h4>Detalles del encuentro</h4>
                                <p class="form-ayuda">Acuerda lugar, fecha y hora con la otra persona.</p>
                                <input type="hidden" name="id_solicitud" value="<?= $idSolicitud ?>">

                                <label for="detalles-<?= $idSolicitud ?>">Detalles</label>
                                <textarea
                                    id="detalles-<?= $idSolicitud ?>"
                                    name="detalles"
                                    placeholder="Notas del encuentro (punto de referencia, condiciones, etc.)"
                                ><?= e($s["detalles_negociacion"] ?? "") ?></textarea>

                                <div class="solicitud-grid">
                                    <div>
                                        <label for="lugar-<?= $idSolicitud ?>">Lugar de encuentro</label>
                                        <input id="lugar-<?= $idSolicitud ?>" name="lugar" placeholder="Lugar de encuentro" value="<?= e($s["lugar_encuentro"] ?? "") ?>">
                                    </div>
                                    <div>
                                        <label for="fecha-<?= $idSolicitud ?>">Fecha</label>
                                        <input type="date" id="fecha-<?= $idSolicitud ?>" name="fecha" value="<?= e($s["fecha_encuentro"] ?? "") ?>">
                                    </div>
                                    <div>
                                        <label for="hora-<?= $idSolicitud ?>">Hora</label>
                                        <input type="time" id="hora-<?= $idSolicitud ?>" name="hora" value="<?= e($s["hora_encuentro"] ?? "") ?>">
                                    </div>
                                </div>

                                <button type="submit">Guardar encuentro</button>
                            </form>

                            <form
                                action="gestionar_solicitud.php"
                                method="post"
                                class="form-inline solicitud-cancelar"
                                onsubmit="return confirm('¿Seguro que deseas cancelar esta negociación?');"
                            >
                                <input type="hidden" name="id_solicitud" value="<?= $idSolicitud ?>">
                                <button type="submit" name="accion" value="cancelada" class="peligro">Cancelar negociación</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . "/includes/footer.php"; ?>
