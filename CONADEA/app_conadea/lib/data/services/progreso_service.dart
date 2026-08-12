import 'api_client.dart';
import 'auth_service.dart';

/// Progreso del usuario, persistido en el Backend
/// (Backend/src/Controllers/ProgresoController.php) — antes vivía solo en
/// memoria en ProgresoController y no sobrevivía a cerrar la app.
class ProgresoService {
  ProgresoService({ApiClient? apiClient, AuthService? authService})
      : _api = apiClient ?? ApiClient(),
        _authService = authService ?? AuthService();

  final ApiClient _api;
  final AuthService _authService;

  /// Todo el progreso (lecciones + cursos) del usuario logueado — se pide
  /// una sola vez para poblar ProgresoController al entrar a la app.
  Future<ProgresoRemoto> obtenerTodo() async {
    final token = await _authService.obtenerToken();
    final response = await _api.get('/progreso', token: token);
    return ProgresoRemoto.fromJson(response['data'] as Map<String, dynamic>);
  }

  /// [completada]/[segundosVideo] son opcionales: solo se manda lo que
  /// cambió (marcar como completada, o el checkpoint periódico de
  /// reproducción del video), sin pisar el otro campo en el servidor.
  Future<void> guardarLeccion(int leccionId, {bool? completada, int? segundosVideo}) async {
    final token = await _authService.obtenerToken();
    await _api.put(
      '/lecciones/$leccionId/progreso',
      {
        'completada': ?completada,
        'segundos_video': ?segundosVideo,
      },
      token: token,
    );
  }

  Future<void> guardarEvaluacion(int cursoId, {required int nota, required int total}) async {
    final token = await _authService.obtenerToken();
    await _api.put('/cursos/$cursoId/evaluacion', {'nota': nota, 'total': total}, token: token);
  }
}

/// Snapshot plano de GET /progreso, antes de que ProgresoController lo
/// acomode en su forma interna (`Map<cursoId, ProgresoCurso>`).
class ProgresoRemoto {
  const ProgresoRemoto({required this.lecciones, required this.cursos});

  final List<ProgresoLeccionRemota> lecciones;
  final List<ProgresoCursoRemoto> cursos;

  factory ProgresoRemoto.fromJson(Map<String, dynamic> json) {
    return ProgresoRemoto(
      lecciones: (json['lecciones'] as List<dynamic>)
          .map((e) => ProgresoLeccionRemota.fromJson(e as Map<String, dynamic>))
          .toList(),
      cursos: (json['cursos'] as List<dynamic>)
          .map((e) => ProgresoCursoRemoto.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

class ProgresoLeccionRemota {
  const ProgresoLeccionRemota({
    required this.leccionId,
    required this.cursoId,
    required this.completada,
    required this.segundosVideo,
  });

  final int leccionId;
  final int cursoId;
  final bool completada;
  final int segundosVideo;

  factory ProgresoLeccionRemota.fromJson(Map<String, dynamic> json) {
    return ProgresoLeccionRemota(
      leccionId: json['leccion_id'] as int,
      cursoId: json['curso_id'] as int,
      completada: json['completada'] as bool,
      segundosVideo: json['segundos_video'] as int,
    );
  }
}

class ProgresoCursoRemoto {
  const ProgresoCursoRemoto({
    required this.cursoId,
    required this.nota,
    required this.aprobado,
    required this.fechaAprobado,
  });

  final int cursoId;
  final int? nota;
  final bool aprobado;
  final String? fechaAprobado;

  factory ProgresoCursoRemoto.fromJson(Map<String, dynamic> json) {
    return ProgresoCursoRemoto(
      cursoId: json['curso_id'] as int,
      nota: json['nota'] as int?,
      aprobado: json['aprobado'] as bool,
      fechaAprobado: json['fecha_aprobado'] as String?,
    );
  }
}
