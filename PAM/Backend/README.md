# Backend · PAM (API PHP)

PHP 8.1+ sin framework, MySQL (PDO), JWT propio. Entrada: `api/v1/index.php`. Capas: **Controller → Service → Repository → Entity/DTO**; las rutas y permisos se declaran con atributos (`#[Route]`, `#[Authorize]`, `#[HasPrivilege]`).

## Puesta en marcha

1. Crear la base: ver `../Database/README.md` (`schema.sql` y `seed.sql`).
2. Copiar `.env.example` a `.env` y completar la conexión y `JWT_SECRET`
   (`php -r "echo bin2hex(random_bytes(32));"`).
3. Crear el primer administrador: `php bin/crear_admin.php` (muestra una clave temporal).
4. Servir con Apache (XAMPP): `mod_rewrite` activo y `AllowOverride All`. La API queda en `http://localhost/<proyecto>/Backend/api/v1`.
5. Comprobar: `GET /salud`.

PHP necesita las extensiones `pdo_mysql`, `mbstring`, `iconv` y `zlib`.

## Seguridad

- Contraseñas con Argon2id; política de 10+ caracteres, mayúscula, minúscula y número.
- Access token JWT (30 min) en memoria del cliente; refresh token (12 h) en cookie httpOnly `SameSite=Strict`, rotado en cada uso y guardado como hash.
- Bloqueo tras 5 intentos fallidos (15 min) y límite de peticiones por minuto en login y búsquedas.
- Cada cambio en tablas de negocio queda en `audit_bitacora` (triggers) con usuario, IP y petición; la bitácora no se puede modificar.
- Cabeceras: `nosniff`, `X-Frame-Options`, `Referrer-Policy`, `Cache-Control: no-store`. CORS solo para los orígenes de `CORS_ORIGINS`.

## Endpoints (`/api/v1`)

| Grupo | Rutas |
|---|---|
| Salud | `GET /salud` |
| Auth | `POST /auth/login`, `/auth/refresh`, `/auth/logout`, `/auth/cambiar-clave`; `GET /auth/yo` |
| Catálogos | `GET /catalogos`, `/catalogos/calidad` |
| Tablero | `GET /tablero/datos` (cubo comprimido con ETag) |
| Expedientes | `GET /expedientes`, `/expedientes/{id}`, `/expedientes/{id}/dpi`; `POST /expedientes`, `/{id}/observaciones`, `/visitas`, `/resoluciones`, `/estado`, `/nomina`, `/casos`; `PATCH /{id}/contacto` |
| Reportes | `GET /reportes`; `POST /reportes/exportar` (CSV, con motivo, auditado) |
| Importaciones | `POST /importaciones` (CSV), `GET /importaciones`, `/{lote}`, `/{lote}/errores.csv`; `POST /{lote}/cancelar` |
| Administración | `/admin/usuarios`, `/admin/roles`, `/admin/roles/{id}/permisos`, `/admin/bitacora`, `/admin/parametros`, `/admin/tipos-resolucion` |

Los permisos de cada ruta son los del diseño original (21 permisos, 7 roles; ver `Database/seed.sql`).

## Línea de comandos (`bin/`)

| Comando | Uso |
|---|---|
| `php bin/crear_admin.php [--usuario=admin] [--clave=...]` | Crea o restablece un administrador |
| `php bin/importar.php "BD PAM.csv" [--validar] [--usuario=admin]` | Importa la matriz sin pasar por el navegador |
| `php bin/importar.php --worker` | Procesa los lotes PENDIENTES (archivos mayores a `IMPORT_INLINE_MB`) |
| `php bin/refrescar.php [--forzar]` | Recalcula los agregados del tablero; programarlo en cron cada pocos minutos |

## Importación

Los archivos CSV de hasta `IMPORT_INLINE_MB` se procesan al subirlos (la pantalla muestra el avance). Los mayores quedan PENDIENTES hasta ejecutar `php bin/importar.php --worker`. Si el servidor rechaza archivos grandes, subir `upload_max_filesize` y `post_max_size` en `php.ini`. Excel se guarda como CSV (UTF-8 o Windows-1252: se detecta solo).
