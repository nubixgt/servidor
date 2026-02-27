# App UBA - Aplicación Móvil

Aplicación móvil para la gestión de denuncias de maltrato animal en Guatemala. Desarrollada en Flutter/Dart para Android e iOS.

## 🚀 Tecnologías

- Flutter 3.9.2+
- Dart 3.9.2+
- Google Maps Flutter
- Image Picker
- File Picker
- HTTP Client
- URL Launcher

## 📱 Características

### Denuncias

- ✅ Formulario multipaso de denuncias
- ✅ Captura de fotos (DPI, fachada, evidencias)
- ✅ Adjuntar archivos (PDF, DOC, XLS, audio, video)
- ✅ Selector de ubicación con Google Maps
- ✅ Catálogos de Guatemala (22 departamentos, 340 municipios)
- ✅ Validación de formularios en tiempo real
- ✅ Envío de denuncias al backend PHP

### Noticias

- ✅ Visualización de noticias desde la base de datos
- ✅ Categorías: Campaña, Rescate, Legislación, Alerta, Evento, Otro
- ✅ Prioridades: Normal, Importante, Urgente
- ✅ Pantalla de detalle con contenido completo
- ✅ Pull-to-refresh para actualizar
- ✅ Imágenes dinámicas o emojis según categoría

### Servicios Autorizados

- ✅ Listado de clínicas y veterinarias autorizadas
- ✅ Buscador en tiempo real
- ✅ Calificación con estrellas (1-5)
- ✅ Sistema de calificación interactivo
- ✅ Botón "Llamar" (abre marcador telefónico)
- ✅ Botón "Ubicación" (abre Google Maps con GPS)
- ✅ Pantalla de detalle completa
- ✅ Pull-to-refresh para actualizar

## 📁 Estructura del Proyecto

```
app_uba/
├── lib/
│   ├── modelos/           # Modelos de datos
│   │   ├── denuncia.dart
│   │   ├── clinica.dart   # Modelo ServicioAutorizado
│   │   └── noticia.dart   # Modelo Noticia
│   ├── pantallas/         # Screens de la app
│   │   ├── pantalla_principal.dart
│   │   ├── pantalla_inicio.dart
│   │   ├── pantalla_denuncias.dart
│   │   ├── pantalla_noticias.dart
│   │   ├── pantalla_detalle_noticia.dart  # ← NUEVO
│   │   ├── pantalla_servicios.dart
│   │   └── pantalla_detalle_servicio.dart # ← NUEVO
│   ├── servicios/         # Servicios y API
│   │   ├── api/
│   │   │   └── cliente.dart  # Cliente API con métodos para noticias y servicios
│   │   └── servicios_multimedia.dart
│   ├── utilidades/        # Utilidades y helpers
│   │   ├── colores.dart
│   │   ├── datos_guatemala.dart
│   │   ├── formateadores_texto.dart
│   │   └── validadores.dart
│   ├── widgets/           # Widgets reutilizables
│   │   ├── campo_texto_validado.dart
│   │   ├── selector_ubicacion.dart
│   │   └── tarjeta_menu.dart
│   └── main.dart          # Punto de entrada
├── android/               # Configuración Android
├── ios/                   # Configuración iOS
└── pubspec.yaml           # Dependencias
```

## 🔧 Instalación

### Prerrequisitos

- Flutter SDK 3.9.2 o superior
- Android Studio / Xcode
- Google Maps API Key

### 1. Clonar el repositorio

```bash
git clone https://github.com/TU_USUARIO/AppUBA.git
cd AppUBA/app_uba
```

### 2. Instalar dependencias

```bash
flutter pub get
```

### 3. Configurar Google Maps API Key

#### Para Android:

Edita `android/app/src/main/AndroidManifest.xml`:

```xml
<application>
    <!-- Agrega tu API Key aquí -->
    <meta-data
        android:name="com.google.android.geo.API_KEY"
        android:value="TU_API_KEY_AQUI"/>
</application>
```

#### Para iOS:

Edita `ios/Runner/AppDelegate.swift`:

```swift
import GoogleMaps

GMSServices.provideAPIKey("TU_API_KEY_AQUI")
```

### 4. Configurar la URL del backend

Edita `lib/servicios/api/cliente.dart`:

```dart
class ClienteAPI {
  static const String baseUrl = 'http://TU_SERVIDOR/AppUBA/backend/api';
}
```

### 5. Ejecutar la aplicación

```bash
# Ver dispositivos disponibles
flutter devices

# Ejecutar en Android
flutter run -d <device_id>

# Ejecutar en iOS
flutter run -d <device_id>
```

## 📦 Compilar APK/IPA

### Android (APK)

```bash
# APK de debug
flutter build apk --debug

# APK de release
flutter build apk --release

# El APK se genera en:
# build/app/outputs/flutter-apk/app-release.apk
```

### Android (App Bundle)

```bash
flutter build appbundle --release
```

### iOS

```bash
flutter build ios --release
```

## 🗺️ Google Maps - Obtener API Key

1. Ve a: https://console.cloud.google.com/
2. Crea un proyecto nuevo
3. Habilita "Maps SDK for Android" y "Maps SDK for iOS"
4. Ve a "Credenciales" → "Crear credenciales" → "Clave de API"
5. Copia la clave y pégala en los archivos de configuración

## 📱 Módulos de la App

### 1. **Denuncias** (Principal)

- Formulario de 4 pasos
- Captura de fotos del DPI (frente y dorso)
- Ubicación en mapa con Google Maps
- Foto de la fachada
- Selección de especie animal y tipos de infracción
- Evidencias (fotos y archivos)
- Declaración legal

### 2. **Noticias**

**Listado:**

- Muestra noticias publicadas desde la base de datos
- Ordenadas por fecha de publicación descendente
- Badge de categoría con colores
- Fecha de publicación
- Imagen o emoji según categoría
- Pull-to-refresh para actualizar

**Detalle:**

- Imagen destacada (240px de alto)
- Badges de categoría y prioridad
- Fecha de publicación
- Título completo
- Descripción corta
- Contenido completo
- Scroll vertical

### 3. **Servicios Autorizados**

**Listado:**

- Clínicas y veterinarias activas
- Buscador en tiempo real (nombre, dirección, servicios)
- Calificación con estrellas
- Imagen del servicio
- Pull-to-refresh para actualizar
- Tap en tarjeta abre detalle

**Detalle:**

- Imagen del servicio (240px de alto)
- Nombre y calificación actual
- Dirección y teléfono
- Servicios ofrecidos
- Botón "Llamar" (abre dialer)
- Botón "Ubicación" (abre Google Maps)
- **Sistema de calificación interactivo:**
  - 5 estrellas grandes (48px)
  - Tap para seleccionar calificación
  - Botón "Enviar Calificación"
  - Actualización en tiempo real
  - Protección contra clicks múltiples

## 🔐 Permisos

### Android

```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
<uses-permission android:name="android.permission.READ_MEDIA_IMAGES" />
<uses-permission android:name="android.permission.CALL_PHONE" />
```

### iOS

```xml
<key>NSCameraUsageDescription</key>
<string>Usaré la cámara para adjuntar evidencias</string>
<key>NSPhotoLibraryUsageDescription</key>
<string>Necesito acceder a tus fotos</string>
<key>NSLocationWhenInUseUsageDescription</key>
<string>Para marcar la ubicación de la denuncia</string>
```

## 🐛 Debugging

```bash
# Ver logs en tiempo real
flutter logs

# Ejecutar con verbose
flutter run -v

# Limpiar cache
flutter clean
flutter pub get
```

## 📊 Validaciones del Formulario

- **Nombre:** Mínimo 3 caracteres
- **DPI:** 13 dígitos (formato: 0000 00000 0000)
- **Edad:** Entre 18 y 120 años
- **Celular:** 8 dígitos (formato: 0000-0000)
- **Correo:** Formato válido de email
- **Fotos DPI:** 2 fotos obligatorias (frente y dorso)
- **Foto Fachada:** 1 foto obligatoria
- **Evidencias:** Mínimo 1 foto o archivo

## 🎨 Colores de la App

```dart
class AppColores {
  static const Color azulPrimario = Color(0xFF1E3A8A);    // Noticias
  static const Color verdePrimario = Color(0xFF10B981);   // Servicios
  static const Color rojoPrimario = Color(0xFFDC2626);    // Denuncias
  static const Color grisClaro = Color(0xFFF3F4F6);
}
```

## 📡 Integración con Backend

### Endpoints utilizados:

1. **Denuncias:**

   - `POST /denuncias.php` - Crear denuncia
   - `POST /uploads.php` - Subir archivos

2. **Noticias:**

   - `GET /noticias.php` - Obtener noticias publicadas

3. **Servicios:**

   - `GET /servicios.php` - Obtener servicios activos
   - `POST /calificar_servicio.php` - Enviar calificación

4. **Catálogos:**
   - `GET /infracciones.php?tipo=departamentos`
   - `GET /infracciones.php?tipo=municipios&departamento=X`
   - `GET /infracciones.php?tipo=tipos_infraccion`
   - `GET /infracciones.php?tipo=especies`

## 📝 Notas Importantes

- La app NO requiere autenticación (es pública)
- Las fotos se comprimen a 90% de calidad
- Tamaño máximo de archivos: 20MB
- Máximo 5 fotos de evidencia
- Máximo 5 archivos adjuntos
- Las calificaciones se calculan automáticamente en el backend
- Pull-to-refresh disponible en noticias y servicios

## 🌐 Red y Conectividad

⚠️ **IMPORTANTE:** En redes corporativas/gubernamentales con firewall, la app puede tener problemas de conexión. Se recomienda:

- Usar datos móviles para pruebas
- Configurar excepciones en el firewall para la URL del backend

## 🆕 Últimas Actualizaciones (Enero 2026)

### Noticias

- ✅ Integración con base de datos
- ✅ Pantalla de detalle completa
- ✅ Pull-to-refresh
- ✅ Manejo de estados de carga y errores

### Servicios

- ✅ Integración con base de datos
- ✅ Buscador funcional
- ✅ Pantalla de detalle completa
- ✅ Sistema de calificación con estrellas
- ✅ Integración con teléfono (url_launcher)
- ✅ Integración con Google Maps
- ✅ Pull-to-refresh

## 👨‍💻 Autor

Desarrollado por Miguel - MAGA (Ministerio de Agricultura, Ganadería y Alimentación)

## 📄 Licencia

Proyecto gubernamental - Todos los derechos reservados

## 🤝 Contribuir

Este es un proyecto gubernamental. Para contribuir, contacta al equipo de desarrollo de MAGA.
