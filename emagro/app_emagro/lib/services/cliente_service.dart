import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../models/cliente.dart';

class ClienteService {
  /// Listar todos los clientes
  Future<Map<String, dynamic>> listarClientes() async {
    try {
      final response = await http
          .get(Uri.parse(ApiConfig.listarClientesUrl))
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        List<Cliente> clientes = (data['data'] as List)
            .map((json) => Cliente.fromJson(json))
            .toList();

        return {
          'success': true,
          'message': data['message'],
          'clientes': clientes,
          'total': data['total'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al obtener clientes',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  /// Crear nuevo cliente
  Future<Map<String, dynamic>> crearCliente({
    required String nombre,
    required String nit,
    required String telefono,
    required String departamento,
    required String municipio,
    required String direccion,
    String? email,
    required int usuarioId,
  }) async {
    try {
      final body = {
        'nombre': nombre,
        'nit': nit,
        'telefono': telefono,
        'departamento': departamento,
        'municipio': municipio,
        'direccion': direccion,
        'usuario_id': usuarioId,
      };

      if (email != null && email.isNotEmpty) {
        body['email'] = email;
      }

      final response = await http
          .post(
            Uri.parse(ApiConfig.crearClienteUrl),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode(body),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 201 && data['success'] == true) {
        return {
          'success': true,
          'message': data['message'] ?? 'Cliente creado exitosamente',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al crear cliente',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  /// Actualizar cliente
  Future<Map<String, dynamic>> actualizarCliente({
    required int id,
    required String nombre,
    required String nit,
    required String telefono,
    required String departamento,
    required String municipio,
    required String direccion,
    String? email,
    required String bloquearVentas,
  }) async {
    try {
      final body = {
        'id': id,
        'nombre': nombre,
        'nit': nit,
        'telefono': telefono,
        'departamento': departamento,
        'municipio': municipio,
        'direccion': direccion,
        'bloquear_ventas': bloquearVentas,
      };

      if (email != null && email.isNotEmpty) {
        body['email'] = email;
      }

      final response = await http
          .put(
            Uri.parse(ApiConfig.actualizarClienteUrl),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode(body),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'message': data['message'] ?? 'Cliente actualizado exitosamente',
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al actualizar cliente',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  /// Eliminar cliente
  Future<Map<String, dynamic>> eliminarCliente(int id) async {
    try {
      final response = await http
          .delete(
            Uri.parse(ApiConfig.eliminarClienteUrl),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({'id': id}),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'message': data['message'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al eliminar cliente',
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
