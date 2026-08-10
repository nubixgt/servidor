import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../models/usuario_sesion.dart';
import 'api_client.dart';

/// Registro, inicio de sesión y persistencia del token JWT en el
/// dispositivo (Backend/src/Controllers/AuthController.php).
class AuthService {
  AuthService({ApiClient? apiClient, FlutterSecureStorage? storage})
      : _api = apiClient ?? ApiClient(),
        _storage = storage ?? const FlutterSecureStorage();

  static const _tokenKey = 'conadea_token';
  static const _usuarioKey = 'conadea_usuario';

  final ApiClient _api;
  final FlutterSecureStorage _storage;

  Future<UsuarioSesion> registrar({
    required String nombreCompleto,
    required String usuario,
    required String password,
    required String telefono,
    required int departamentoId,
    required int municipioId,
  }) async {
    final response = await _api.post('/auth/register', {
      'nombre_completo': nombreCompleto,
      'usuario': usuario,
      'password': password,
      'telefono': telefono,
      'departamento_id': departamentoId,
      'municipio_id': municipioId,
    });

    return _guardarSesion(response['data'] as Map<String, dynamic>);
  }

  Future<UsuarioSesion> iniciarSesion({
    required String usuario,
    required String password,
  }) async {
    final response = await _api.post('/auth/login', {
      'usuario': usuario,
      'password': password,
    });

    return _guardarSesion(response['data'] as Map<String, dynamic>);
  }

  Future<String?> obtenerToken() => _storage.read(key: _tokenKey);

  Future<UsuarioSesion?> obtenerSesionGuardada() async {
    final raw = await _storage.read(key: _usuarioKey);
    if (raw == null) return null;
    return UsuarioSesion.fromJson(jsonDecode(raw) as Map<String, dynamic>);
  }

  Future<void> cerrarSesion() async {
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _usuarioKey);
  }

  Future<UsuarioSesion> _guardarSesion(Map<String, dynamic> data) async {
    final token = data['token'] as String;
    final usuario = UsuarioSesion.fromJson(data['usuario'] as Map<String, dynamic>);

    await _storage.write(key: _tokenKey, value: token);
    await _storage.write(key: _usuarioKey, value: jsonEncode(data['usuario']));

    return usuario;
  }
}
