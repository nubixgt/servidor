import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../models/producto_precio.dart';

class ProductoService {
  /// Listar productos únicos
  Future<Map<String, dynamic>> listarProductos() async {
    try {
      final response = await http
          .get(Uri.parse(ApiConfig.listarProductosUrl))
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        List<String> productos = (data['data'] as List)
            .map((producto) => producto.toString())
            .toList();

        return {
          'success': true,
          'message': data['message'],
          'productos': productos,
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al obtener productos',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  /// Obtener presentaciones y precios de un producto
  Future<Map<String, dynamic>> obtenerPresentaciones(String producto) async {
    try {
      final url = '${ApiConfig.obtenerPresentacionesUrl}?producto=${Uri.encodeComponent(producto)}';
      
      final response = await http
          .get(Uri.parse(url))
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        List<PresentacionPrecio> presentaciones = (data['data'] as List)
            .map((json) => PresentacionPrecio.fromJson(json))
            .toList();

        return {
          'success': true,
          'message': data['message'],
          'presentaciones': presentaciones,
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al obtener presentaciones',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  /// Crear producto-precio
  Future<Map<String, dynamic>> crearProducto({
    required String producto,
    required String presentacion,
    required double precio,
    required int cantidad,
  }) async {
    try {
      final body = {
        'producto': producto,
        'presentacion': presentacion,
        'precio': precio,
        'cantidad': cantidad,
      };

      final response = await http
          .post(
            Uri.parse(ApiConfig.crearProductoUrl),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode(body),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 201 && data['success'] == true) {
        return {
          'success': true,
          'message': data['message'] ?? 'Producto creado exitosamente',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al crear producto',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  /// Actualizar producto-precio
  Future<Map<String, dynamic>> actualizarProducto({
    required int id,
    required String producto,
    required String presentacion,
    required double precio,
    required int cantidad,
  }) async {
    try {
      final body = {
        'id': id,
        'producto': producto,
        'presentacion': presentacion,
        'precio': precio,
        'cantidad': cantidad,
      };

      final response = await http
          .put(
            Uri.parse(ApiConfig.actualizarProductoUrl),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode(body),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'message': data['message'] ?? 'Producto actualizado exitosamente',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al actualizar producto',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  /// Eliminar producto-precio
  Future<Map<String, dynamic>> eliminarProducto(int id) async {
    try {
      final response = await http
          .delete(
            Uri.parse(ApiConfig.eliminarProductoUrl),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({'id': id}),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'message': data['message'] ?? 'Producto eliminado exitosamente',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al eliminar producto',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  /// Listar todos los productos-precios
  Future<Map<String, dynamic>> listarTodosProductosPrecios() async {
    try {
      final response = await http
          .get(Uri.parse(ApiConfig.listarTodosProductosUrl))
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        List<ProductoPrecio> productos = (data['data'] as List)
            .map((json) => ProductoPrecio.fromJson(json))
            .toList();

        return {
          'success': true,
          'message': data['message'],
          'data': productos,
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al obtener productos',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }
}
