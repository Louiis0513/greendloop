# Green Loop — sistema de identidad (refinado, no reemplazado)

Comunidad escolar de regalo y trueque de objetos usados. La identidad
actual se conserva: verde bosque, lima suave, tipografía Outfit y el
logo de `img/Logo.jpeg`. Este documento fija cómo se usa para que cada
pantalla nueva nazca del mismo sistema.

## Idea de marca

- **Metáfora:** el ciclo que se cierra — lo que una persona ya no usa
  vuelve a circular y le sirve a otra. De ahí el nombre y el ⇄ del trueque.
- **Frase guía:** "Lo que ya no usas, a alguien le falta."
- **Tono:** directo, de barrio y de colegio. Frases cortas y concretas.
  Nada de eslóganes corporativos ni palabras vacías ("eleva", "sin fricción").

## Color (tokens en `css/estilos.css`)

| Token | Valor | Uso |
|---|---|---|
| `--gl-bg` | `#0a0e0c` | Fondo casi neutro, apenas verdoso |
| `--gl-surface-solid` | `#161c18` | Superficies neutras |
| `--gl-accent` | `#2f9e63` | Solo acción principal, activo y seleccionado |
| `--gl-accent-bright` | `#5fd98e` | Solo foco y seleccionado |
| `--gl-text` | `#eef3f0` | Texto y titulares |
| `--gl-muted` | `#99a49c` | Texto secundario neutro |
| Terracota | `#d4895a` | Solo para **Trueque** y avisos |

Regla 90/10: el fondo, los bordes y el texto son neutros; el verde se
reserva para la única acción principal de cada vista. Nada de
degradados lima en botones: verde sólido con texto blanco.

Regla de tipos: **Regalo = lima**, **Trueque = terracota**. Nunca usar
los dos acentos mezclados en el mismo componente.

## Prohibido (huella de "hecho por IA")

- Degradados morado/azul, blobs, iconos flotantes, glow neón.
- Tarjetas idénticas en serie sin jerarquía ni fotos reales.
- Copy genérico de startup. Siempre objetos, personas y lugares concretos.

## Tipografía

Outfit (400/500/600/700) ya cargada en `includes/header.php`.
Titulares con tracking negativo, cuerpo máximo ~54ch, números de
contadores en la misma fuente sin adornos.

## Componentes del flujo

- Selector de tipo al publicar: dos tarjetas (Regalo / Trueque) con
  radio real, `:has(:checked)` para el estado y ayuda de una línea.
- Trueque en 3 pasos: lo que quieres → lo que ofreces (1 a 5 tarjetas
  seleccionables) → mensaje. Botón de envío fijo abajo en móvil.
- Navbar: enlaces de texto con subrayado en el activo; `Publicar` es el
  único botón sólido; la cuenta vive en un avatar con menú desplegable
  (perfil, publicaciones, solicitudes con contador de pendientes, salir).
- En Mis solicitudes, cada tarjeta nace colapsada: foto, estado,
  contraparte y "Siguiente paso". Los formularios se abren solo con su
  acción — en pendiente se decide (elegir/aceptar/rechazar); en aceptada
  se acuerda el encuentro. El propietario elige con radios sobre las
  tarjetas ofrecidas; el elegido queda marcado "Elegido" y el resumen
  muestra `objeto ⇄ objeto`. "En negociación" = solo aceptadas.

## Responsive

Breakpoints existentes: 900 / 760 / 560 / 380px. Verificar siempre en
360, 390, 768, 1024 y 1440px: sin scroll horizontal, objetivos táctiles
de 44px (`--gl-tap`), modal como hoja inferior en móvil y grillas que
colapsan a 2 columnas (ofrecidos) o 1 (formularios).
