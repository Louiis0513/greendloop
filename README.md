# Green Loop

Plataforma de **intercambio y regalo** de objetos usados (Liceo El Gran Virrey · ficha 3260309).

## URL local

- Inicio: http://localhost/Green-Loop-php/
- Catálogo: http://localhost/Green-Loop-php/catalogo.php
- Login: http://localhost/Green-Loop-php/login.php
- Publicar: http://localhost/Green-Loop-php/publicar.php
- Mis publicaciones: http://localhost/Green-Loop-php/mis_publicaciones.php
- Solicitudes / negociaciones: http://localhost/Green-Loop-php/mis_solicitudes.php

## Credenciales demo

| Correo | Contraseña | Rol en el seed |
|---|---|---|
| `admin@demo.com` | `Admin123!` | María López (publica + recibe/envía) |
| `carlos@demo.com` | `Admin123!` | Carlos Ruiz |
| `ana@demo.com` | `Admin123!` | Ana Torres |
| `diego@demo.com` | `Admin123!` | Diego Vargas |

## Importar base de datos

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root < /Applications/XAMPP/xamppfiles/htdocs/Green-Loop-php/bd/green_loop.sql
```

O importa `bd/green_loop.sql` desde phpMyAdmin.

## Flujo UX (presentación)

1. **Publicar** → elegir **Regalo** o **Trueque** con el selector de tarjetas
2. **Catálogo** → filtrar por tipo/categoría
   - Regalo → solicitar desde el modal (solo mensaje)
   - Trueque → **Proponer trueque**: página en 3 pasos donde eliges
     de 1 a 5 objetos tuyos para ofrecer
3. **Mis solicitudes** → pestañas Recibidas / Enviadas / En negociación
   - En un trueque recibido, el propietario **elige 1** de los objetos
     ofrecidos y acepta con ese; ambos objetos salen del catálogo
   - En un regalo, **Aceptar** o **Rechazar** como siempre
4. Guardar lugar/fecha/hora del encuentro
5. **Mis publicaciones** → editar, disponibilidad, eliminar

> BD ya importada: ejecutar una vez `bd/migracion_trueque.sql`
> (crea `solicitud_ofrecimientos` sin borrar datos).
> Identidad visual: ver `DESIGN.md`.

## Cache (hosting compartido)

Archivos bajo `cache/` (TTL 4 h + parche write-through). Se actualiza al publicar/editar/eliminar, cambiar disponibilidad o aceptar/cancelar solicitudes. Búsqueda por texto no se cachea. Estáticos con `.htaccess`.

## Stack

PHP + mysqli + CSS propio (dark verde) + sesiones. Fotos en `img_productos/`. Sin Redis.
