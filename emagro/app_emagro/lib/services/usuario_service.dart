import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../models/usuario.dart';

class UsuarioService {
  /// Listar todos los usuarios
  Future<Map<String, dynamic>> listarUsuarios() async {
    try {
      final response = await http
          .get(Uri.parse(ApiConfig.listarUsuariosUrl))
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        List<Usuario> usuarios = (data['data'] as List)
            .map((json) => Usuario.fromJson(json))
            .toList();

        return {
          'success': true,
          'message': data['message'],
          'usuarios': usuarios,
          'total': data['total'],
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al obtener usuarios',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  /// Crear nuevo usuario
  Future<Map<String, dynamic>> crearUsuario({
    required String nombre,
    required String usuario,
    required String contrasena,
    required String rol,
    String estado = 'activo',
  }) async {
    try {
      final response = await http
          .post(
            Uri.parse(ApiConfig.crearUsuarioUrl),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({
              'nombre': nombre,
              'usuario': usuario,
              'contrasena': contrasena,
              'rol': rol,
              'estado': estado,
            }),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 201 && data['success'] == true) {
        return {
          'success': true,
          'message': data['message'],
          'usuario': Usuario.fromJson(data['data']),
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al crear usuario',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  /// Actualizar usuario
  Future<Map<String, dynamic>> actualizarUsuario({
    required int id,
    required String nombre,
    required String usuario,
    String? contrasena,
    required String rol,
    required String estado,
  }) async {
    try {
      final body = {
        'id': id,
        'nombre': nombre,
        'usuario': usuario,
        'rol': rol,
        'estado': estado,
      };

      if (contrasena != null && contrasena.isNotEmpty) {
        body['contrasena'] = contrasena;
      }

      final response = await http
          .put(
            Uri.parse(ApiConfig.actualizarUsuarioUrl),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode(body),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        return {
          'success': true,
          'message': data['message'],
          'usuario': Usuario.fromJson(data['data']),
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al actualizar usuario',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  /// Eliminar usuario
  Future<Map<String, dynamic>> eliminarUsuario(int id) async {
    try {
      final response = await http
          .delete(
            Uri.parse(ApiConfig.eliminarUsuarioUrl),
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
          'message': data['message'] ?? 'Error al eliminar usuario',
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
