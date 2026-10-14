# Daños a Cultivos · App móvil (Flutter)

Misma lógica y mismos apartados que la web (`Frontend/`), conectada al mismo backend (`Backend/api/v1`).

## Apartados y roles

| Apartado | admin | supervisor | técnico |
|---|---|---|---|
| Dashboard | todos los registros | todos los registros | solo los suyos |
| Crear registro | sí | sí | sí |
| Registros | todos (puede eliminar) | todos (puede eliminar) | solo los suyos |
| Usuarios | sí | no | no |

El filtro por rol de los registros lo hace el backend (`DanoCultivoService::listar`).

## Backend

No requiere cambios: la app usa la misma API REST con JWT (`Authorization: Bearer`).
URL base por defecto: `https://m.nubix.gt/Da%C3%B1osACultivos/Backend`.
Para apuntar a otro servidor sin tocar el código:

```bash
flutter run --dart-define=API_BASE=https://mi-servidor/DañosACultivos/Backend
```

Recuerda ejecutar las migraciones de `Database/` (002 y 003) en el servidor.

## Permisos

- **Android** (`android/app/src/main/AndroidManifest.xml`): INTERNET, ACCESS_NETWORK_STATE,
  ACCESS_FINE_LOCATION, ACCESS_COARSE_LOCATION, CAMERA, READ_MEDIA_IMAGES,
  READ_EXTERNAL_STORAGE (hasta Android 12), WRITE_EXTERNAL_STORAGE (hasta Android 9).
- **iOS** (`ios/Runner/Info.plist`): cámara, fotos (lectura y escritura), ubicación en uso, micrófono.

## Estructura (`lib/`)

- `config/` URL del backend · `theme/` colores, tipografía y diálogos
- `data/` catálogos de cultivos, causas y municipios (mismos que la web)
- `models/` · `services/` (API, sesión, registros, usuarios)
- `widgets/` componentes compartidos (paneles, botones, buscador, barra inferior)
- `screens/` login, dashboard, crear registro (4 pasos), registros, usuarios

## Ejecutar

```bash
flutter pub get
flutter run
```
