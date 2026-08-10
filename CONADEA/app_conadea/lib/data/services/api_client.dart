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

  /// POST multipart/form-data: los campos van como texto (usa [jsonEncode]
  /// tú mismo si alguno es una estructura anidada) y [filePath] es la ruta
  /// local del archivo a subir bajo el nombre de campo [fileField].
  Future<Map<String, dynamic>> postMultipart(
    String path, {
    required Map<String, String> fields,
    required String fileField,
    required String filePath,
    String? token,
  }) {
    return _send(() async {
      final request = http.MultipartRequest('POST', _uri(path));
      if (token != null) request.headers['Authorization'] = 'Bearer $token';
      request.fields.addAll(fields);
      request.files.add(await http.MultipartFile.fromPath(fileField, filePath));

      final streamed = await request.send();
      return http.Response.fromStream(streamed);
    });
  }

  Uri _uri(String path) => Uri.parse('${ApiConfig.baseUrl}$path');

  Map<String, String> _headers(String? token) {
    return {
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  Future<Map<String, dynamic>> _send(Future<http.Response> Function() request) async {
    late final http.Response response;

    try {
      response = await request().timeout(const Duration(seconds: 15));
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
