# Backend · TestBases

API PHP 8+ sin framework (`api/v1/index.php` + autoload PSR-4 `App\` → `src/`). Capas: **Controllers** (HTTP) → **Services** (reglas) → **Repositories** (SQL con PDO preparado).

## Primer arranque (local)

1. Crear la base de datos con los archivos de `../Database/` (ver su README).
2. `cp .env.example .env` y completar `DB_*` y `JWT_SECRET` (`php -r "echo bin2hex(random_bytes(32));"`, mínimo 32 caracteres).
3. Servir `Backend/` con Apache/XAMPP. La API queda en `http://localhost/TestBases/Backend/api/v1`.

## Producción (m.nubix.gt/TestBases)

El push a `main` despliega solo (`.github/workflows/deploy.yml`). **Lo único manual**, una vez:

- Crear `Backend/.env` en el servidor (no viaja en el deploy ni se borra con él). Usar `ENTORNO=produccion` y un `JWT_SECRET` propio.
- Ejecutar los SQL de `Database/` en la base de datos del servidor.
- `Backend/uploads/` (imágenes adjuntas) se crea sola al subir la primera imagen; tampoco se toca en el deploy. Conviene incluirla en los respaldos del hosting.
- `upload_max_filesize` y `post_max_size` de PHP deben ser ≥ 5 MB (el frontend ya reduce las fotos a ~1 MB, pero conviene el margen).

## Endpoints (`/api/v1`)

| Método | Ruta | Rol | Descripción |
|--------|------|-----|-------------|
| POST | `/auth/login` | público | `{usuario, clave}` → token JWT + usuario |
| GET | `/auth/me` | admin, tecnico | Usuario de la sesión vigente |
| GET | `/catalogos` | admin, tecnico | Comunidades y comunidades base (para filtros) |
| GET | `/beneficiarios` | admin, tecnico | Lista paginada. Query: `q`, `comunidad_id`, `comunidad_base_id` (`0` = sin base), `estado`, `resaltado`, `pagina`, `por_pagina` (máx. 100) |
| GET | `/beneficiarios/resumen` | admin, tecnico | Totales: registros, con comentario, con imagen |
| POST | `/beneficiarios/{id}/seguimiento` | admin, tecnico | `multipart/form-data`: `comentario` (máx. 2000) e `imagen` (JPG/PNG/WebP, máx. 5 MB) |
| GET | `/beneficiarios/{id}/imagen` | admin, tecnico | Archivo de la imagen (requiere token) |
| DELETE | `/beneficiarios/{id}/seguimiento/imagen` | **admin** | Elimina la imagen adjunta |

Respuestas: `{status: "success"|"error", message, data}`. Errores esperados llevan su código HTTP (401, 403, 404, 413, 415, 422, 429).

## Seguridad

- Contraseñas con `password_hash` (bcrypt). 5 intentos fallidos bloquean la cuenta 15 min (`LOGIN_MAX_INTENTOS`, `LOGIN_BLOQUEO_MIN`).
- Token JWT HS256 firmado con `JWT_SECRET` (nunca en el código), vence a las 8 h (`ACCESS_TTL_MIN`).
- Las imágenes no se sirven por URL directa: `uploads/` está bloqueada y solo se entregan por `/beneficiarios/{id}/imagen` con token. El tipo de archivo se verifica por contenido, y se guarda con nombre aleatorio.
- `src/`, `config/`, `uploads/` y los archivos ocultos (`.env`) están bloqueados en `.htaccess`.
