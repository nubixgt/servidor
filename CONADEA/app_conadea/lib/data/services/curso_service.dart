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

  Future<void> crearCurso(Map<String, dynamic> payload) async {
    final token = await _authService.obtenerToken();
    await _api.post('/cursos', payload, token: token);
  }
}
