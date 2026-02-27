import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../models/pago.dart';

class PagoService {
  /// Lista todas las facturas a crédito con saldo pendiente
  Future<Map<String, dynamic>> listarFacturasCredito() async {
    try {
      final response = await http.get(
        Uri.parse(ApiConfig.listarFacturasCreditoUrl),
        headers: {'Content-Type': 'application/json'},
      ).timeout(
        Duration(seconds: ApiConfig.timeoutSeconds),
      );

      final data = json.decode(response.body);

      if (response.statusCode == 200 && data['success']) {
        final List<FacturaCredito> facturas = (data['data'] as List)
            .map((facturaJson) => FacturaCredito.fromJson(facturaJson))
            .toList();

        return {
          'success': true,
          'message': data['message'],
          'facturas': facturas,
          'total': data['total'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al obtener las facturas',
          'facturas': <FacturaCredito>[],
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: $e',
        'facturas': <FacturaCredito>[],
      };
    }
  }

  /// Crea un nuevo pago
  Future<Map<String, dynamic>> crearPago({
    required int facturaId,
    required String fechaPago,
    required String banco,
    required double montoPago,
    required String referenciaTransaccion,
    required int usuarioId,
  }) async {
    try {
      final body = json.encode({
        'factura_id': facturaId,
        'fecha_pago': fechaPago,
        'banco': banco,
        'monto_pago': montoPago,
        'referencia_transaccion': referenciaTransaccion,
        'usuario_id': usuarioId,
      });

      final response = await http.post(
        Uri.parse(ApiConfig.crearPagoUrl),
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
          'pago_id': data['pago_id'],
          'numero_factura': data['numero_factura'],
          'nuevo_saldo': data['nuevo_saldo'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al crear el pago',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: $e',
      };
    }
  }

  /// Lista todos los pagos registrados
  Future<Map<String, dynamic>> listarPagos() async {
    try {
      final response = await http.get(
        Uri.parse(ApiConfig.listarPagosUrl),
        headers: {'Content-Type': 'application/json'},
      ).timeout(
        Duration(seconds: ApiConfig.timeoutSeconds),
      );

      final data = json.decode(response.body);

      if (response.statusCode == 200 && data['success']) {
        final List<Pago> pagos = (data['data'] as List)
            .map((pagoJson) => Pago.fromJson(pagoJson))
            .toList();

        return {
          'success': true,
          'message': data['message'],
          'pagos': pagos,
          'total': data['total'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al obtener los pagos',
          'pagos': <Pago>[],
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: $e',
        'pagos': <Pago>[],
      };
    }
  }

  /// Obtiene el saldo pendiente de una factura específica
  Future<Map<String, dynamic>> obtenerSaldoFactura(int facturaId) async {
    try {
      final response = await http.get(
        Uri.parse('${ApiConfig.obtenerSaldoFacturaUrl}?factura_id=$facturaId'),
        headers: {'Content-Type': 'application/json'},
      ).timeout(
        Duration(seconds: ApiConfig.timeoutSeconds),
      );

      final data = json.decode(response.body);

      if (response.statusCode == 200 && data['success']) {
        return {
          'success': true,
          'message': data['message'],
          'data': data['data'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al obtener el saldo',
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
