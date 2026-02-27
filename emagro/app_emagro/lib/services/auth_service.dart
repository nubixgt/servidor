import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';
import '../models/usuario.dart';

class AuthService {
  // Singleton pattern
  static final AuthService _instance = AuthService._internal();
  factory AuthService() => _instance;
  AuthService._internal();

  Usuario? _usuarioActual;
  Usuario? get usuarioActual => _usuarioActual;

  /// Login de usuario
  Future<Map<String, dynamic>> login(String usuario, String contrasena) async {
    try {
      final response = await http
          .post(
            Uri.parse(ApiConfig.loginUrl),
            headers: {'Content-Type': 'application/json'},
            body: jsonEncode({
              'usuario': usuario,
              'contrasena': contrasena,
            }),
          )
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['success'] == true) {
        // Guardar datos del usuario
        _usuarioActual = Usuario.fromJson(data['data']);
        await _guardarSesion(_usuarioActual!);

        return {
          'success': true,
          'message': data['message'],
          'usuario': _usuarioActual,
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Error al iniciar sesión',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error de conexión: ${e.toString()}',
      };
    }
  }

  /// Logout de usuario
  Future<void> logout() async {
    try {
      await http
          .post(
            Uri.parse(ApiConfig.logoutUrl),
            headers: {'Content-Type': 'application/json'},
          )
          .timeout(Duration(seconds: ApiConfig.timeoutSeconds));
    } catch (e) {
      // Ignorar errores de logout
    } finally {
      _usuarioActual = null;
      await _limpiarSesion();
    }
  }

  /// Verificar si hay sesión activa
  Future<bool> verificarSesion() async {
    final prefs = await SharedPreferences.getInstance();
    final usuarioJson = prefs.getString('usuario');

    if (usuarioJson != null) {
      try {
        final usuarioData = jsonDecode(usuarioJson);
        _usuarioActual = Usuario.fromJson(usuarioData);
        return true;
      } catch (e) {
        await _limpiarSesion();
        return false;
      }
    }
    return false;
  }

  /// Guardar sesión en SharedPreferences
  Future<void> _guardarSesion(Usuario usuario) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('usuario', jsonEncode(usuario.toJson()));
  }

  /// Limpiar sesión
  Future<void> _limpiarSesion() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('usuario');
  }
}
