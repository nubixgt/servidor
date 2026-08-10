import 'dart:convert';

import 'api_client.dart';
import 'auth_service.dart';

/// Creación de cursos (Backend/src/Controllers/CursoController.php),
/// exclusiva del rol Administrador — el token JWT ya viaja con el rol.
class CursoService {
  CursoService({ApiClient? apiClient, AuthService? authService})
      : _api = apiClient ?? ApiClient(),
        _authService = authService ?? AuthService();

  final ApiClient _api;
  final AuthService _authService;

  /// [datos] es todo lo del formulario menos la imagen (icono, título,
  /// descripción, lecciones, quiz); [imagenPath] es la ruta local del
  /// archivo elegido con la cámara o la galería. Viaja todo en una sola
  /// petición multipart: el Backend guarda la imagen en
  /// Backend/uploads/cursos/{id}/ una vez que sabe el id del curso.
  Future<void> crearCurso({required Map<String, dynamic> datos, required String imagenPath}) async {
    final token = await _authService.obtenerToken();
    await _api.postMultipart(
      '/cursos',
      fields: {'data': jsonEncode(datos)},
      fileField: 'imagen',
      filePath: imagenPath,
      token: token,
    );
  }
}
