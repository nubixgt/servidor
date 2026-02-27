class ApiConfig {
  // ⚠️ IMPORTANTE: Cambia esta URL por la dirección de tu servidor
  // Ejemplos:
  // - Desarrollo local: 'http://localhost/backend_movil'
  // - XAMPP local: 'http://localhost/Emagro/backend_movil'
  // - Servidor remoto: 'https://tu-dominio.com/backend_movil'
  static const String baseUrl = 'http://159.65.168.91/Emagro/backend_movil';
  
  // Endpoints de autenticación
  static const String loginUrl = '$baseUrl/api/auth/login.php';
  static const String logoutUrl = '$baseUrl/api/auth/logout.php';
  
  // Endpoints de usuarios
  static const String listarUsuariosUrl = '$baseUrl/api/usuarios/listar.php';
  static const String crearUsuarioUrl = '$baseUrl/api/usuarios/crear.php';
  static const String actualizarUsuarioUrl = '$baseUrl/api/usuarios/actualizar.php';
  static const String eliminarUsuarioUrl = '$baseUrl/api/usuarios/eliminar.php';
  
  // Endpoints de clientes
  static const String listarClientesUrl = '$baseUrl/api/clientes/listar.php';
  static const String crearClienteUrl = '$baseUrl/api/clientes/crear.php';
  static const String actualizarClienteUrl = '$baseUrl/api/clientes/actualizar.php';
  static const String eliminarClienteUrl = '$baseUrl/api/clientes/eliminar.php';
  
  // Endpoints de productos
  static const String listarProductosUrl = '$baseUrl/api/productos/listar.php';
  static const String listarTodosProductosUrl = '$baseUrl/api/productos/listar_todos.php';
  static const String obtenerPresentacionesUrl = '$baseUrl/api/productos/obtener_presentaciones.php';
  static const String crearProductoUrl = '$baseUrl/api/productos/crear.php';
  static const String actualizarProductoUrl = '$baseUrl/api/productos/actualizar.php';
  static const String eliminarProductoUrl = '$baseUrl/api/productos/eliminar.php';
  
  // Endpoints de ventas
  static const String listarVentasUrl = '$baseUrl/api/ventas/listar.php';
  static const String crearVentaUrl = '$baseUrl/api/ventas/crear.php';
  
  // Endpoints de notas de envío
  static const String obtenerSiguienteNumeroUrl = '$baseUrl/api/notas_envio/obtener_siguiente_numero.php';
  static const String crearNotaUrl = '$baseUrl/api/notas_envio/crear_nota.php';
  static const String listarNotasUrl = '$baseUrl/api/notas_envio/listar_notas.php';
  static const String eliminarNotaUrl = '$baseUrl/api/notas_envio/eliminar_nota.php';
  
  // Endpoints de inventario
  static const String listarInventarioUrl = '$baseUrl/api/inventario/listar_inventario.php';
  
  // Endpoints de pagos
  static const String listarFacturasCreditoUrl = '$baseUrl/api/pagos/listar_facturas_credito.php';
  static const String crearPagoUrl = '$baseUrl/api/pagos/crear_pago.php';
  static const String listarPagosUrl = '$baseUrl/api/pagos/listar_pagos.php';
  static const String obtenerSaldoFacturaUrl = '$baseUrl/api/pagos/obtener_saldo_factura.php';
  
  // Timeout para las peticiones (en segundos)
  static const int timeoutSeconds = 30;
}
