import 'dart:convert';

import '../models/curso.dart';
import 'api_client.dart';
import 'auth_service.dart';

/// Cursos reales (Backend/src/Controllers/CursoController.php). Crear es
/// exclusivo del rol Administrador; listar/obtener funciona para cualquier
/// rol autenticado — el token JWT ya viaja con el rol en ambos casos.
class CursoService {
  CursoService({ApiClient? apiClient, AuthService? authService})
      : _api = apiClient ?? ApiClient(),
        _authService = authService ?? AuthService();

  final ApiClient _api;
  final AuthService _authService;

  /// [datos] es todo lo del formulario menos los archivos (icono, título,
  /// descripción, lecciones, quiz); [imagenPath] es la ruta local del
  /// archivo elegido con la cámara o la galería, y [videosLecciones] trae
  /// (opcionalmente) la ruta local del video de cada lección, indexada por
  /// su posición en `datos['lecciones']` — el video es opcional por
  /// lección, así que no todos los índices tienen que estar presentes.
  /// Viaja todo en una sola petición multipart: el Backend guarda la
  /// imagen y los videos en Backend/uploads/cursos/{id}/... una vez que
  /// sabe el id del curso (y de cada lección).
  Future<void> crearCurso({
    required Map<String, dynamic> datos,
    required String imagenPath,
    Map<int, String> videosLecciones = const {},
  }) async {
    final token = await _authService.obtenerToken();
    await _api.postMultipart(
      '/cursos',
      fields: {'data': jsonEncode(datos)},
      files: {
        'imagen': imagenPath,
        for (final entry in videosLecciones.entries) 'leccion_video_${entry.key}': entry.value,
      },
      token: token,
    );
  }

  /// Catálogo completo (sin lecciones/quiz — GET /cursos solo manda lo
  /// general, para no cargar de más una lista).
  Future<List<Curso>> listarCursos() async {
    final token = await _authService.obtenerToken();
    final response = await _api.get('/cursos', token: token);
    final data = response['data'] as List<dynamic>;
    return data.map((e) => Curso.fromJson(e as Map<String, dynamic>)).toList();
  }

  /// Curso completo, con lecciones y quiz — para abrir el detalle.
  Future<Curso> obtenerCurso(int id) async {
    final token = await _authService.obtenerToken();
    final response = await _api.get('/cursos/$id', token: token);
    return Curso.fromJson(response['data'] as Map<String, dynamic>);
  }
}
