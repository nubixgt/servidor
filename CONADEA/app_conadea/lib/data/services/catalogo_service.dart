import '../models/departamento.dart';
import '../models/municipio.dart';
import 'api_client.dart';

/// Catálogos de ubicación (Backend/src/Controllers/LocationController.php),
/// usados por el selector en cascada departamento -> municipio del registro.
class CatalogoService {
  CatalogoService({ApiClient? apiClient}) : _api = apiClient ?? ApiClient();

  final ApiClient _api;

  Future<List<Departamento>> listarDepartamentos() async {
    final response = await _api.get('/ubicacion/departamentos');
    final data = response['data'] as List<dynamic>;
    return data.map((e) => Departamento.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<List<Municipio>> listarMunicipios(int departamentoId) async {
    final response = await _api.get('/ubicacion/municipios/$departamentoId');
    final data = response['data'] as List<dynamic>;
    return data.map((e) => Municipio.fromJson(e as Map<String, dynamic>)).toList();
  }
}
