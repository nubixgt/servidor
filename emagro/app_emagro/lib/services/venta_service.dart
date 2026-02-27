import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../models/venta.dart';

class VentaService {
  /// Listar todas las ventas
  Future<Map<String, dynamic>> listarVentas() async {
    try {
      final response = await http
          .get(Uri.parse(ApiConfig.listarVentasUrl))
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        List<Venta> ventas = (data['data'] as List)
            .map((json) => Venta.fromJson(json))
            .toList();

        return {
          'success': true,
          'message': data['message'],
          'ventas': ventas,
          'total': data['total'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al obtener ventas',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  /// Crear nueva venta
  Future<Map<String, dynamic>> crearVenta({
    required String fecha,
    required String vendedor,
    required int clienteId,
    required String nit,
    required String direccion,
    required String tipoVenta,
    int? diasCredito,
    required String producto,
    required String presentacion,
    required double precioUnitario,
    required int cantidad,
    required double descuento,
    required double total,
    required int usuarioId,
  }) async {
    try {
      final body = {
        'fecha': fecha,
        'vendedor': vendedor,
        'cliente_id': clienteId,
        'nit': nit,
        'direccion': direccion,
        'tipo_venta': tipoVenta,
        'dias_credito': diasCredito,
        'producto': producto,
        'presentacion': presentacion,
        'precio_unitario': precioUnitario,
        'cantidad': cantidad,
        'descuento': descuento,
        'total': total,
        'usuario_id': usuarioId,
      };

      final response = await http
          .post(
            Uri.parse(ApiConfig.crearVentaUrl),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode(body),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 201 && data['success'] == true) {
        return {
          'success': true,
          'message': data['message'] ?? 'Venta creada exitosamente',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al crear venta',
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
