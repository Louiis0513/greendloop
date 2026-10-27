<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

enviarHeadersCache('publico');

$recientes = obtenerRecientes($mysqli, 6);
$stats = obtenerStats($mysqli);

require __DIR__ . "/includes/header.php";
?>

<div class="inicio">

    <section class="inicio-hero">
        <div class="inicio-hero-fondo" aria-hidden="true">
            <img src="img/camisa.jpg" alt="">
        </div>
        <div class="inicio-hero-contenido">
            <p class="inicio-marca">Green Loop</p>
            <h1>Dale una segunda vida a lo que ya no usas</h1>
            <p>Intercambia o regala objetos con tu comunidad, sin gastar dinero.</p>
            <div class="inicio-acciones">
                <?php if (usuarioLogueado()): ?>
                    <a href="publicar.php" class="boton">Publicar un objeto</a>
                <?php else: ?>
                    <a href="registro.php" class="boton">Crear mi cuenta</a>
                <?php endif; ?>
                <a href="catalogo.php" class="boton secundario">Ver el catálogo</a>
            </div>
        </div>
    </section>

    <div class="inicio-stats" aria-label="Indicadores de la comunidad">
        <div class="inicio-stat">
            <strong><?= (int) ($stats["objetos"] ?? 0) ?></strong>
            <span>Objetos disponibles</span>
        </div>
        <div class="inicio-stat">
            <strong><?= (int) ($stats["vecinos"] ?? 0) ?></strong>
            <span>Vecinos activos</span>
        </div>
        <div class="inicio-stat">
            <strong><?= (int) ($stats["trueques"] ?? 0) ?></strong>
            <span>Solicitudes / trueques</span>
        </div>
    </div>

    <section class="inicio-seccion">
        <h2>Así funciona</h2>
        <p>Tres pasos simples para compartir lo que ya no necesitas.</p>
        <div class="inicio-pasos">
            <div class="inicio-paso">
                <span class="inicio-paso-num">1</span>
                <h3>Publica</h3>
                <p>Sube el objeto que ya no usas y elige si lo regalas o lo intercambias.</p>
            </div>
            <div class="inicio-paso">
                <span class="inicio-paso-num">2</span>
                <h3>Solicita</h3>
                <p>Busca en el catálogo lo que necesitas y envía una solicitud.</p>
            </div>
            <div class="inicio-paso">
                <span class="inicio-paso-num">3</span>
                <h3>Acuerda</h3>
                <p>Pónganse de acuerdo en el lugar, la fecha y la hora de la entrega.</p>
            </div>
        </div>
    </section>

    <section class="inicio-seccion inicio-formas">
        <img src="img/libros.jpg" alt="Libros usados en fila">
        <div>
            <h2>Dos formas de compartir</h2>
            <div class="inicio-forma">
                <h3>Regalo</h3>
                <p>Entregas el objeto sin pedir nada a cambio para que alguien más lo use.</p>
            </div>
            <div class="inicio-forma">
                <h3>Trueque</h3>
                <p>Ofreces hasta cinco objetos tuyos y la otra persona elige uno para hacer el cambio.</p>
            </div>
        </div>
    </section>

    <section class="inicio-seccion">
        <div class="inicio-recientes-titulo">
            <h2>Recién publicados</h2>
            <a href="catalogo.php">Ver todos →</a>
        </div>

        <?php if (count($recientes) === 0): ?>
            <p class="vacio">Todavía no hay objetos publicados. ¡Sé el primero en compartir algo!</p>
        <?php else: ?>
            <div class="productos">
                <?php foreach ($recientes as $p): ?>
                    <article class="producto">
                        <div class="producto-media<?= empty($p["imagen"]) ? " sin-foto" : "" ?>">
                            <?php if (!empty($p["imagen"])): ?>
                                <img src="<?= e($p["imagen"]) ?>" alt="<?= e($p["nombre"]) ?>" loading="lazy">
                            <?php endif; ?>
                        </div>
                        <div class="producto-cuerpo">
                            <div class="badges">
                                <span class="badge"><?= e($p["categoria"]) ?></span>
                                <span class="badge badge-tipo <?= esTrueque($p["tipo_publicacion"]) ? "es-trueque" : "es-regalo" ?>"><?= e(tipoEtiqueta($p["tipo_publicacion"])) ?></span>
                            </div>
                            <h3><?= e($p["nombre"]) ?></h3>
                            <p class="producto-desc"><?= e($p["descripcion"]) ?></p>
                            <p class="producto-autor">Publicado por <?= e($p["propietario"]) ?></p>
                            <a href="catalogo.php" class="boton secundario">Ver en catálogo</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="inicio-seccion inicio-cita">
        <img src="img/planta.jpg" alt="Manos sosteniendo tierra con una planta">
        <div>
            <blockquote>
                Hoy los trueques se hacen entre vecinos y conocidos.
                Green Loop los lleva más allá.
            </blockquote>
            <p>Queremos reducir el consumismo y el desperdicio de objetos.
            Aquí no se compra ni se vende: solo se intercambia y se regala.</p>
        </div>
    </section>

</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
