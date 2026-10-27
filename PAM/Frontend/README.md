# Frontend · PAM (Programa del Adulto Mayor · MINTRAB)

Vue 3 + Vite + Pinia + Vue Router + Axios. El diseño (colores, tipografías, logos, mapa, animaciones) es el de `Diseno/frontend`, portado sin cambios visuales; las fuentes y los logos están en `src/assets`.

## Puesta en marcha

```sh
cp .env.example .env     # y ajustar VITE_API_URL (URL de Backend/api/v1)
npm install
npm run dev              # http://localhost:5173
npm run build
```

Para que la cookie de renovación de sesión viaje, el origen del frontend debe estar en `CORS_ORIGINS` de `Backend/.env` (por defecto `http://localhost:5173`).

## Estructura

| Carpeta | Contenido |
|---|---|
| `src/assets` | `css/app.css` (tokens y componentes del diseño), fuentes, logos y `data/geo.json` (mapa) |
| `src/router` | Rutas con guard por sesión y permisos |
| `src/stores` | Pinia: `auth`, `catalogos`, `tablero` (cubo y agregados), `filtros`, `ui`, `expediente` |
| `src/services` | Axios (`api.js` con renovación automática del token) y un servicio por dominio |
| `src/utils` | Cálculo del cubo (`cubo.js`), formato, reglas de alerta, consulta en lenguaje natural |
| `src/components/layout` | Barra lateral, barra superior, filtros, consulta «Preguntar» y `MainLayout` |
| `src/components/dashboard` | KPIs, mapa, flujo, evolución, tiempos, tablas y demás tarjetas |
| `src/components/expediente` | Expediente 360° y formularios de gestión |
| `src/components/admin` | Pestañas de Administración |
| `src/views` | `auth/Login.vue` y `admin/*` (una vista por ruta) |

## Sesión

El token de acceso vive solo en memoria; la renovación usa la cookie httpOnly `pam_rt` que fija el backend. Al recargar la página se restaura la sesión con esa cookie.
