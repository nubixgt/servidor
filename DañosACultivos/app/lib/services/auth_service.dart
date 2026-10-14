import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../models/usuario.dart';
import 'api_client.dart';

/// Sesión del usuario (equivale a authService.js). Se guarda en el dispositivo.
class AuthService extends ChangeNotifier {
  static const _kToken = 'token';
  static const _kUser = 'user';

  AuthUser? user;
  bool get autenticado => user != null;

  /// Restaura la sesión guardada si el token no ha vencido.
  Future<void> restaurar() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString(_kToken);
    final raw = prefs.getString(_kUser);
    if (token != null && raw != null && _vigente(token)) {
      ApiClient.instance.token = token;
      user = AuthUser.fromJson(jsonDecode(raw));
    } else {
      await prefs.remove(_kToken);
      await prefs.remove(_kUser);
    }
    ApiClient.instance.onUnauthorized = () => logout();
    notifyListeners();
  }

  Future<void> login(String usuario, String password) async {
    final data = await ApiClient.instance.post('/auth/login', {'usuario': usuario, 'password': password}, auth: false);
    final token = data['token'] as String;
    final u = AuthUser.fromJson(Map<String, dynamic>.from(data['user']));
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_kToken, token);
    await prefs.setString(_kUser, jsonEncode(u.toJson()));
    ApiClient.instance.token = token;
    user = u;
    notifyListeners();
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_kToken);
    await prefs.remove(_kUser);
    ApiClient.instance.token = null;
    user = null;
    notifyListeners();
  }

  bool _vigente(String token) {
    try {
      final p = token.split('.')[1];
      final json = jsonDecode(utf8.decode(base64Url.decode(base64Url.normalize(p))));
      final exp = json['exp'];
      return exp == null || (exp as num) * 1000 > DateTime.now().millisecondsSinceEpoch;
    } catch (_) {
      return false;
    }
  }
}
