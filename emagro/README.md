# Proyecto Emagro

Sistema de gestión agrícola completo con backend PHP y aplicación móvil Flutter.

## 📁 Estructura del Proyecto

```
Emagro/
├── backend_movil/          # Backend API REST en PHP
│   ├── api/               # Endpoints de la API
│   ├── config/            # Configuración de BD y CORS
│   └── README.md          # Documentación del backend
│
└── app_emagro/            # Aplicación móvil Flutter
    ├── lib/               # Código fuente de la app
    ├── android/           # Configuración Android
    ├── ios/               # Configuración iOS
    └── README.md          # Documentación de la app
```

## 🚀 Inicio Rápido

### Backend (PHP)

1. Coloca la carpeta `backend_movil` en tu servidor web (XAMPP, WAMP, etc.)
2. Configura las credenciales de la base de datos en `backend_movil/config/database.php`
3. Crea la tabla de usuarios en MySQL (ver `backend_movil/README.md`)

### App Móvil (Flutter)

1. Abre el proyecto `app_emagro` en tu editor
2. Configura la URL del backend en `lib/config/api_config.dart`
3. Ejecuta `flutter pub get`
4. Ejecuta `flutter run`

## 📚 Documentación

- [Backend API](./backend_movil/README.md)
- [App Móvil](./app_emagro/README.md)

## 🔐 Credenciales de Prueba

```
Usuario: admin
Contraseña: password
```

## 🛠️ Tecnologías

- **Backend**: PHP 7.4+, MySQL 5.7+
- **App Móvil**: Flutter 3.0+, Dart
- **Autenticación**: Bcrypt para contraseñas
- **API**: REST con JSON

## 👨‍💻 Autor

Desarrollado para Emagro

---

**Última actualización:** 20 de enero de 2026
