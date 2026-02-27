import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../models/nota_envio.dart';

class NotaEnvioService {
  /// Obtiene el siguiente número de nota correlativo
  Future<Map<String, dynamic>> obtenerSiguienteNumero() async {
    try {
      final response = await http.get(
        Uri.parse(ApiConfig.obtenerSiguienteNumeroUrl),
        headers: {'Content-Type': 'application/json'},
      ).timeout(
        Duration(seconds: ApiConfig.timeoutSeconds),
      );

      final data = json.decode(response.body);

      if (response.statusCode == 200 && data['success']) {
        return {
          'success': true,
          'numero_nota': data['numero_nota'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al obtener el siguiente número',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: $e',
      };
    }
  }

  /// Crea una nota de envío completa con sus productos
  Future<Map<String, dynamic>> crearNota({
    required String fecha,
    required String vendedor,
    required int clienteId,
    required String nit,
    required String direccion,
    required String tipoVenta,
    int? diasCredito,
    required List<Map<String, dynamic>> productos,
    required double subtotal,
    required double descuentoTotal,
    required double total,
    required int usuarioId,
  }) async {
    try {
      final body = json.encode({
        'fecha': fecha,
        'vendedor': vendedor,
        'cliente_id': clienteId,
        'nit': nit,
        'direccion': direccion,
        'tipo_venta': tipoVenta,
        'dias_credito': diasCredito,
        'productos': productos,
        'subtotal': subtotal,
        'descuento_total': descuentoTotal,
        'total': total,
        'usuario_id': usuarioId,
      });

      final response = await http.post(
        Uri.parse(ApiConfig.crearNotaUrl),
        headers: {'Content-Type': 'application/json'},
        body: body,
      ).timeout(
        Duration(seconds: ApiConfig.timeoutSeconds),
      );

      final data = json.decode(response.body);

      if (response.statusCode == 201 && data['success']) {
        return {
          'success': true,
          'message': data['message'],
          'numero_nota': data['numero_nota'],
          'nota_id': data['nota_id'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al crear la nota de envío',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: $e',
      };
    }
  }

  /// Lista todas las notas de envío
  Future<Map<String, dynamic>> listarNotas() async {
    try {
      final response = await http.get(
        Uri.parse(ApiConfig.listarNotasUrl),
        headers: {'Content-Type': 'application/json'},
      ).timeout(
        Duration(seconds: ApiConfig.timeoutSeconds),
      );

      final data = json.decode(response.body);

      if (response.statusCode == 200 && data['success']) {
        final List<NotaEnvio> notas = (data['notas'] as List)
            .map((notaJson) => NotaEnvio.fromJson(notaJson))
            .toList();

        return {
          'success': true,
          'message': data['message'],
          'notas': notas,
          'total': data['total'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al obtener las notas',
          'notas': <NotaEnvio>[],
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: $e',
        'notas': <NotaEnvio>[],
      };
    }
  }


  /// Elimina una nota de envío
  Future<Map<String, dynamic>> eliminarNota(int id) async {
    try {
      final response = await http.post(
        Uri.parse(ApiConfig.eliminarNotaUrl),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'id': id}),
      ).timeout(
        Duration(seconds: ApiConfig.timeoutSeconds),
      );

      final data = json.decode(response.body);

      if (response.statusCode == 200 && data['success']) {
        return {
          'success': true,
          'message': data['message'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al eliminar la nota',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: $e',
      };
    }
  }
}
