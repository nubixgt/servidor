import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../models/producto_precio.dart';

class InventarioService {
  /// Listar inventario completo
  Future<Map<String, dynamic>> listarInventario() async {
    try {
      final response = await http
          .get(Uri.parse(ApiConfig.listarInventarioUrl))
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        List<ProductoPrecio> inventario = (data['data'] as List)
            .map((json) => ProductoPrecio.fromJson(json))
            .toList();

        return {
          'success': true,
          'message': data['message'],
          'data': inventario,
          'total': data['total'] ?? inventario.length,
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al obtener inventario',
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
