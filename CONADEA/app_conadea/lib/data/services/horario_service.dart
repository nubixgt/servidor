import '../models/horario.dart';
import 'api_client.dart';
import 'auth_service.dart';

/// Horarios de estudio del usuario logueado
/// (Backend/src/Controllers/HorarioController.php) — alimentan el calendario.
class HorarioService {
  HorarioService({ApiClient? apiClient, AuthService? authService})
      : _api = apiClient ?? ApiClient(),
        _authService = authService ?? AuthService();

  final ApiClient _api;
  final AuthService _authService;

  Future<List<HorarioEstudio>> listar() async {
    final token = await _authService.obtenerToken();
    final response = await _api.get('/horarios', token: token);
    final data = response['data'] as List<dynamic>;
    return data.map((e) => HorarioEstudio.fromJson(e as Map<String, dynamic>)).toList();
  }

  /// Crea o reemplaza el horario de ese curso. [dias] = letras L M X J V S D;
  /// [hora] = "HH:MM" (24 h).
  Future<void> guardar({
    required int cursoId,
    required Set<String> dias,
    required String hora,
    required int duracionMinutos,
  }) async {
    final token = await _authService.obtenerToken();
    await _api.post(
      '/horarios',
      {
        'curso_id': cursoId,
        'dias': dias.join(','),
        'hora': hora,
        'duracion_minutos': duracionMinutos,
      },
      token: token,
    );
  }

  Future<void> actualizarActivo(int cursoId, {required bool activo}) async {
    final token = await _authService.obtenerToken();
    await _api.post('/horarios/activo', {'curso_id': cursoId, 'activo': activo}, token: token);
  }
}
