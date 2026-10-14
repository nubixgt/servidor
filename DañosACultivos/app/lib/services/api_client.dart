import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';

class ApiException implements Exception {
  final String message;
  final int? status;
  ApiException(this.message, [this.status]);
  @override
  String toString() => message;
}

/// Cliente HTTP con el token JWT (equivale a Frontend/src/services/api.js).
class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();

  String? token;

  /// Se invoca cuando el servidor responde 401 fuera del login (sesión vencida).
  void Function()? onUnauthorized;

  static const _timeout = Duration(seconds: 30);

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Uri _uri(String path, [Map<String, String>? query]) => Uri.parse('${ApiConfig.api}$path').replace(queryParameters: query);

  Future<dynamic> get(String path, {Map<String, String>? query}) => _send(() => http.get(_uri(path, query), headers: _headers));
  Future<dynamic> post(String path, Map<String, dynamic> body, {bool auth = true}) =>
      _send(() => http.post(_uri(path), headers: _headers, body: jsonEncode(body)), login: !auth);
  Future<dynamic> put(String path, Map<String, dynamic> body) => _send(() => http.put(_uri(path), headers: _headers, body: jsonEncode(body)));
  Future<dynamic> delete(String path) => _send(() => http.delete(_uri(path), headers: _headers));

  /// Sube archivos (campo "fotos[]") a un registro.
  Future<dynamic> postFotos(String path, List<File> archivos) async {
    return _send(() async {
      final req = http.MultipartRequest('POST', _uri(path))
        ..headers.addAll({'Accept': 'application/json', if (token != null) 'Authorization': 'Bearer $token'});
      for (final f in archivos) {
        req.files.add(await http.MultipartFile.fromPath('fotos[]', f.path));
      }
      return http.Response.fromStream(await req.send());
    }, timeout: const Duration(minutes: 3));
  }

  Future<dynamic> _send(Future<http.Response> Function() fn, {bool login = false, Duration timeout = _timeout}) async {
    http.Response res;
    try {
      res = await fn().timeout(timeout);
    } on SocketException {
      throw ApiException('No se pudo conectar con el servidor.');
    } on TimeoutException {
      throw ApiException('El servidor tardó demasiado en responder.');
    } on http.ClientException {
      throw ApiException('No se pudo conectar con el servidor.');
    }

    dynamic data;
    try {
      data = res.body.isEmpty ? null : jsonDecode(utf8.decode(res.bodyBytes));
    } catch (_) {
      data = null;
    }

    if (res.statusCode >= 200 && res.statusCode < 300) return data;

    if (res.statusCode == 401 && !login) onUnauthorized?.call();
    final msg = (data is Map && data['error'] != null) ? data['error'].toString() : 'Error del servidor (${res.statusCode}).';
    throw ApiException(msg, res.statusCode);
  }
}
