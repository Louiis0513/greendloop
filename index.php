<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

$resultado = $mysqli->query(
    "SELECT p.*, u.nombre AS propietario
     FROM productos p
     JOIN usuarios u ON p.id_usuario = u.id_usuario
     WHERE p.disponible = 1
     ORDER BY p.fecha_publicacion DESC
     LIMIT 3"
);
$recientes = $resultado->fetch_all(MYSQLI_ASSOC);

$claseBody = "pagina-inicio";
require __DIR__ . "/includes/header.php";
?>

<div class="inicio">

    <section class="inicio-hero">
        <div class="inicio-hero-texto">
            <p class="inicio-etiqueta">Intercambia · Regala · Reutiliza</p>
            <h1>Dale una segunda vida a lo que ya no usas</h1>
            <p>Esa camisa que ya no te pones o ese libro que ya leíste pueden
            servirle a alguien más. En Green Loop los intercambias o los regalas,
            sin gastar dinero.</p>

            <div class="inicio-acciones">
                <?php if (usuarioLogueado()): ?>
                    <a href="publicar.php" class="inicio-boton">Publicar un objeto</a>
                <?php else: ?>
                    <a href="registro.php" class="inicio-boton">Crear mi cuenta</a>
                <?php endif; ?>
                <a href="catalogo.php" class="inicio-enlace">Ver el catálogo →</a>
            </div>
        </div>

        <img src="img/camisa.jpg" alt="Camisa usada colgada sobre una silla de madera">
    </section>

    <section class="inicio-pasos">
        <h2>Así funciona</h2>
        <div class="inicio-pasos-lista">
            <div class="inicio-paso">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="3"/>
                    <path d="M12 8v8M8 12h8"/>
                </svg>
                <h3>Publica</h3>
                <p>Sube el objeto que ya no usas y elige si lo regalas o lo intercambias.</p>
            </div>
            <div class="inicio-paso">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="M20 20l-4-4"/>
                </svg>
                <h3>Solicita</h3>
                <p>Busca en el catálogo lo que necesitas y envía una solicitud.</p>
            </div>
            <div class="inicio-paso">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="5" width="18" height="16" rx="3"/>
                    <path d="M3 10h18M8 3v4M16 3v4"/>
                </svg>
                <h3>Acuerda</h3>
                <p>Pónganse de acuerdo en el lugar, la fecha y la hora de la entrega.</p>
            </div>
        </div>
    </section>

    <section class="inicio-formas">
        <img src="img/libros.jpg" alt="Libros usados en fila">
        <div>
            <h2>Dos formas de compartir</h2>
            <div class="inicio-forma">
                <h3>Regalo</h3>
                <p>Entregas el objeto sin pedir nada a cambio para que alguien más lo use.</p>
            </div>
            <div class="inicio-forma">
                <h3>Intercambio</h3>
                <p>Cambias tu objeto por otro que necesites.</p>
            </div>
        </div>
    </section>

    <section class="inicio-recientes">
        <div class="inicio-recientes-titulo">
            <h2>Recién publicados</h2>
            <a href="catalogo.php" class="inicio-enlace">Ver todos →</a>
        </div>

        <?php if (count($recientes) === 0): ?>
            <p class="inicio-vacio">Todavía no hay objetos publicados. ¡Sé el primero en compartir algo!</p>
        <?php else: ?>
            <div class="inicio-tarjetas">
            <?php foreach ($recientes as $p): ?>
                <article class="inicio-tarjeta">
                    <p class="inicio-tarjeta-categoria"><?= e($p["categoria"]) ?> · <?= e($p["tipo_publicacion"]) ?></p>
                    <h3><?= e($p["nombre"]) ?></h3>
                    <p><?= e($p["descripcion"]) ?></p>
                    <p class="inicio-tarjeta-autor">Publicado por <?= e($p["propietario"]) ?></p>
                </article>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="inicio-porque">
        <img src="img/planta.jpg" alt="Manos sosteniendo tierra con una planta">
        <div>
            <blockquote>
                Hoy los trueques se hacen entre vecinos y conocidos.
                Green Loop los lleva más allá.
            </blockquote>
            <p>Queremos reducir el consumismo y el desperdicio de objetos.
            Con Green Loop puedes intercambiar con personas fuera de tu círculo
            y encontrar lo que necesitas sin comprar. Aquí no se compra ni se vende:
            solo se intercambia y se regala.</p>
        </div>
    </section>

</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
