# Backend Móvil - Emagro

Backend API REST desarrollado en PHP para la aplicación móvil Emagro.

## 📋 Descripción

Este backend proporciona los servicios necesarios para la aplicación móvil Flutter, incluyendo:

- **Autenticación** de usuarios
- **Gestión de Usuarios** (CRUD)
- **Gestión de Clientes** (CRUD)
- **Gestión de Productos y Precios** (CRUD con control de inventario)
- **Gestión de Inventario** (Control de stock por producto)
- **Gestión de Notas de Envío** (Sistema de carrito con múltiples productos)
- **Numeración Correlativa** automática para notas de envío
- **Sistema de Bonificación** (Ventas sin costo)
- **Validación de Stock** automática
- **Registro de Pagos** (Gestión de pagos para facturas a crédito)
- **Gestión de Ventas** (Legacy - sistema anterior)

## 🗂️ Estructura de Carpetas

```
backend_movil/
├── api/
│   ├── auth/
│   │   ├── login.php          # Endpoint de inicio de sesión
│   │   └── logout.php         # Endpoint de cierre de sesión
│   ├── usuarios/
│   │   ├── listar.php         # Listar todos los usuarios
│   │   ├── crear.php          # Crear nuevo usuario
│   │   ├── actualizar.php     # Actualizar usuario existente
│   │   └── eliminar.php       # Eliminar usuario
│   ├── clientes/
│   │   ├── listar.php         # Listar todos los clientes
│   │   ├── crear.php          # Crear nuevo cliente
│   │   ├── actualizar.php     # Actualizar cliente existente
│   │   └── eliminar.php       # Eliminar cliente
│   ├── productos/
│   │   ├── listar.php         # Listar productos únicos
│   │   ├── listar_todos.php   # Listar todos productos-precios
│   │   ├── obtener_presentaciones.php  # Obtener presentaciones por producto
│   │   ├── crear.php          # Crear producto-precio
│   │   ├── actualizar.php     # Actualizar producto-precio
│   │   └── eliminar.php       # Eliminar producto-precio
│   ├── notas_envio/
│   │   ├── obtener_siguiente_numero.php  # Obtener número correlativo
│   │   ├── crear_nota.php     # Crear nota de envío completa
│   │   ├── listar_notas.php   # Listar todas las notas de envío
│   │   └── eliminar_nota.php  # Eliminar nota de envío (restore stock)
│   ├── inventario/
│   │   └── listar_inventario.php  # Listar inventario completo
│   ├── pagos/
│   │   ├── listar_facturas_credito.php  # Listar facturas a crédito
│   │   ├── crear_pago.php     # Crear nuevo pago
│   │   ├── listar_pagos.php   # Listar todos los pagos
│   │   └── obtener_saldo_factura.php  # Obtener saldo de factura
│   └── ventas/
│       ├── listar.php         # Listar todas las ventas (legacy)
│       └── crear.php          # Crear nueva venta (legacy)
├── config/
│   ├── database.php           # Configuración de base de datos
│   └── cors.php               # Configuración de CORS
├── database/
│   ├── crear_tabla_clientes.sql        # Script SQL para tabla clientes
│   ├── crear_tabla_productos_precios.sql  # Script SQL para tabla productos_precios
│   ├── crear_tabla_nota_envio.sql      # Script SQL para tabla nota_envio
│   ├── crear_tabla_detalle_nota_envio.sql # Script SQL para tabla detalle_nota_envio
│   ├── crear_tabla_pagos.sql  # Script SQL para tabla pagos
│   ├── crear_tabla_nueva_venta.sql     # Script SQL para tabla nueva_venta (legacy)
│   └── INSTRUCCIONES.sql      # Instrucciones para crear las tablas
└── README.md                  # Este archivo
```

## 🚀 Características Principales

### 1. Sistema de Notas de Envío

El sistema más reciente que reemplaza al sistema de ventas simple:

- **Múltiples productos por nota**: Una nota puede contener varios productos
- **Numeración correlativa**: Números automáticos (00001, 00002, etc.)
- **Transacciones**: Garantiza integridad de datos
- **Relación maestro-detalle**: `nota_envio` + `detalle_nota_envio`
- **Validación de stock**: Verifica inventario disponible antes de crear nota
- **Descuento automático**: Reduce inventario al crear la venta
- **Soporte de bonificación**: Productos sin costo marcados como bonificación

### 2. Autenticación y Seguridad

- Login con validación de credenciales
- Gestión de sesiones
- CORS configurado para peticiones desde la app móvil

### 3. Gestión de Datos

- **CRUD completo** para todas las entidades
- **Validaciones** en el servidor
- **Respuestas JSON** estandarizadas
- **Manejo de errores** robusto

## 📊 Base de Datos

### Tablas Principales

#### `usuarios`

- Gestión de usuarios del sistema
- Roles: Administrador, Vendedor

#### `clientes`

- Información de clientes
- NIT flexible (permite letras y números)
- Departamento y municipio
- Estado de ventas bloqueadas

#### `productos_precios`

- Productos con múltiples presentaciones y precios
- Relación producto-presentación-precio
- **Campo `cantidad`**: Control de inventario por producto-presentación
- Validación de stock disponible

#### `nota_envio` (Nueva)

- Cabecera de las notas de envío
- Número correlativo automático
- Información del cliente y vendedor
- **Tipo de venta**: Contado, Crédito, Pruebas, **Bonificación**
- Totales calculados

#### `detalle_nota_envio` (Nueva)

- Productos de cada nota de envío
- Cantidad, precio, descuento por producto
- **Campo `es_bonificacion`**: Marca productos bonificados (sin costo)
- Relación con `nota_envio` (FK con CASCADE DELETE)

#### `pagos` (Nueva)

- Registro de pagos para facturas a crédito
- Relación con `nota_envio` mediante `factura_id`
- **Bancos disponibles**: G&T Continental, Industrial, BAC Credomatic, Banrural, Bantrab
- Cálculo automático de saldo pendiente
- Validación de montos contra saldo de factura

#### `nueva_venta` (Legacy)

- Sistema anterior de ventas simples
- Un producto por venta
- Mantenido para compatibilidad

## 🔧 Configuración

### 1. Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Extensiones PHP: PDO, PDO_MySQL

### 2. Configurar Base de Datos

Edita `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('DB_NAME', 'Emagro');
```

### 3. Crear Tablas

Ejecuta los scripts SQL en orden:

```sql
-- 1. Tablas básicas (si no existen)
source database/crear_tabla_clientes.sql;
source database/crear_tabla_productos_precios.sql;

-- 2. Tablas de notas de envío (NUEVO SISTEMA)
source database/crear_tabla_nota_envio.sql;
source database/crear_tabla_detalle_nota_envio.sql;

-- 3. Tabla de ventas legacy (opcional)
source database/crear_tabla_nueva_venta.sql;
```

O sigue las instrucciones en `database/INSTRUCCIONES.sql`

### 4. Configurar CORS

El archivo `config/cors.php` ya está configurado para permitir peticiones desde cualquier origen (desarrollo). En producción, cambia:

```php
header('Access-Control-Allow-Origin: *');
```

por:

```php
header('Access-Control-Allow-Origin: https://tu-dominio.com');
```

## 📡 Endpoints API

### Autenticación

#### POST `/api/auth/login.php`

Login de usuario

**Request:**

```json
{
  "usuario": "admin",
  "password": "password123"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Login exitoso",
  "usuario": {
    "id": 1,
    "nombre": "Administrador",
    "usuario": "admin",
    "rol": "Administrador"
  }
}
```

### Notas de Envío (NUEVO SISTEMA)

#### GET `/api/notas_envio/obtener_siguiente_numero.php`

Obtiene el siguiente número correlativo

**Response:**

```json
{
  "success": true,
  "numero_nota": "00001"
}
```

#### POST `/api/notas_envio/crear_nota.php`

Crea una nota de envío completa con sus productos

**Request:**

```json
{
  "fecha": "2026-01-23",
  "vendedor": "Felipe Machán",
  "cliente_id": 4,
  "nit": "12453625-8",
  "direccion": "Dirección del cliente",
  "tipo_venta": "Contado",
  "dias_credito": null,
  "productos": [
    {
      "producto": "EM SuperAgua",
      "presentacion": "1 litro",
      "precio_unitario": 30.0,
      "cantidad": 1,
      "descuento": 0.0,
      "total": 30.0
    },
    {
      "producto": "EM SuperAnimal",
      "presentacion": "4 litros",
      "precio_unitario": 660.0,
      "cantidad": 1,
      "descuento": 60.0,
      "total": 600.0
    }
  ],
  "subtotal": 690.0,
  "descuento_total": 60.0,
  "total": 630.0,
  "usuario_id": 1
}
```

**Response:**

```json
{
  "success": true,
  "message": "Nota de envío creada exitosamente",
  "numero_nota": "00001",
  "nota_id": 1
}
```

#### GET `/api/notas_envio/listar_notas.php`

Lista todas las notas de envío con sus productos

**Response:**

```json
{
  "success": true,
  "message": "Notas de envío obtenidas correctamente",
  "notas": [
    {
      "id": "1",
      "numero_nota": "00001",
      "fecha": "2026-01-23",
      "vendedor": "Felipe Machán",
      "cliente_id": 4,
      "cliente_nombre": "Prueba 1",
      "nit": "12453625-8",
      "direccion": "Prueba de direccion",
      "tipo_venta": "Contado",
      "dias_credito": null,
      "subtotal": "690.00",
      "descuento_total": "60.00",
      "total": "630.00",
      "usuario_id": 1,
      "fecha_creacion": "2026-01-23 17:40:59",
      "usuario_nombre": "Administrador",
      "productos": [
        {
          "id": 1,
          "nota_envio_id": 1,
          "producto": "EM SuperAgua",
          "presentacion": "1 litro",
          "precio_unitario": "30.00",
          "cantidad": 1,
          "descuento": "0.00",
          "total": "30.00"
        }
      ]
    }
  ],
  "total": 1
}
```

"total": 1
}

````

#### POST `/api/notas_envio/eliminar_nota.php`

Elimina una nota de envío, restaura el inventario y elimina pagos asociados.

**Request:**

```json
{
  "id": 1
}
````

**Response:**

```json
{
  "success": true,
  "message": "Nota eliminada y stock restaurado correctamente"
}
```

### Clientes

#### GET `/api/clientes/listar.php`

Lista todos los clientes

#### POST `/api/clientes/crear.php`

Crea un nuevo cliente

#### PUT `/api/clientes/actualizar.php`

Actualiza un cliente existente

#### DELETE `/api/clientes/eliminar.php`

Elimina un cliente

### Productos

#### GET `/api/productos/listar.php`

Lista productos únicos

#### GET `/api/productos/listar_todos.php`

Lista todos los productos con sus presentaciones y precios

#### GET `/api/productos/obtener_presentaciones.php?producto=EM1`

Obtiene presentaciones y precios de un producto específico

#### POST `/api/productos/crear.php`

Crea un nuevo producto-precio

#### PUT `/api/productos/actualizar.php`

Actualiza un producto-precio

#### DELETE `/api/productos/eliminar.php`

Elimina un producto-precio

### Pagos

#### GET `/api/pagos/listar_facturas_credito.php`

Lista todas las facturas a crédito con saldo pendiente

**Response:**

```json
{
  "success": true,
  "message": "Facturas a crédito obtenidas correctamente",
  "data": [
    {
      "id": 1,
      "numero_nota": "00001",
      "fecha": "2026-01-23",
      "cliente_id": 4,
      "cliente_nombre": "Cliente Ejemplo",
      "nit": "12345678-9",
      "total": "1000.00",
      "dias_credito": 30,
      "total_pagado": "300.00",
      "saldo_pendiente": "700.00"
    }
  ],
  "total": 1
}
```

#### POST `/api/pagos/crear_pago.php`

Crea un nuevo pago para una factura a crédito

**Request:**

```json
{
  "factura_id": 1,
  "fecha_pago": "2026-02-03",
  "banco": "Banco G&T Continental",
  "monto_pago": 300.0,
  "referencia_transaccion": "REF123456",
  "usuario_id": 1
}
```

**Response:**

```json
{
  "success": true,
  "message": "Pago registrado exitosamente",
  "pago_id": 1,
  "numero_factura": "00001",
  "nuevo_saldo": 400.0
}
```

#### GET `/api/pagos/listar_pagos.php`

Lista todos los pagos registrados

**Response:**

```json
{
  "success": true,
  "message": "Pagos obtenidos correctamente",
  "data": [
    {
      "id": 1,
      "factura_id": 1,
      "fecha_pago": "2026-02-03",
      "banco": "Banco G&T Continental",
      "monto_pago": "300.00",
      "referencia_transaccion": "REF123456",
      "fecha_creacion": "2026-02-03 08:00:00",
      "numero_nota": "00001",
      "cliente_nombre": "Cliente Ejemplo",
      "nit": "12345678-9",
      "total_factura": "1000.00",
      "usuario_nombre": "Administrador"
    }
  ],
  "total": 1
}
```

#### GET `/api/pagos/obtener_saldo_factura.php?factura_id=1`

Obtiene el saldo pendiente de una factura específica

**Response:**

```json
{
  "success": true,
  "message": "Saldo obtenido correctamente",
  "data": {
    "factura_id": 1,
    "numero_nota": "00001",
    "total_factura": "1000.00",
    "total_pagado": "300.00",
    "saldo_pendiente": "700.00",
    "tipo_venta": "Crédito"
  }
}
```

## 🔒 Seguridad

### Validaciones Implementadas

- **Validación de entrada**: Todos los datos son validados antes de procesarse
- **Prepared Statements**: Prevención de SQL Injection
- **Transacciones**: Garantizan integridad de datos en operaciones complejas
- **Manejo de errores**: Respuestas consistentes sin exponer detalles internos

### Recomendaciones para Producción

1. **Cambiar credenciales** de base de datos
2. **Configurar CORS** específico para tu dominio
3. **Usar HTTPS** para todas las comunicaciones
4. **Implementar rate limiting** para prevenir abuso
5. **Logs de auditoría** para operaciones críticas

## 🐛 Solución de Problemas

### Error de conexión a base de datos

1. Verifica credenciales en `config/database.php`
2. Asegúrate que MySQL esté corriendo
3. Verifica que la base de datos `Emagro` exista

### Error 500 en endpoints

1. Activa `display_errors` en PHP para desarrollo:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

2. Revisa logs de PHP y MySQL
3. Verifica que todas las tablas existan

### CORS errors

1. Verifica que `cors.php` esté incluido en todos los endpoints
2. Asegúrate que los headers se envíen antes de cualquier output
3. Verifica configuración del servidor web

### Numeración correlativa no funciona

1. Verifica que la tabla `nota_envio` exista
2. Asegúrate que la función `getConnection()` esté en `database.php`
3. Revisa permisos de la base de datos

## 📝 Notas de Desarrollo

### Diferencias entre Sistemas

**Sistema Anterior (nueva_venta):**

- Una venta = un producto
- Tabla simple `nueva_venta`
- Sin numeración correlativa

**Sistema Nuevo (nota_envio):**

- Una nota = múltiples productos
- Tablas relacionadas `nota_envio` + `detalle_nota_envio`
- Numeración correlativa automática
- Transacciones para integridad
- Integración con generación de PDF

### Migración

Si necesitas migrar datos del sistema antiguo al nuevo:

1. Los datos en `nueva_venta` permanecen intactos
2. El nuevo sistema usa tablas separadas
3. Ambos sistemas pueden coexistir
4. Se recomienda usar solo el nuevo sistema para nuevas ventas

## 📄 Licencia

Proyecto privado - EMAGRO

## 👥 Contacto

Para soporte o consultas sobre el backend, contacta al equipo de desarrollo.
