# App Emagro

Aplicación móvil Flutter para el sistema de gestión agrícola Emagro.

## 📱 Descripción

Aplicación móvil multiplataforma (Android & iOS) desarrollada con Flutter que se conecta al backend PHP para gestionar de forma eficiente y elegante:

- **Usuarios** (CRUD completo)
- **Clientes** (CRUD completo con validación flexible de NIT)
- **Productos y Precios** (Catálogo digital con control de inventario)
- **Inventario** (Visualización de stock con indicadores de color)
- **Notas de Envío** (Sistema de carrito de compras intuitivo)
- **Sistema de Bonificación** (Ventas sin costo)
- **Validación de Stock** (Previene ventas sin inventario)
- **Registro de Pagos** (Gestión de pagos para facturas a crédito)
- **Generación de PDF** (Notas de envío profesionales listas para impresión)

## 🗂️ Estructura del Proyecto

```
app_emagro/
├── assets/
│   └── images/
│       ├── LogoPrincipal.png            # Logo de EMAGRO
│       └── BannerEmagro2.png           # Banner decorativo de cabecera
│       └── Fondo.jpeg                 # Imagen de Fondo Login
├── lib/
│   ├── main.dart                      # Punto de entrada de la app
│   ├── config/
│   │   └── api_config.dart            # Configuración de URLs del backend
│   ├── data/
│   │   └── guatemala_data.dart        # Datos de departamentos y municipios
│   ├── models/
│   │   ├── usuario.dart               # Modelo de Usuario
│   │   ├── cliente.dart               # Modelo de Cliente
│   │   ├── producto_precio.dart       # Modelo de Producto
│   │   ├── item_carrito.dart          # Modelo de carrito temporal
│   │   └── nota_envio.dart            # Modelo de Nota de Envío
│   ├── services/
│   │   ├── auth_service.dart          # Autenticación
│   │   ├── usuario_service.dart       # Gestión de usuarios
│   │   ├── cliente_service.dart       # Gestión de clientes
│   │   ├── producto_service.dart      # Gestión de productos
│   │   ├── inventario_service.dart    # Gestión de inventario
│   │   ├── nota_envio_service.dart    # Gestión de notas de envío
│   │   └── pdf_service.dart           # Motor de generación de PDFs
│   ├── screens/
│   │   ├── login_screen.dart          # Login moderno
│   │   ├── home_screen.dart           # Dashboard principal (Glassmorphism)
│   │   ├── clientes_screen.dart       # Lista de clientes (Rediseñada)
│   │   ├── cliente_form_screen.dart   # Formulario de clientes
│   │   ├── productos_screen.dart      # Catálogo de productos (Rediseñado)
│   │   ├── producto_form_screen.dart  # Formulario de productos
│   │   ├── nueva_venta_screen.dart    # Carrito de compras (Rediseñado)
│   │   ├── vista_previa_nota_screen.dart # Vista previa "Papel"
│   │   ├── ventas_screen.dart         # Historial de transacciones
│   │   ├── pagos_screen.dart          # Gestión de pagos (Tabs)
│   │   └── registro_pago_form_screen.dart # Formulario de registro de pago
│   ├── widgets/
│   │   └── app_drawer.dart            # Menú lateral personalizado
│   └── pubspec.yaml                   # Dependencias
```

## 🚀 Características y Rediseño 2024

### 1. Experiencia de Usuario (UX) Mejorada

- **Diseño Glassmorphism**: Menús y botones con efectos translúcidos modernos.
- **Navegación Intuitiva**: Banners curvos y transiciones suaves entre pantallas.
- **Feedback Visual**: Indicadores de carga, mensajes de éxito (Snackbars) y confirmaciones claras.
- **Símbolo de Quetzal (Q)**: Toda la aplicación utiliza el símbolo de moneda local para claridad financiera.

### 2. Gestión de Clientes

- **Listado Moderno**: Tarjetas con avatar por iniciales y botones de acción rápida (Llamar/Editar).
- **Validación Inteligente**: NIT flexible que acepta "CF" o alfanuméricos.
- **Geolocalización**: Selector integrado de Departamentos y Municipios de Guatemala.

### 3. Catálogo de Productos

- **Visualización Clara**: Tarjetas con stock en tiempo real e identificación por colores.
- **Formularios Dinámicos**: Creación y edición con campos validados.
- **Buscador Integrado**: Filtra rápidamente por nombre o presentación.

### 4. Inventario e Indicadores

- 🔴 **Rojo**: Sin stock (bloquea ventas)
- 🟠 **Naranja**: Stock bajo (< 10 unidades)
- 🟢 **Verde**: Stock saludable

### 5. Nueva Venta (Carrito)

- **Interfaz Limpia**: Secciones separadas para datos del cliente, producto y resumen.
- **Switch de Bonificación**: Marca productos como regalo (precio cero) fácilmente.
- **Tabla de Detalles**: Visualización clara de cada item agregado.
- **Validación de Stock**: Impide agregar más unidades de las disponibles.

### 6. Vista Previa y PDF

- **Efecto "Hoja de Papel"**: La vista previa simula un documento físico para confirmar datos visualmente.
- **PDF Profesional**: Generación instantánea de notas de envío con:
  - Logotipo oficial
  - Correlativo automático
  - Tabla detallada
  - Firmas de recibido/entregado

### 7. Historial de Transacciones

- **Bitácora Completa**: Historial de todas las notas generadas con filtros por fecha, cliente o vendedor.
- **Regeneración**: Posibilidad de volver a generar/descargar el PDF de una venta pasada.
- **Eliminación Segura**: Opción para eliminar ventas (Solo Administradores), restaurando stock y eliminando pagos automáticamente.

## 📦 Dependencias Clave

```yaml
dependencies:
  flutter:
    sdk: flutter
  intl: ^0.18.1 # Formato de fechas y moneda
  pdf: ^3.10.0 # Motor PDF
  printing: ^5.11.0 # Gestión de impresión/compartir
  path_provider: ^2.1.0
  google_fonts: ^6.1.0 # Tipografías modernas
```

## ⚙️ Configuración

1. **Clonar repositorio**
2. **Configurar API URL**: Editar `lib/config/api_config.dart`.
3. **Instalar dependencias**: `flutter pub get`.
4. **Ejecutar**: `flutter run`.

## 🔐 Roles

- **Administrador**: Acceso total (Usuarios, Clientes, Productos, Ventas, Reportes).
- **Vendedor**: Acceso restringido (Clientes, Ventas, Ver Productos).

## 📄 Licencia

Proyecto privado exclusivo para EMAGRO.
