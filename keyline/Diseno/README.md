# KeylineGT · Sistema de registro de parcelas Keyline

Sistema web (backend + frontend) para el registro y monitoreo de parcelas keyline en Guatemala, con
roles de **técnico de campo**, **supervisor regional** y **administrador**, captura de ubicación GPS
y fotografías, flujo de validación técnica, y un dashboard ejecutivo con mapa nacional, indicadores
y gráficos.

## 1. Requisitos

- Node.js 18 o superior
- npm

## 2. Instalación local

```bash
cd keyline-sistema
npm install
cp .env.example .env    # y edita las variables si lo deseas
npm start
```

El sistema queda disponible en `http://localhost:4000`.

La primera vez que arranca crea automáticamente 3 usuarios de ejemplo (los verás impresos en la
consola). **Cámbialos o elimínalos antes de usar el sistema en producción**:

| Rol            | Correo                 | Contraseña      |
|----------------|-------------------------|-----------------|
| Administrador  | admin@keyline.gt        | Keyline2026!    |
| Supervisor     | supervisor@keyline.gt   | Supervisor2026! |
| Técnico        | tecnico@keyline.gt      | Tecnico2026!    |

Puedes cambiar el correo/contraseña del administrador inicial definiendo `ADMIN_EMAIL` y
`ADMIN_PASSWORD` en `.env` **antes** del primer arranque (o simplemente edita/crea usuarios desde la
sección "Usuarios y equipo" una vez dentro).

## 3. Cómo está organizado

```
src/
  server.js         Servidor Express, rutas y arranque
  db.js             Almacén de datos en JSON (data/db.json)
  constants.js      Catálogos (departamentos, estados, roles...)
  middleware/auth.js  Autenticación con JWT en cookie httpOnly
  routes/           auth, usuarios, parcelas, dashboard
  utils/seed.js     Creación de usuarios iniciales
public/
  index.html, css/, js/   Frontend (SPA en JavaScript nativo, sin frameworks)
  vendor/           Leaflet y Chart.js empaquetados localmente (no dependen de CDN externo)
uploads/parcelas/   Fotografías subidas por los técnicos (se generan miniaturas automáticas)
data/db.json        Base de datos (se crea sola al arrancar)
```

### ¿Por qué JSON en vez de una base de datos tradicional?

Para que el sistema se pueda desplegar en cualquier lado sin depender de compilar módulos nativos.
Es perfectamente adecuado para equipos pequeños/medianos (cientos a algunos miles de parcelas). Si
el proyecto crece mucho, se puede migrar a PostgreSQL/MySQL sustituyendo `src/db.js` sin tocar las
rutas ni el frontend.

**Importante:** haz respaldos periódicos de `data/db.json` y de la carpeta `uploads/`. Puedes
automatizar un respaldo diario copiando ambas rutas a almacenamiento externo (Google Drive, S3, etc.).

## 4. Roles del sistema

- **Técnico de campo**: solo ve un formulario simple (tipo asistente en 4 pasos) para registrar
  parcelas, capturar GPS desde el celular y subir fotos. Puede ver y editar únicamente las parcelas
  que él mismo registró.
- **Supervisor regional**: ve el panel ejecutivo y la base de parcelas filtrada a su departamento
  asignado (si tiene una región asignada), puede revisar/validar parcelas y dejar comentarios a los
  técnicos, y ver el equipo técnico de su región.
- **Administrador**: acceso total. Ve todas las parcelas y el dashboard nacional completo, gestiona
  usuarios (crear, editar, desactivar, eliminar), revisa/valida parcelas y puede eliminarlas.

## 5. Datos que se capturan por parcela

Todos los campos del formulario original (identificación, ubicación, área, estado del proceso, uso
del suelo, tipo de suelo, pendiente, altitud, agua, riesgo de erosión, profundidad de suelo,
talpetate, encharcamiento, bioindicadores, lluvia anual y su fuente, intervenciones, observaciones),
más los siguientes campos adicionales recomendados:

- Código único autogenerado (`KL-AAAA-00001`)
- Coordenadas GPS capturadas automáticamente desde el navegador/celular (con precisión en metros)
- Fotografías múltiples con miniaturas automáticas
- Tenencia de la tierra, familias beneficiadas, cultivo principal, fuente y sistema de riego
- Especies utilizadas en reforestación
- Fecha de próxima visita de seguimiento
- Consentimiento del productor para el uso de la información
- Técnico responsable y bitácora de auditoría (quién creó/revisó y cuándo)
- Estado de validación técnica (Pendiente de revisión / Validado / Requiere corrección) con
  comentario del supervisor

## 6. Desplegar en un servidor real (para que todo el equipo lo use desde internet)

Cualquier proveedor que corra Node.js sirve. Opciones sencillas y con capa gratuita:

### Opción A: Render.com
1. Sube este proyecto a un repositorio de GitHub.
2. En Render, crea un **Web Service** nuevo apuntando al repo.
3. Build command: `npm install` · Start command: `npm start`.
4. Agrega las variables de entorno de `.env.example` (especialmente `JWT_SECRET`, `ADMIN_EMAIL`,
   `ADMIN_PASSWORD`).
5. **Importante:** agrega un "Persistent Disk" montado en `/opt/render/project/src/data` y otro (o el
   mismo) en `/opt/render/project/src/uploads`, para que los datos y fotos no se borren en cada
   despliegue.

### Opción B: Railway.app / Fly.io / un VPS propio
El proceso es equivalente: `npm install && npm start`, exponiendo el puerto definido en `PORT`
(4000 por defecto), y asegurando almacenamiento persistente para las carpetas `data/` y `uploads/`.

### Detrás de un dominio propio
Se recomienda poner Nginx o Caddy como proxy inverso con HTTPS (Let's Encrypt) delante de la app
Node, y fijar `NODE_ENV=production` para que las cookies de sesión requieran HTTPS.

## 7. Seguridad recomendada antes de producción

- Cambia `JWT_SECRET` por un valor largo y aleatorio propio.
- Cambia la contraseña del administrador inicial.
- Sirve el sitio siempre bajo HTTPS.
- Define una rutina de respaldo periódico de `data/db.json` y `uploads/`.
