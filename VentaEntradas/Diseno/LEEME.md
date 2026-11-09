# SANAFER 2026 — Proyecto descargable

Esta es la versión azul y negro con el logo de la feria, las 90 mesas y el flujo de reservas.

## Empieza por aquí

El proyecto usa React 19, TypeScript, CSS, Vinext/Vite y una API con Cloudflare D1 (SQLite).
No es una página PHP ni un HTML que se pueda abrir con doble clic. Puedes reutilizar el diseño y migrar la API a tu backend.

## Archivos que vas a modificar

| Archivo | Contenido |
| --- | --- |
| `app/page.tsx` | Página completa: encabezado, mapa SVG, selección, reservas y ventanas. |
| `app/globals.css` | Estilos y tamaños adaptables. El bloque final “SANAFER 2026 — midnight blue identity” aplica el tema azul y negro. |
| `lib/tables.ts` | Ubicación y categoría de las 90 mesas; precios en quetzales. |
| `public/sanafer-2026.png` | Logo original que compartiste. |
| `app/layout.tsx` | Título, descripción, idioma e icono del sitio. |
| `app/api/reservations/route.ts` | API que consulta, crea y cancela reservas. |
| `db/schema.ts` | Esquema de la tabla de reservas. |
| `drizzle/0000_burly_madelyne_pryor.sql` | SQL inicial para crear la tabla. |
| `package.json` y `pnpm-lock.yaml` | Dependencias y versiones del proyecto. |
| `README_BASE.md` | Documentación técnica del entorno original. |

Las carpetas `components`, `hooks` y algunos archivos auxiliares pertenecen a la base del proyecto; la pantalla principal está en `app/page.tsx`.

## Ejecutarlo en tu computadora

Necesitas Node.js 22.13 o superior y pnpm 11.25.0, versión indicada en `package.json`.
Desde una terminal dentro de la carpeta descomprimida:

```bash
pnpm install --frozen-lockfile
pnpm build
```

El primer build genera la configuración local de la base de datos. Después, ejecuta UNA VEZ para inicializar una base nueva:

```bash
node --import ./scripts/sites-env.mjs ./node_modules/wrangler/bin/wrangler.js d1 execute DB --local --config dist/server/wrangler.json --persist-to .wrangler/state --file drizzle/0000_burly_madelyne_pryor.sql
```

Para iniciar el servidor de desarrollo:

```bash
pnpm dev
```

Abre la dirección que aparezca en la terminal; el entorno portátil usa el puerto 5173 por defecto. La base local empieza vacía y es independiente de la página publicada.

Para probar la compilación, después de `pnpm build`, puedes ejecutar `pnpm start` y abrir la URL que indique.

## Adaptarlo a tu formato

Puedes llevar los estilos de `app/globals.css`, el logo y el mapa de `app/page.tsx` a tu proyecto. La sintaxis TSX necesita adaptarse si usas PHP/HTML, Vue u otro framework; copiar el TSX directamente en un archivo PHP no lo convierte.

La pantalla consume `/api/reservations`. Puedes conservar esta ruta y la siguiente estructura JSON en tu propio backend, o cambiar las llamadas `fetch` de `app/page.tsx`:

### GET /api/reservations

Devuelve un arreglo con una fila por mesa ocupada:

```json
[
  {
    "tableId": 12,
    "bookingId": "UUID-DE-LA-RESERVA",
    "name": "Nombre de ejemplo",
    "createdAt": "2026-11-09T15:00:00.000Z"
  }
]
```

### POST /api/reservations

Solicitud:

```json
{"tableIds": [12, 13], "name": "Nombre de la reserva"}
```

Éxito: HTTP 201 con `bookingId`, `tableIds` y `createdAt`. Conflicto de mesa ocupada: HTTP 409 con `error`. Datos inválidos: HTTP 400 con `error`.

### DELETE /api/reservations

Solicitud:

```json
{"bookingId": "UUID-DE-LA-RESERVA"}
```

Éxito: HTTP 200 con `{"ok": true}`. Cancela todas las mesas del mismo `bookingId`.

Si cambias a MySQL, adapta el SQL y el acceso a datos. Mantén `table_id` como clave única y crea todas las mesas de una reserva en una transacción para evitar reservas duplicadas o incompletas.

## Alcance de esta versión

- Cada mesa se reserva completa con 8 entradas.
- Platino Q1,600; VIP Q1,400; Preferencia Q1,200.
- La página vuelve a consultar la disponibilidad cada 15 segundos.
- Las reservas confirmadas se muestran como ocupadas.
- No hay pasarela de pago ni comprobación de cobros.
- La aplicación no implementa roles propios: la vista de Reservas permite ver y cancelar las reservas a quien acceda. Al integrarla en un sistema con público y administradores, conecta esas operaciones a tus permisos del servidor.
- El ZIP contiene código y recursos, no las reservas de la base publicada, credenciales ni `node_modules`.
- Se retiró de la copia el identificador del sitio publicado. Esta descarga no modifica la página en línea.

## Referencia de la entrega

Código de origen: d02d47c6611658e159c9d6085037a02fa0f136b8.
Los archivos funcionales y visuales coinciden con la versión publicada. Solo se adaptaron la documentación, el nombre del paquete y el comando auxiliar `install:ci` para la copia descargable; se eliminó la vinculación al sitio publicado.


## Nueva pantalla de inicio de sesión

Abre `/login` o usa el botón «Iniciar sesión» en el encabezado del mapa.

Archivos nuevos:

- `app/login/page.tsx`: ruta y título de la pantalla.
- `app/login/login-screen.tsx`: formulario e interacciones.
- `app/login/login.css`: diseño adaptable azul y negro.

El formulario incluye usuario o correo, contraseña, botón para mostrar u ocultar la contraseña, validación de campos obligatorios, orientación para recuperar el acceso y enlaces al mapa.

Es una pantalla de demostración: no autentica usuarios, no crea sesiones, no envía correos de recuperación ni guarda o envía contraseñas. Al pulsar «Iniciar sesión» con ambos campos llenos, informa que el acceso todavía no está habilitado. No protege la página de reservas.

### Conexión a tu sistema de usuarios

En `app/login/login-screen.tsx`, sustituye `handleSubmit` por la llamada a tu API de autenticación. El formulario usa los nombres `username` y `password`. Muestra el error del servidor en `notice`; redirige al mapa únicamente cuando el servidor confirme la sesión. Aplica los permisos en las rutas y API del servidor, no solo ocultando enlaces del frontend. Cuando la autenticación esté implementada, retira la nota «Vista previa» y conecta la recuperación de contraseña a tu flujo real.
