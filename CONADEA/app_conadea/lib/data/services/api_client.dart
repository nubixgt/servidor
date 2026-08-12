import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../../core/config/api_config.dart';
import 'api_exception.dart';

/// Cliente HTTP delgado sobre el Backend PHP (Backend/api/v1).
/// Traduce respuestas `{status, message, data}` y errores de red a
/// [ApiException] con un mensaje ya listo para el usuario final.
class ApiClient {
  Future<Map<String, dynamic>> get(String path, {String? token}) {
    return _send(() => http.get(_uri(path), headers: _headers(token)));
  }

  Future<Map<String, dynamic>> post(String path, Map<String, dynamic> body, {String? token}) {
    return _send(
      () => http.post(_uri(path), headers: _headers(token), body: jsonEncode(body)),
    );
  }

  Future<Map<String, dynamic>> put(String path, Map<String, dynamic> body, {String? token}) {
    return _send(
      () => http.put(_uri(path), headers: _headers(token), body: jsonEncode(body)),
    );
  }

  /// POST multipart/form-data: los campos van como texto (usa [jsonEncode]
  /// tú mismo si alguno es una estructura anidada) y [files] son los
  /// archivos a subir, nombre de campo -> ruta local (puede ir más de uno,
  /// ej. la imagen del curso + un video por lección).
  Future<Map<String, dynamic>> postMultipart(
    String path, {
    required Map<String, String> fields,
    required Map<String, String> files,
    String? token,
  }) {
    // Timeout largo: puede ir la imagen del curso + varios videos de
    // lección, que juntos pesan mucho más de lo que el timeout corto de
    // las llamadas normales (15s) alcanzaría a subir.
    return _send(() async {
      final request = http.MultipartRequest('POST', _uri(path));
      if (token != null) request.headers['Authorization'] = 'Bearer $token';
      request.fields.addAll(fields);
      for (final entry in files.entries) {
        request.files.add(await http.MultipartFile.fromPath(entry.key, entry.value));
      }

      final streamed = await request.send();
      return http.Response.fromStream(streamed);
    }, timeout: const Duration(minutes: 10));
  }

  Uri _uri(String path) => Uri.parse('${ApiConfig.baseUrl}$path');

  Map<String, String> _headers(String? token) {
    return {
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  Future<Map<String, dynamic>> _send(
    Future<http.Response> Function() request, {
    Duration timeout = const Duration(seconds: 15),
  }) async {
    late final http.Response response;

    try {
      response = await request().timeout(timeout);
    } on SocketException {
      throw const ApiException('No se pudo conectar al servidor. Revisa tu conexión a internet.');
    } catch (_) {
      throw const ApiException('No se pudo conectar al servidor. Intenta de nuevo.');
    }

    Map<String, dynamic> body;
    try {
      body = jsonDecode(response.body) as Map<String, dynamic>;
    } catch (_) {
      throw const ApiException('El servidor respondió de forma inesperada.');
    }

    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw ApiException(body['message'] as String? ?? 'Ocurrió un error inesperado.');
    }

    return body;
  }
}
